<?php
require_once __DIR__ . '/session.php';
require_once "../config/database.php";
require_once "../includes/audit.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") { header("Location: ../login.php"); exit; }
$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";
if ($username === "" || $password === "") { header("Location: ../login.php?error=" . urlencode("Username dan password wajib diisi.")); exit; }

$stmt = mysqli_prepare($conn, "SELECT id, name, username, password, role FROM users WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $username); mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$user || !password_verify($password, $user["password"])) {
    writeAudit('login_failed', 'Username: ' . $username, null);
    header("Location: ../login.php?error=" . urlencode("Username atau password salah.")); exit;
}

session_regenerate_id(true);
$_SESSION["user_id"] = (int)$user["id"];
$_SESSION["name"] = $user["name"];
$_SESSION["username"] = $user["username"];
$_SESSION["role"] = in_array($user["role"], ["admin", "user"], true) ? $user["role"] : "user";
$_SESSION["csrf_token"] = bin2hex(random_bytes(32));
writeAudit('login_success', 'Successful login', (int)$user['id']);
header("Location: ../admin/dashboard.php"); exit;
