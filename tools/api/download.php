<?php
/**
 * WebForge AI — ZIP Download Generator
 * Accepts a JSON array of {name, content} file objects and returns a ZIP archive.
 * Falls back to JSON response if ZipArchive is unavailable.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

/* --- Read input --- */
$filesJson   = isset($_POST['files'])        ? $_POST['files']        : '';
$projectName = isset($_POST['project_name']) ? $_POST['project_name'] : 'project';

if (empty($filesJson)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing files parameter.']);
    exit;
}

$files = json_decode($filesJson, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($files)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid files JSON.']);
    exit;
}

/* --- Sanitize project name --- */
$safeProjectName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $projectName);
if (empty($safeProjectName)) $safeProjectName = 'project';

/* --- Attempt ZIP creation --- */
if (class_exists('ZipArchive')) {
    $tmpFile = tempnam(sys_get_temp_dir(), 'wf_') . '.zip';
    $zip     = new ZipArchive();

    if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to create ZIP archive.']);
        exit;
    }

    foreach ($files as $file) {
        if (!isset($file['name']) || !isset($file['content'])) continue;

        /* Sanitize the file path inside the ZIP */
        $filePath = $safeProjectName . '/' . ltrim($file['name'], '/');
        /* Prevent directory traversal */
        $filePath = str_replace(['../', '..\\', '..'], '', $filePath);

        /* Create intermediate directories in ZIP */
        $dirPath = dirname($filePath);
        if ($dirPath !== '.' && $dirPath !== $safeProjectName) {
            $parts = explode('/', $dirPath);
            $accumulated = '';
            foreach ($parts as $part) {
                $accumulated .= ($accumulated ? '/' : '') . $part;
                if ($zip->locateName($accumulated . '/') === false) {
                    $zip->addEmptyDir($accumulated);
                }
            }
        }

        $zip->addFromString($filePath, $file['content']);
    }

    $zip->close();

    if (!file_exists($tmpFile) || filesize($tmpFile) === 0) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'ZIP file creation failed.']);
        exit;
    }

    /* Stream ZIP to client */
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $safeProjectName . '.zip"');
    header('Content-Length: ' . filesize($tmpFile));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    readfile($tmpFile);
    unlink($tmpFile);
    exit;

} else {
    /*
     * ZipArchive not available — return JSON with files so the
     * frontend can trigger individual downloads.
     */
    header('Content-Type: application/json');
    echo json_encode([
        'zip_available' => false,
        'message'       => 'ZipArchive extension not available. Files returned as JSON.',
        'files'         => $files,
    ]);
    exit;
}
