<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$jsonFile = 'bookings.json';

// Read POST data
$input = file_get_contents('php://input');
$newBooking = json_decode($input, true);

if (!$newBooking) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid data'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Load existing bookings
if (!file_exists($jsonFile)) {
    $data = ['bookings' => []];
} else {
    $jsonData = file_get_contents($jsonFile);
    $data = json_decode($jsonData, true);
    if ($data === null) {
        $data = ['bookings' => []];
    }
}

// Required fields validation
// Support both old (clientName/serviceType) and new (taskName/description) for transition
$taskName = !empty($newBooking['taskName']) ? $newBooking['taskName'] : (!empty($newBooking['clientName']) ? $newBooking['clientName'] : '');
$description = !empty($newBooking['description']) ? $newBooking['description'] : (!empty($newBooking['serviceType']) ? $newBooking['serviceType'] : '');

$isAllDay = !empty($newBooking['isAllDay']);

if (empty($taskName) || empty($newBooking['date'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ], JSON_PRETTY_PRINT);
    exit;
}

if (!$isAllDay && (empty($newBooking['startTime']) || empty($newBooking['endTime']))) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing time for non All Day task'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Generate ID if not present
if (empty($newBooking['id'])) {
    $newBooking['id'] = time() . rand(1000, 9999);
}

// Add the booking
$data['bookings'][] = $newBooking;

// Save to JSON file
if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT))) {
    $username = $newBooking['username'] ?? 'Anonymous';
    logEvent("Booking Saved: " . ($taskName), $username);
    echo json_encode([
        'success' => true,
        'message' => 'Task saved successfully',
        'booking' => $newBooking
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error during saving'
    ], JSON_PRETTY_PRINT);
}

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
?>
