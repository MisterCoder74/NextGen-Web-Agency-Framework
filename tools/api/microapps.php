<?php
header('Content-Type: application/json');

$jsonFile = __DIR__ . DIRECTORY_SEPARATOR . 'microapps.json';
$appsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'microapps';

$action = $_GET['action'] ?? '';
$username = $_GET['username'] ?? $_GET['u'] ?? '';
$postData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $postData = json_decode($rawInput, true) ?: [];
    if (!$username) {
        $username = $postData['username'] ?? $postData['u'] ?? '';
    }
}

function getUserRole($username) {
    $usersFile = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'users.json';
    if (!file_exists($usersFile)) return 'technician';
    
    $users = json_decode(file_get_contents($usersFile), true);
    if (!is_array($users)) return 'technician';
    
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            return $user['role'] ?? 'technician';
        }
    }
    return 'technician';
}

function getSetupConfig() {
    $setupFile = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'management' . DIRECTORY_SEPARATOR . 'setup.json';
    if (file_exists($setupFile)) {
        return json_decode(file_get_contents($setupFile), true);
    }
    return [];
}

// Permission Check for CONTROL mode
$restricted_actions = ['rename', 'assign', 'delete'];
if (in_array($action, $restricted_actions)) {
    $config = getSetupConfig();
    $mode = strtolower($config['mode'] ?? 'sync');
    $role = getUserRole($username);

    if ($mode === 'control' && $role === 'technician') {
        echo json_encode(['success' => false, 'message' => 'Restricted action: Managers only in CONTROL mode.']);
        exit;
    }
}

switch ($action) {
    case 'list':
        if (!file_exists($jsonFile)) {
            echo json_encode([]);
            exit;
        }
        $apps = json_decode(file_get_contents($jsonFile), true) ?: [];
        
        $config = getSetupConfig();
        $mode = strtolower($config['mode'] ?? 'sync');
        $role = getUserRole($username);

        if ($mode === 'control' && $role === 'technician') {
            $projectsFile = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'management' . DIRECTORY_SEPARATOR . 'projects.json';
            $assignedClientIds = [];
            if (file_exists($projectsFile)) {
                $projects = json_decode(file_get_contents($projectsFile), true) ?: [];
                foreach ($projects as $p) {
                    if (($p['assigned_to'] ?? '') === $username) {
                        $assignedClientIds[] = $p['cliente_id'];
                    }
                }
            }
            
            $apps = array_filter($apps, function($app) use ($assignedClientIds) {
                return in_array($app['client_id'] ?? '', $assignedClientIds);
            });
            $apps = array_values($apps);
        }
        
        echo json_encode($apps);
        break;

    case 'rename':
        $data = $postData;
        $id = $data['id'] ?? '';
        $newName = $data['name'] ?? '';

        if (!$id || !$newName) {
            echo json_encode(['success' => false, 'message' => 'ID and Name are required']);
            exit;
        }

        if (!file_exists($jsonFile)) {
            echo json_encode(['success' => false, 'message' => 'Database not found']);
            exit;
        }

        $apps = json_decode(file_get_contents($jsonFile), true);
        $found = false;
        foreach ($apps as &$app) {
            if ($app['id'] === $id) {
                $app['name'] = $newName;
                $found = true;
                break;
            }
        }

        if ($found) {
            file_put_contents($jsonFile, json_encode($apps, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'App not found']);
        }
        break;

    case 'assign':
        $data = $postData;
        $id = $data['id'] ?? '';
        $clientId = $data['client_id'] ?? '';

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'App ID is required']);
            exit;
        }

        if (!file_exists($jsonFile)) {
            echo json_encode(['success' => false, 'message' => 'Database not found']);
            exit;
        }

        $apps = json_decode(file_get_contents($jsonFile), true);
        $found = false;
        foreach ($apps as &$app) {
            if ($app['id'] === $id) {
                $app['client_id'] = $clientId;
                $found = true;
                break;
            }
        }

        if ($found) {
            file_put_contents($jsonFile, json_encode($apps, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'App not found']);
        }
        break;

    case 'delete':
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID is required']);
            exit;
        }

        // Validate ID to prevent Path Traversal
        if (!preg_match('/^[a-zA-Z0-9]{1,16}$/', $id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID format']);
            exit;
        }
        
        if (!file_exists($jsonFile)) {
            echo json_encode(['success' => false, 'message' => 'Database not found']);
            exit;
        }

        $apps = json_decode(file_get_contents($jsonFile), true);
        $newApps = [];
        $appToDelete = null;

        foreach ($apps as $app) {
            if ($app['id'] === $id) {
                $appToDelete = $app;
            } else {
                $newApps[] = $app;
            }
        }

        if ($appToDelete) {
            // Delete the directory
            $dir = $appsDir . DIRECTORY_SEPARATOR . $id;
            if (file_exists($dir)) {
                deleteDirectory($dir);
            }
            file_put_contents($jsonFile, json_encode($newApps, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'App not found']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    return rmdir($dir);
}
