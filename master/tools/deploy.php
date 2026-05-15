<?php
require_once __DIR__ . '/api/security_helper.php';

SecurityHelper::initSession();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

if (!SecurityHelper::verifyCSRFToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

// Log an event to the audit log.
function logEvent($action, $username = 'Anonymous') {
    SecurityHelper::logEvent($action, $username);
}

// Leggo input JSON
$data = json_decode(file_get_contents('php://input'), true);
$username = $_SESSION['username'];

if (!$data || !isset($data['frontend']) || !isset($data['backend'])) {
echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
exit;
}

$frontendCode = $data['frontend'];
$backendCode = $data['backend'];

// Percorso base dove creare microapp
$baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'microapps';

// Crea cartella base se non esiste
if (!file_exists($baseDir)) {
if (!mkdir($baseDir, 0755, true)) {
echo json_encode(['success' => false, 'message' => 'Impossibile creare la cartella microapps']);
exit;
}
// Crea file .htaccess per bloccare listing directory
$htaccessContent = "Options -Indexes\nIndexIgnore *";
file_put_contents($baseDir . DIRECTORY_SEPARATOR . '.htaccess', $htaccessContent);
        
}

// Genera id univoco alfanumerico max 8 caratteri
function generateAppId($length = 8) {
$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$id = '';
for ($i = 0; $i < $length; $i++) {
$id .= $chars[random_int(0, strlen($chars) - 1)];
}
return $id;
}

do {
$appId = generateAppId();
$appDir = $baseDir . DIRECTORY_SEPARATOR . $appId;
} while(file_exists($appDir)); // In caso improbabile di collisione

// Crea cartella app
if (!mkdir($appDir, 0755)) {
echo json_encode(['success' => false, 'message' => 'Impossibile creare la cartella app']);
exit;
}

// Salva i file index.html e backend.php
$frontendFile = $appDir . DIRECTORY_SEPARATOR . 'index.html';
$backendFile = $appDir . DIRECTORY_SEPARATOR . 'backend.php';

if (file_put_contents($frontendFile, $frontendCode) === false) {
echo json_encode(['success' => false, 'message' => 'Impossibile scrivere index.html']);
exit;
}
if (file_put_contents($backendFile, $backendCode) === false) {
echo json_encode(['success' => false, 'message' => 'Impossibile scrivere backend.php']);
exit;
}

// Costruisci URL per accesso (modifica la base URL se serve)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . $host . dirname($_SERVER['PHP_SELF']);
$baseUrl = rtrim($baseUrl, '/\\');
$finalUrl = $baseUrl . "/microapps/" . $appId . "/index.html";

// Registra l'app nel file JSON
$jsonFile = __DIR__ . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'microapps.json';
$apps = [];
if (file_exists($jsonFile)) {
    $apps = json_decode(file_get_contents($jsonFile), true) ?: [];
}
$newApp = [
    'id' => $appId,
    'name' => 'Micro App ' . $appId,
    'url' => $finalUrl,
    'date' => date('Y-m-d H:i:s'),
    'client_id' => $data['client_id'] ?? '',
    'project_id' => $data['project_id'] ?? '',
    'created_by' => $username
];
array_unshift($apps, $newApp);
SecurityHelper::writeJson($jsonFile, $apps);

logEvent("Microapp Deployed: $appId", $username);

exit;
?>