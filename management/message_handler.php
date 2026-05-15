<?php
require_once __DIR__ . '/../tools/api/security_helper.php';

SecurityHelper::initSession();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

$messagesFile = __DIR__ . '/messages.json';

if (!file_exists($messagesFile)) {
    SecurityHelper::writeJson($messagesFile, []);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $messages = json_decode(file_get_contents($messagesFile), true);
    echo json_encode($messages ?: []);
} elseif ($method === 'POST') {
    if (!SecurityHelper::verifyCSRFToken()) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token.']);
        exit;
    }
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
        'project_id' => isset($data['project_id']) ? $data['project_id'] : 'general',
        'username' => $_SESSION['username'],
        'timestamp' => date('c'),
        'status' => 'unanswered'
    ];

    // When someone replies, mark all previous messages from the OTHER role as answered
    $otherRole = ($data['role'] === 'manager') ? 'tech' : 'manager';
    foreach ($messages as &$msg) {
        if ($msg['role'] === $otherRole && $msg['status'] === 'unanswered') {
            $msg['status'] = 'answered';
        }
    }

    $messages[] = $newMessage;
    SecurityHelper::writeJson($messagesFile, $messages);
    
    echo json_encode($newMessage);
}
?>
