<?php
/**
 * Vivacity AI — Image Generation Proxy
 * Receives JSON body, forwards to OpenAI-compatible Image Generation API.
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

/**
 * Log an event to the audit log.
 */
function logEvent($action, $username, $params) {
    $logFile = __DIR__ . '/../../audit_log.json';
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action'    => $action,
        'user'      => $username,
        'params'    => $params,
        'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
        'user_agent'=> $_SERVER['HTTP_USER_AGENT'] ?? 'None'
    ];

    $logs = [];
    if (file_exists($logFile)) {
        $logs = json_decode(file_get_contents($logFile), true) ?: [];
    }
    $logs[] = $entry;
    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
}

if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body.']);
    exit;
}

$apiKey   = isset($body['api_key'])  ? trim($body['api_key'])  : '';
$username = isset($body['username']) ? trim($body['username']) : 'Anonymous';
$prompt   = isset($body['prompt'])   ? trim($body['prompt'])   : '';
$model    = isset($body['model'])    ? trim($body['model'])    : 'gpt-image-1';
$size     = !empty($body['size'])    ? trim($body['size'])     : '1024x1024';
$quality  = isset($body['quality'])  ? trim($body['quality'])  : 'medium';

if (empty($apiKey)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing api_key.']);
    exit;
}

if (empty($prompt)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing prompt.']);
    exit;
}

/* --- Whitelist allowed models --- */
$allowedModels = [
    'gpt-image-1.5',
    'gpt-image-1',
    'gpt-image-1-mini',
];

if (!in_array($model, $allowedModels, true)) {
    $model = 'gpt-image-1';
}

/* --- Append mandatory padding instruction --- */
$mandatoryPadding = " MANDATORY: make sure the image content is not cut, and is all included inside the borders of the image with a little internal padding.";
if (stripos($prompt, $mandatoryPadding) === false) {
    $prompt .= $mandatoryPadding;
}

logEvent("Image Generation Request", $username, [
    'model' => $model,
    'size' => $size,
    'quality' => $quality,
    'prompt_length' => strlen($prompt)
]);

/* --- Build OpenAI request --- */
$payload = json_encode([
    'model'   => $model,
    'prompt'  => $prompt,
    'size'    => $size,
    'quality' => $quality,
    'n'       => 1,
]);

/* --- cURL to OpenAI --- */
$ch = curl_init('https://api.openai.com/v1/images/generations');

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

/* Use system CA bundle if available */
$caBundlePaths = [
    '/etc/ssl/certs/ca-certificates.crt',
    '/etc/pki/tls/certs/ca-bundle.crt',
    '/etc/ssl/cert.pem',
    ini_get('curl.cainfo'),
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

/* SSL fallback */
if ($result === false && in_array($curlErrNo, [CURLE_SSL_CACERT, CURLE_SSL_PEER_CERTIFICATE, 60, 77, 35], true)) {
    $ch2 = curl_init('https://api.openai.com/v1/images/generations');
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

/* --- Relay response --- */
http_response_code($httpCode);
echo $result;
