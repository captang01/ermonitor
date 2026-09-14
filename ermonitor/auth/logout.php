<?php
require_once __DIR__ . '/check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(405);
    exit('Metode logout tidak valid.');
}

require_once __DIR__ . '/../includes/audit.php';
writeAudit('logout', 'User signed out');

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
}
session_destroy();
header('Location: ../login.php');
exit;
