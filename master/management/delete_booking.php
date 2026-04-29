<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$jsonFile = 'bookings.json';

// Read POST data
$input = file_get_contents('php://input');
$requestData = json_decode($input, true);

if (!$requestData || empty($requestData['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing booking ID'
    ], JSON_PRETTY_PRINT);
    exit;
}

$bookingId = $requestData['id'];

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

// Find and delete the booking
$found = false;
$newBookings = [];

foreach ($data['bookings'] as $booking) {
    if ($booking['id'] == $bookingId) {
        $found = true;
        // Don't add this booking (deletes it)
    } else {
        $newBookings[] = $booking;
    }
}

if (!$found) {
    echo json_encode([
        'success' => false,
        'message' => 'Booking not found'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Update the bookings array
$data['bookings'] = $newBookings;

// Save to JSON file
if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT))) {
    $username = $requestData['username'] ?? 'Anonymous';
    logEvent("Booking Deleted: " . ($bookingId), $username);
    echo json_encode([
        'success' => true,
        'message' => 'Booking deleted successfully'
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error during deletion'
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
