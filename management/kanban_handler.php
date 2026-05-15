<?php
require_once __DIR__ . '/../tools/api/security_helper.php';

SecurityHelper::initSession();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$file = 'kanban.json';

// Ensure file exists
if (!file_exists($file)) {
    SecurityHelper::writeJson($file, []);
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
$username = $_SESSION['username'];

if ($method === 'GET') {
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

        if (SecurityHelper::writeJson($file, $tasksToSave)) {
            // Logging
            SecurityHelper::logEvent('Kanban Board Updated', $username);

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
