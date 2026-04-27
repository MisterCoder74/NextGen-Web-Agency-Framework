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
    $username = $_GET['u'] ?? 'Anonymous';
    $config = getSetupConfig();
    $allTasks = json_decode(file_get_contents($file), true) ?: [];
    
    $role = getUserRole($username);
    if (strtolower($config['mode'] ?? '') === 'control' && $role === 'technician') {
        $filteredTasks = array_filter($allTasks, function($task) use ($username) {
            return isset($task['created_by']) && $task['created_by'] === $username;
        });
        echo json_encode(array_values($filteredTasks));
    } else {
        echo json_encode($allTasks);
    }
} elseif ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    if ($data !== null) {
        $username = $data['username'] ?? 'Anonymous';
        $config = getSetupConfig();
        $role = getUserRole($username);
        
        // Extract incoming tasks
        $incomingTasks = isset($data['tasks']) && is_array($data['tasks']) ? $data['tasks'] : $data;

        // Determine save strategy
        if (strtolower($config['mode'] ?? '') === 'control' && $role === 'technician') {
            // Merge-on-save for technicians in control mode
            $masterTasks = json_decode(file_get_contents($file), true) ?: [];
            
            // Keep all tasks not created by this technician (including legacy tasks)
            $otherUsersTasks = array_filter($masterTasks, function($task) use ($username) {
                return !isset($task['created_by']) || $task['created_by'] !== $username;
            });
            
            // Force created_by on all incoming tasks to be current user
            foreach ($incomingTasks as &$task) {
                $task['created_by'] = $username;
            }
            
            // Combine
            $tasksToSave = array_merge(array_values($otherUsersTasks), array_values($incomingTasks));
        } else {
            // Manager or SYNC mode: full overwrite
            $tasksToSave = $incomingTasks;
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
