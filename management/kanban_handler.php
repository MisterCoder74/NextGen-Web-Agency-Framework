<?php
header('Content-Type: application/json');

$file = 'kanban.json';

// Ensure file exists
if (!file_exists($file)) {
    file_put_contents($file, json_encode([]));
}

function getUserRole($username) {
    $usersFile = __DIR__ . '/../users.json';
    if (!file_exists($usersFile)) return 'technician';
    $users = json_decode(file_get_contents($usersFile), true);
    if (!is_array($users)) return 'technician';
    foreach ($users as $user) {
        if ($user['username'] === $username) return $user['role'] ?? 'technician';
    }
    return 'technician';
}

function getSetupConfig() {
    $setupFile = __DIR__ . '/setup.json';
    return file_exists($setupFile) ? json_decode(file_get_contents($setupFile), true) : [];
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo file_get_contents($file);
} elseif ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    if ($data !== null) {
        $username = $data['username'] ?? 'Anonymous';
        
        // Permission Check
        $config = getSetupConfig();
        if (strtolower($config['mode'] ?? '') === 'control' && getUserRole($username) === 'technician') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied: Managers only in CONTROL mode']);
            exit;
        }

        // Extract tasks
        if (isset($data['tasks']) && is_array($data['tasks'])) {
            $tasksToSave = $data['tasks'];
        } else {
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
