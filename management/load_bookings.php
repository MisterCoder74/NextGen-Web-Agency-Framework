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

// Create file if it doesn't exist
if (!file_exists($jsonFile)) {
    $initialData = [
        'bookings' => []
    ];
    SecurityHelper::writeJson($jsonFile, $initialData);
}

// Read and return data
$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);

if ($data === null) {
    echo json_encode([
        'success' => false,
        'message' => 'Error reading data',
        'bookings' => []
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode($data, JSON_PRETTY_PRINT);
}
?>
