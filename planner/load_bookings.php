<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$jsonFile = 'bookings.json';

// Crea il file se non esiste
if (!file_exists($jsonFile)) {
    $initialData = [
        'bookings' => []
    ];
    file_put_contents($jsonFile, json_encode($initialData, JSON_PRETTY_PRINT));
}

// Leggi e restituisci i dati
$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);

if ($data === null) {
    echo json_encode([
        'success' => false,
        'message' => 'Errore nella lettura dei dati',
        'bookings' => []
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode($data, JSON_PRETTY_PRINT);
}
?>

