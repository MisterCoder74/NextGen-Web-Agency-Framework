<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$jsonFile = 'bookings.json';

// Leggi i dati POST
$input = file_get_contents('php://input');
$requestData = json_decode($input, true);

if (!$requestData || empty($requestData['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID prenotazione mancante'
    ], JSON_PRETTY_PRINT);
    exit;
}

$bookingId = $requestData['id'];

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

// Trova ed elimina la prenotazione
$found = false;
$newBookings = [];

foreach ($data['bookings'] as $booking) {
    if ($booking['id'] == $bookingId) {
        $found = true;
        // Non aggiungere questa prenotazione (la elimina)
    } else {
        $newBookings[] = $booking;
    }
}

if (!$found) {
    echo json_encode([
        'success' => false,
        'message' => 'Prenotazione non trovata'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Aggiorna l'array di prenotazioni
$data['bookings'] = $newBookings;

// Salva nel file JSON
if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT))) {
    echo json_encode([
        'success' => true,
        'message' => 'Prenotazione eliminata con successo'
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Errore durante l\'eliminazione'
    ], JSON_PRETTY_PRINT);
}
?>
