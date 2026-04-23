<?php
header('Content-Type: application/json');

$messagesFile = __DIR__ . '/messages.json';

if (!file_exists($messagesFile)) {
    file_put_contents($messagesFile, json_encode([]));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $messages = json_decode(file_get_contents($messagesFile), true);
    echo json_encode($messages ?: []);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['text']) || !isset($data['role'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }

    $messages = json_decode(file_get_contents($messagesFile), true);
    if (!$messages) $messages = [];
    
    $newMessage = [
        'id' => uniqid(),
        'text' => $data['text'],
        'role' => $data['role'], // 'manager' or 'tech'
        'timestamp' => date('c'),
        'status' => $data['role'] === 'manager' ? 'unanswered' : 'answered'
    ];

    if ($data['role'] === 'tech') {
        // When tech replies, mark all previous manager messages as answered
        foreach ($messages as &$msg) {
            if ($msg['role'] === 'manager' && $msg['status'] === 'unanswered') {
                $msg['status'] = 'answered';
            }
        }
    }

    $messages[] = $newMessage;
    file_put_contents($messagesFile, json_encode($messages, JSON_PRETTY_PRINT));
    
    echo json_encode($newMessage);
}
?>
