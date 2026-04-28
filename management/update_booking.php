<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$jsonFile = 'bookings.json';

// Read POST data
$input = file_get_contents('php://input');
$updatedBooking = json_decode($input, true);

if (!$updatedBooking || empty($updatedBooking['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid data or missing ID'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Load existing bookings
if (!file_exists($jsonFile)) {
    echo json_encode([
        'success' => false,
        'message' => 'Bookings file not found'
    ], JSON_PRETTY_PRINT);
    exit;
}

$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);

if ($data === null || !isset($data['bookings'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Error reading data'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Required fields validation
$taskName = !empty($updatedBooking['taskName']) ? $updatedBooking['taskName'] : (!empty($updatedBooking['clientName']) ? $updatedBooking['clientName'] : '');
$isAllDay = !empty($updatedBooking['isAllDay']);

if (empty($taskName) || empty($updatedBooking['date'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ], JSON_PRETTY_PRINT);
    exit;
}

if (!$isAllDay && (empty($updatedBooking['startTime']) || empty($updatedBooking['endTime']))) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing time for non All Day task'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Find and update the booking
$found = false;
foreach ($data['bookings'] as $key => $booking) {
    if ($booking['id'] == $updatedBooking['id']) {
        $data['bookings'][$key] = $updatedBooking;
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode([
        'success' => false,
        'message' => 'Task not found'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Save to JSON file
if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT))) {
    $username = $updatedBooking['username'] ?? 'Anonymous';
    logEvent("Booking Updated: " . ($taskName), $username);
    echo json_encode([
        'success' => true,
        'message' => 'Task updated successfully',
        'booking' => $updatedBooking
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error during update'
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
