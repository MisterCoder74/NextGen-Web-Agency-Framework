<?php
/**
 * GitHub Bridge for NextGen WebAgency Framework
 * Handles communication with GitHub API
 */

header('Content-Type: application/json');

// Check for GitHub Token
$token = $_SERVER['HTTP_X_GITHUB_TOKEN'] ?? null;
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'GitHub Token is missing.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? $_GET['action'] ?? null;

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

/**
 * Helper function for GitHub API requests
 */
function github_api_request($url, $token, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'NextGen-WebAgency-Framework');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'Accept: application/vnd.github.v3+json',
        'Content-Type: application/json'
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

if ($action === 'deploy') {
    $repoName = $data['repo_name'] ?? null;
    $description = $data['description'] ?? 'Micro-app deployed via NextGen WebAgency Framework';
    $private = $data['private'] ?? true;
    $appId = $data['app_id'] ?? null;

    if (!$repoName || !$appId) {
        echo json_encode(['success' => false, 'message' => 'Repository name and App ID are required.']);
        exit;
    }

    // 1. Create Repository
    $createRepo = github_api_request('/user/repos', $token, 'POST', [
        'name' => $repoName,
        'description' => $description,
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
    // Locate the app directory
    $appDir = __DIR__ . '/../microapps/' . $appId;
    if (!is_dir($appDir)) {
        echo json_encode(['success' => false, 'message' => "App directory not found: $appId"]);
        exit;
    }

    $filesToUpload = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appDir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $filePath = $file->getPathname();
            $relativePath = str_replace($appDir . '/', '', $filePath);
            $filesToUpload[] = [
                'path' => $relativePath,
                'content' => base64_encode(file_get_contents($filePath))
            ];
        }
    }

    foreach ($filesToUpload as $file) {
        $upload = github_api_request("/repos/$owner/$repoName/contents/" . $file['path'], $token, 'PUT', [
            'message' => 'Initial commit via NextGen WebAgency Framework',
            'content' => $file['content']
        ]);

        if (!in_array($upload['code'], [201, 200])) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload ' . $file['path'] . ': ' . ($upload['body']['message'] ?? 'Unknown error')]);
            exit;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Project deployed successfully to GitHub!',
        'repo_url' => $repoUrl
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
