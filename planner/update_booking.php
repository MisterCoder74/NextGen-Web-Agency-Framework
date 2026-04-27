<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$jsonFile = 'bookings.json';

// Leggi i dati POST
$input = file_get_contents('php://input');
$updatedBooking = json_decode($input, true);

if (!$updatedBooking || empty($updatedBooking['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Dati non validi o ID mancante'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Carica prenotazioni esistenti
if (!file_exists($jsonFile)) {
    echo json_encode([
        'success' => false,
        'message' => 'File prenotazioni non trovato'
    ], JSON_PRETTY_PRINT);
    exit;
}

$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);

if ($data === null || !isset($data['bookings'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Errore nella lettura dei dati'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Validazione campi obbligatori
if (empty($updatedBooking['clientName']) || empty($updatedBooking['serviceType']) || 
    empty($updatedBooking['date']) || empty($updatedBooking['startTime']) || empty($updatedBooking['endTime'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Campi obbligatori mancanti'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Trova e aggiorna la prenotazione
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
        'message' => 'Prenotazione non trovata'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Salva nel file JSON
if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT))) {
    echo json_encode([
        'success' => true,
        'message' => 'Prenotazione aggiornata con successo',
        'booking' => $updatedBooking
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Errore durante l\'aggiornamento'
    ], JSON_PRETTY_PRINT);
}
?>

