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
// Support both old (clientName/serviceType) and new (taskName/description) for transition
$taskName = !empty($newBooking['taskName']) ? $newBooking['taskName'] : (!empty($newBooking['clientName']) ? $newBooking['clientName'] : '');
$description = !empty($newBooking['description']) ? $newBooking['description'] : (!empty($newBooking['serviceType']) ? $newBooking['serviceType'] : '');

$isAllDay = !empty($newBooking['isAllDay']);

if (empty($taskName) || empty($newBooking['date'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Campi obbligatori mancanti'
    ], JSON_PRETTY_PRINT);
    exit;
}

if (!$isAllDay && (empty($newBooking['startTime']) || empty($newBooking['endTime']))) {
    echo json_encode([
        'success' => false,
        'message' => 'Orario mancante per task non All Day'
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
        'message' => 'Task salvata con successo',
        'booking' => $newBooking
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Errore durante il salvataggio'
    ], JSON_PRETTY_PRINT);
}
?>
