<?php
/**
 * System Backup Generator
 * Creates a ZIP archive of all JSON data files and logs the action.
 */

header('Access-Control-Allow-Origin: *');

/**
 * Log an event to the audit log.
 */
function logEvent($action) {
    $logFile = __DIR__ . '/../../audit_log.json';
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action'    => $action,
        'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
        'user_agent'=> $_SERVER['HTTP_USER_AGENT'] ?? 'None'
    ];

    $logs = [];
    if (file_exists($logFile)) {
        $logs = json_decode(file_get_contents($logFile), true) ?: [];
    }
    $logs[] = $entry;
    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
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
    'tools/api/microapps.json' => 'tools/api/microapps.json'
];

logEvent('System Backup Requested');

if (class_exists('ZipArchive')) {
    $tmpFile = tempnam(sys_get_temp_dir(), 'backup_') . '.zip';
    $zip     = new ZipArchive();

    if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        logEvent('System Backup Failed: Could not create ZIP');
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
        logEvent('System Backup Failed: ZIP file is empty or missing');
        http_response_code(500);
        echo json_encode(['error' => 'ZIP file creation failed.']);
        exit;
    }

    logEvent('System Backup Successful');

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="system_backup_' . date('Ymd_His') . '.zip"');
    header('Content-Length: ' . filesize($tmpFile));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    readfile($tmpFile);
    unlink($tmpFile);
    exit;
} else {
    logEvent('System Backup Failed: ZipArchive missing');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ZipArchive extension not available.']);
    exit;
}
