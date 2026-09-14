<?php
require_once __DIR__ . '/../auth/session.php';

function writeAudit(string $action, string $details = '', ?int $userId = null): void
{
    static $loaded = false;
    static $conn = null;

    if (!$loaded) {
        $loaded = true;
        $dbFile = __DIR__ . '/../config/database.php';
        if (is_file($dbFile)) {
            require_once $dbFile;
            $conn = $GLOBALS['conn'] ?? null;
        }
    }

    if (!$conn) {
        return;
    }

    $uid = $userId ?? (isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);

    try {
        $stmt = mysqli_prepare($conn, 'INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)');
        if (!$stmt) {
            return;
        }
        mysqli_stmt_bind_param($stmt, 'issss', $uid, $action, $details, $ip, $ua);
        @mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } catch (Throwable $e) {
        // Audit logging must never take the application down when the
        // optional Step 7 table has not been migrated yet.
        return;
    }
}
