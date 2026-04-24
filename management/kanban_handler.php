<?php
header('Content-Type: application/json');

$file = 'kanban.json';

// Ensure file exists
if (!file_exists($file)) {
    file_put_contents($file, json_encode([]));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo file_get_contents($file);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data !== null) {
        // Extract username and tasks
        if (isset($data['tasks']) && is_array($data['tasks'])) {
            $username = $data['username'] ?? 'Anonymous';
            $tasksToSave = $data['tasks'];
        } else {
            $username = 'Anonymous';
            $tasksToSave = $data;
        }

        if (file_put_contents($file, json_encode($tasksToSave, JSON_PRETTY_PRINT))) {
            // Logging
            $logFile = __DIR__ . '/../audit_log.json';
            $entry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'action'    => 'Kanban Board Updated',
                'user'      => $username,
                'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
                'user_agent'=> $_SERVER['HTTP_USER_AGENT'] ?? 'None'
            ];
            $logs = [];
            if (file_exists($logFile)) {
                $logs = json_decode(file_get_contents($logFile), true) ?: [];
            }
            $logs[] = $entry;
            file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));

            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Could not save data']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
