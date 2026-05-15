<?php
require_once __DIR__ . '/../tools/api/security_helper.php';

SecurityHelper::initSession();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

if (!SecurityHelper::verifyCSRFToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
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

// Log an event to the audit log.
function logEvent($action, $username = 'Anonymous') {
    SecurityHelper::logEvent($action, $username);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success'=>false, 'error'=>'Invalid data']);
    exit;
}

$username = $_SESSION['username'];

// Permission Check
$config = getSetupConfig();
if (strtolower($config['mode'] ?? '') === 'control' && getUserRole($username) === 'technician') {
    echo json_encode(['success' => false, 'error' => 'Permission denied: Managers only in CONTROL mode']);
    exit;
}

$file = 'quotes.json';

if (!file_exists($file)) {
    SecurityHelper::writeJson($file, []);
}

$content = file_get_contents($file);
$quotes = json_decode($content, true);
if (!is_array($quotes)) {
    $quotes = [];
}

$quote_to_save = [
    'date' => $input['date'] ?? date('c'),
    'jobtype' => $input['jobtype'] ?? 'unknown',
    'total' => $input['total'] ?? '0',
    'breakdown' => $input['breakdown'] ?? [],
    'company' => $input['company'] ?? [],
    'client' => $input['client'] ?? null,
    'discountRate' => $input['discountRate'] ?? 0,
    'ivaRate' => $input['ivaRate'] ?? 0,
    'ivaAmount' => $input['ivaAmount'] ?? 0,
    'subtotal' => $input['subtotal'] ?? 0,
];

$quotes[] = $quote_to_save;

if (SecurityHelper::writeJson($file, $quotes)) {
    $clientInfo = 'Unknown Client';
    if (isset($input['client']) && is_array($input['client'])) {
        $clientName = $input['client']['nominativo'] ?? 'Unknown Name';
        $clientId = $input['client']['id'] ?? 'Manual';
        $clientInfo = "$clientName [ID: $clientId]";
    }
    logEvent("Quote Saved for: " . $clientInfo, $username);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Unable to save']);
}
