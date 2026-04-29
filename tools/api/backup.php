<?php
/**
 * System Backup Generator
 * Creates a ZIP archive of all JSON data files and logs the action.
 */

require_once __DIR__ . '/security_helper.php';

header('Access-Control-Allow-Origin: *');

/* --- Rate limiting (5 backups per minute - expensive operation) --- */
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimit = SecurityHelper::checkRateLimit('backup_' . $clientIp, 5, 60);

if (!$rateLimit['allowed']) {
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Rate limit exceeded. Please wait before creating another backup.',
        'retry_after' => $rateLimit['retry_after']
    ]);
    exit;
}

// List of files to backup relative to this script's directory
$baseDir = realpath(__DIR__ . '/../../');
$filesToBackup = [
    'users.json' => 'users.json',
    'audit_log.json' => 'audit_log.json',
    'management/clients.json' => 'management/clients.json',
    'management/messages.json' => 'management/messages.json',
    'management/projects.json' => 'management/projects.json',
    'management/quotes.json' => 'management/quotes.json',
    'management/setup.json' => 'management/setup.json',
    'management/bookings.json' => 'management/bookings.json',
    'management/kanban.json' => 'management/kanban.json',
    'tools/api/microapps.json' => 'tools/api/microapps.json'
];

$username = $_POST['username'] ?? $_GET['username'] ?? $_GET['u'] ?? 'Anonymous';
SecurityHelper::logEvent('System Backup Requested', $username);

if (class_exists('ZipArchive')) {
    $tmpFile = tempnam(sys_get_temp_dir(), 'backup_') . '.zip';
    $zip     = new ZipArchive();

    if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        SecurityHelper::logEvent('System Backup Failed: Could not create ZIP', $username);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create ZIP archive.']);
        exit;
    }

    foreach ($filesToBackup as $relPath => $zipPath) {
        $fullPath = $baseDir . '/' . $relPath;
        if (file_exists($fullPath)) {
            $zip->addFile($fullPath, $zipPath);
        }
    }

    $zip->close();

    if (!file_exists($tmpFile) || filesize($tmpFile) === 0) {
        SecurityHelper::logEvent('System Backup Failed: ZIP file is empty or missing', $username);
        http_response_code(500);
        echo json_encode(['error' => 'ZIP file creation failed.']);
        exit;
    }

    SecurityHelper::logEvent('System Backup Successful', $username);

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="system_backup_' . date('Ymd_His') . '.zip"');
    header('Content-Length: ' . filesize($tmpFile));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    readfile($tmpFile);
    unlink($tmpFile);
    exit;
} else {
    SecurityHelper::logEvent('System Backup Failed: ZipArchive missing', $username);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ZipArchive extension not available.']);
    exit;
}
