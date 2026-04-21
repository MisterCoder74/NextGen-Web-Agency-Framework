<?php
session_start();

header('Content-Type: application/json');


$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success'=>false, 'error'=>'Dati non validi']);
    exit;
}

$file = 'quotes.json';

if (!file_exists($file)) {
    file_put_contents($file, json_encode([]));
}

$quotes = json_decode(file_get_contents($file), true);

$quote_to_save = [
    'date' => $input['date'] ?? date('c'),
    'total' => $input['total'] ?? '0',
    'breakdown' => $input['breakdown'] ?? [],
    'company' => $input['user']['company'] ?? [],

];

$quotes[] = $quote_to_save;

if (file_put_contents($file, json_encode($quotes, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Impossibile salvare']);
}
