<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success'=>false, 'error'=>'Invalid data']);
    exit;
}

$file = 'quotes.json';

if (!file_exists($file)) {
    file_put_contents($file, json_encode([]));
}

$content = file_get_contents($file);
$quotes = json_decode($content, true);
if (!is_array($quotes)) {
    $quotes = [];
}

$quote_to_save = [
    'date' => $input['date'] ?? date('c'),
    'jobtype' => $input['jobtype'] ?? 'unknown',
    'total' => $input['total'] ?? '0',
    'breakdown' => $input['breakdown'] ?? [],
    'company' => $input['company'] ?? [],
    'discountRate' => $input['discountRate'] ?? 0,
    'ivaRate' => $input['ivaRate'] ?? 0,
    'ivaAmount' => $input['ivaAmount'] ?? 0,
    'subtotal' => $input['subtotal'] ?? 0,
];

$quotes[] = $quote_to_save;

if (file_put_contents($file, json_encode($quotes, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Unable to save']);
}
