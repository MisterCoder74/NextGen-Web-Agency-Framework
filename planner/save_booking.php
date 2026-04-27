<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$jsonFile = 'bookings.json';

// Leggi i dati POST
$input = file_get_contents('php://input');
$newBooking = json_decode($input, true);

if (!$newBooking) {
    echo json_encode([
        'success' => false,
        'message' => 'Dati non validi'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Carica prenotazioni esistenti
if (!file_exists($jsonFile)) {
    $data = ['bookings' => []];
} else {
    $jsonData = file_get_contents($jsonFile);
    $data = json_decode($jsonData, true);
    if ($data === null) {
        $data = ['bookings' => []];
    }
}

// Validazione campi obbligatori
if (empty($newBooking['clientName']) || empty($newBooking['serviceType']) || 
    empty($newBooking['date']) || empty($newBooking['startTime']) || empty($newBooking['endTime'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Campi obbligatori mancanti'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Genera ID se non presente
if (empty($newBooking['id'])) {
    $newBooking['id'] = time() . rand(1000, 9999);
}

// Aggiungi la prenotazione
$data['bookings'][] = $newBooking;

// Salva nel file JSON
if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT))) {
    echo json_encode([
        'success' => true,
        'message' => 'Prenotazione salvata con successo',
        'booking' => $newBooking
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Errore durante il salvataggio'
    ], JSON_PRETTY_PRINT);
}
?>

