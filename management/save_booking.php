<?php
require_once __DIR__ . '/../tools/api/security_helper.php';

SecurityHelper::initSession();

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

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
if (SecurityHelper::writeJson($jsonFile, $data)) {
    $username = $_SESSION['username'];
    SecurityHelper::logEvent("Booking Saved: " . ($taskName), $username);
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
exit; // Remove old logEvent function below
?>
