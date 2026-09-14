<?php
require_once __DIR__ . '/../auth/check.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config/database.php';

$dbOk = isset($conn) && $conn instanceof mysqli && mysqli_ping($conn);
$prometheusOk = false;
$prometheusCode = 0;

$ch = curl_init('http://127.0.0.1:9090/-/ready');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 2,
    CURLOPT_CONNECTTIMEOUT => 1,
]);
curl_exec($ch);
if (!curl_errno($ch)) {
    $prometheusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $prometheusOk = $prometheusCode === 200;
}
curl_close($ch);

$apache = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Apache/PHP';
$overall = $dbOk && $prometheusOk;

http_response_code($overall ? 200 : 503);
echo json_encode([
    'success' => $overall,
    'application' => 'ERMonitor',
    'server_time' => date('c'),
    'checks' => [
        'php' => PHP_VERSION,
        'apache' => $apache,
        'mysql' => $dbOk ? 'ok' : 'error',
        'prometheus' => $prometheusOk ? 'ok' : 'error',
        'prometheus_http' => $prometheusCode,
    ],
], JSON_PRETTY_PRINT);
