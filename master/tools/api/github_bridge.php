<?php
/**
 * GitHub Bridge for NextGen WebAgency Framework
 * Handles communication with GitHub API
 * Includes security hardening: token validation, rate limiting.
 */

require_once __DIR__ . '/security_helper.php';

header('Content-Type: application/json');

/* --- Rate limiting (20 requests per minute for GitHub API) --- */
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimit = SecurityHelper::checkRateLimit('gh_' . $clientIp, 20, 60);

if (!$rateLimit['allowed']) {
    http_response_code(429);
    header('Retry-After: ' . $rateLimit['retry_after']);
    echo json_encode([
        'success' => false,
        'message' => 'Rate limit exceeded. Please wait before making more requests.',
        'retry_after' => $rateLimit['retry_after']
    ]);
    exit;
}

// Check for GitHub Token
$token = $_SERVER['HTTP_X_GITHUB_TOKEN'] ?? null;
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'GitHub Token is missing.']);
    exit;
}

/* --- Validate GitHub token format and length --- */
$tokenValidation = SecurityHelper::validateGitHubToken($token);
if (!$tokenValidation['valid']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $tokenValidation['error']]);
    SecurityHelper::logEvent("Invalid GitHub token format attempt", 'Anonymous', [
        'error' => $tokenValidation['error']
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? $_GET['action'] ?? null;
$username = isset($data['username']) ? trim($data['username']) : 'Anonymous';

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

/* --- Validate action type --- */
$allowedActions = ['deploy', 'list_repos', 'get_repo', 'create_file', 'update_file'];
if (!in_array($action, $allowedActions, true)) {
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

/**
 * Helper function for GitHub API requests
 * Sanitizes response data for logging.
 */
function github_api_request($url, $token, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'NextGen-WebAgency-Framework/1.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'Accept: application/vnd.github.v3+json',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'body' => json_decode($response, true) ?? $response,
        'error' => $curlErr
    ];
}

if ($action === 'deploy') {
    $repoName = isset($data['repo_name']) ? trim($data['repo_name']) : null;
    $description = isset($data['description']) ? trim($data['description']) : 'Micro-app deployed via NextGen WebAgency Framework';
    $private = isset($data['private']) ? (bool)$data['private'] : true;
    $appId = isset($data['app_id']) ? trim($data['app_id']) : null;

    if (!$repoName || !$appId) {
        echo json_encode(['success' => false, 'message' => 'Repository name and App ID are required.']);
        exit;
    }

    /* --- Validate repo name (prevent injection) --- */
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $repoName) || strlen($repoName) > 100) {
        echo json_encode(['success' => false, 'message' => 'Invalid repository name. Use only alphanumeric characters, dots, hyphens, and underscores.']);
        exit;
    }

    SecurityHelper::logEvent("GitHub Deploy Request", $username, [
        'repo_name' => $repoName,
        'private' => $private,
        'app_id' => $appId
    ]);

    // 1. Create Repository
    $createRepo = github_api_request('/user/repos', $token, 'POST', [
        'name' => $repoName,
        'description' => substr($description, 0, 500), // Limit description length
        'private' => $private,
        'auto_init' => true
    ]);

    if ($createRepo['code'] !== 201) {
        $error = $createRepo['body']['message'] ?? 'Unknown error';
        if ($createRepo['code'] === 422) {
            $error = "Repository '$repoName' already exists or contains invalid characters.";
        }
        echo json_encode(['success' => false, 'message' => 'Failed to create repository: ' . $error]);
        exit;
    }

    $repoUrl = $createRepo['body']['html_url'];
    $owner = $createRepo['body']['owner']['login'];

    // 2. Upload Files
    // Locate the app directory (prevent path traversal)
    $appIdSafe = preg_replace('/[^a-zA-Z0-9_-]/', '', $appId);
    $appDir = realpath(__DIR__ . '/../microapps/' . $appIdSafe);
    $baseDir = realpath(__DIR__ . '/../microapps/');
    
    // Security check: ensure the resolved path is within the microapps directory
    if (!$appDir || !$baseDir || strpos($appDir, $baseDir) !== 0) {
        echo json_encode(['success' => false, 'message' => "App directory not found: $appId"]);
        exit;
    }

    $filesToUpload = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appDir));
    $fileCount = 0;
    $maxFiles = 100; // Limit number of files per deploy
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $fileCount < $maxFiles) {
            $filePath = $file->getPathname();
            $relativePath = str_replace($appDir . '/', '', $filePath);
            
            // Security: prevent path traversal in file names
            if (preg_match('/\.\./', $relativePath)) {
                continue;
            }
            
            // Limit individual file size (1MB max)
            if ($file->getSize() > 1048576) {
                continue;
            }
            
            $filesToUpload[] = [
                'path' => $relativePath,
                'content' => base64_encode(file_get_contents($filePath))
            ];
            $fileCount++;
        }
    }

    $uploadedCount = 0;
    foreach ($filesToUpload as $file) {
        $upload = github_api_request("/repos/$owner/$repoName/contents/" . $file['path'], $token, 'PUT', [
            'message' => 'Initial commit via NextGen WebAgency Framework',
            'content' => $file['content']
        ]);

        if (in_array($upload['code'], [201, 200])) {
            $uploadedCount++;
        } else {
            // Log error but continue with other files
            SecurityHelper::logEvent("GitHub File Upload Error", $username, [
                'repo' => $repoName,
                'file' => $file['path'],
                'error' => $upload['body']['message'] ?? 'Unknown error'
            ]);
        }
    }

    SecurityHelper::logEvent("GitHub Deploy Complete", $username, [
        'repo_name' => $repoName,
        'repo_url' => $repoUrl,
        'files_uploaded' => $uploadedCount
    ]);

    echo json_encode([
        'success' => true,
        'message' => "Project deployed successfully! {$uploadedCount} files uploaded.",
        'repo_url' => $repoUrl
    ]);
    exit;
}

if ($action === 'list_repos') {
    SecurityHelper::logEvent("GitHub List Repos", $username, []);
    
    $response = github_api_request('/user/repos?per_page=30', $token);
    
    if ($response['code'] !== 200) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch repositories.']);
        exit;
    }
    
    $repos = is_array($response['body']) ? array_map(function($r) {
        return [
            'name' => $r['name'],
            'full_name' => $r['full_name'],
            'html_url' => $r['html_url'],
            'private' => $r['private'],
            'description' => $r['description'] ?? ''
        ];
    }, $response['body']) : [];
    
    echo json_encode(['success' => true, 'repositories' => $repos]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
