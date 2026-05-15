<?php
/**
 * Session Info Endpoint
 * Returns current session data and CSRF token.
 */

require_once __DIR__ . '/security_helper.php';

SecurityHelper::initSession();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['authenticated' => false]);
    exit;
}

echo json_encode([
    'authenticated' => true,
    'username' => $_SESSION['username'],
    'role' => $_SESSION['role'],
    'tenant' => $_SESSION['tenant'] ?? null,
    'csrf_token' => SecurityHelper::generateCSRFToken()
]);
