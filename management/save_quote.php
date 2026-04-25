<?php
header('Content-Type: application/json');

// Log an event to the audit log.
function logEvent($action, $username = 'Anonymous') {
    $logFile = __DIR__ . '/../audit_log.json';
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action'    => $action,
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
}

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? 'Anonymous';

if (!$input) {
    echo json_encode(['success'=>false, 'error'=>'Invalid data']);
    exit;
}

$file = 'quotes.json';

if (!file_exists($file)) {
    file_put_contents($file, json_encode([]));
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

if (file_put_contents($file, json_encode($quotes, JSON_PRETTY_PRINT))) {
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
