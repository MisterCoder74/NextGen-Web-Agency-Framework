<?php
/**
 * WebForge AI — OpenAI Chat Proxy
 * Receives JSON body, forwards to OpenAI Chat Completions API.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

/* --- Read & validate body --- */
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body.']);
    exit;
}

$apiKey   = isset($body['api_key'])    ? trim($body['api_key'])    : '';
$model    = isset($body['model'])      ? trim($body['model'])      : 'gpt-4.1-nano';
$messages = isset($body['messages'])   ? $body['messages']         : [];
$maxTok   = isset($body['max_tokens']) ? (int)$body['max_tokens']  : 16384;
$temp     = isset($body['temperature'])? (float)$body['temperature']: 0.3;

if (empty($apiKey)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing api_key.']);
    exit;
}

if (empty($messages) || !is_array($messages)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid messages array.']);
    exit;
}

/* --- Whitelist allowed models --- */
$allowedModels = [
    'gpt-4o-mini',
    'gpt-4.1-nano',
    'gpt-4.1-mini',
];

if (!in_array($model, $allowedModels, true)) {
    $model = 'gpt-4.1-nano';
}

/* --- Build OpenAI request --- */
$payload = json_encode([
    'model'       => $model,
    'messages'    => $messages,
    'max_tokens'  => min($maxTok, 16384),
    'temperature' => max(0.0, min(2.0, $temp)),
]);

/* --- cURL to OpenAI --- */
$ch = curl_init('https://api.openai.com/v1/chat/completions');

$curlOpts = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_TIMEOUT        => 180,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
];

/* Use system CA bundle if available; otherwise fall back to bundled cacert */
$caBundlePaths = [
    '/etc/ssl/certs/ca-certificates.crt',          // Debian/Ubuntu
    '/etc/pki/tls/certs/ca-bundle.crt',            // RHEL/CentOS
    '/etc/ssl/cert.pem',                            // macOS / Alpine
    ini_get('curl.cainfo'),                         // php.ini override
];
foreach ($caBundlePaths as $ca) {
    if (!empty($ca) && file_exists($ca)) {
        $curlOpts[CURLOPT_CAINFO] = $ca;
        break;
    }
}

curl_setopt_array($ch, $curlOpts);

$result   = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
$curlErrNo= curl_errno($ch);
curl_close($ch);

/* If SSL peer verification failed, retry without it (shared hosting fallback) */
if ($result === false && in_array($curlErrNo, [CURLE_SSL_CACERT, CURLE_SSL_PEER_CERTIFICATE, 60, 77, 35], true)) {
    $ch2 = curl_init('https://api.openai.com/v1/chat/completions');
    $curlOpts[CURLOPT_SSL_VERIFYPEER] = false;
    $curlOpts[CURLOPT_SSL_VERIFYHOST] = 0;
    unset($curlOpts[CURLOPT_CAINFO]);
    curl_setopt_array($ch2, $curlOpts);
    $result   = curl_exec($ch2);
    $httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch2);
    curl_close($ch2);
}

if ($result === false || $result === '') {
    http_response_code(502);
    echo json_encode([
        'error' => [
            'message' => 'cURL failed to reach api.openai.com. Error: ' . $curlErr,
            'curl_errno' => $curlErrNo,
        ],
    ]);
    exit;
}

/* --- Relay OpenAI response as-is --- */
http_response_code($httpCode);
echo $result;
