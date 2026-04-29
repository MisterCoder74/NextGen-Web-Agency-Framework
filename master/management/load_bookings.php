<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$jsonFile = 'bookings.json';

// Create file if it doesn't exist
if (!file_exists($jsonFile)) {
    $initialData = [
        'bookings' => []
    ];
    file_put_contents($jsonFile, json_encode($initialData, JSON_PRETTY_PRINT));
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
