<?php
header('Content-Type: application/json');

$jsonFile = __DIR__ . DIRECTORY_SEPARATOR . 'microapps.json';
$appsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'microapps';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        if (!file_exists($jsonFile)) {
            echo json_encode([]);
            exit;
        }
        echo file_get_contents($jsonFile);
        break;

    case 'rename':
        $data = json_decode(file_get_contents('php://input'), true);
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
        $data = json_decode(file_get_contents('php://input'), true);
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
