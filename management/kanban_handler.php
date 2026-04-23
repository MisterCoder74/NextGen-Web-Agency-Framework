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
        if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT))) {
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
