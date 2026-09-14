<?php
require_once __DIR__ . '/session.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: " . ermonitorUrl('login.php'));
    exit;
}

function isAdmin(): bool
{
    return ($_SESSION["role"] ?? "user") === "admin";
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        http_response_code(403);
        exit("Akses ditolak. Halaman ini khusus administrator.");
    }
}

function csrfToken(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function verifyCsrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION["csrf_token"])
        && hash_equals($_SESSION["csrf_token"], $token);
}
