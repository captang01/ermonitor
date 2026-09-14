<?php
require_once __DIR__ . '/session.php';
require_once "../config/database.php";
require_once "../includes/audit.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") { header("Location: ../register.php"); exit; }
$name = trim($_POST["name"] ?? "");
$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";
$confirm = $_POST["password_confirm"] ?? "";
if ($name === "" || $username === "" || $password === "" || $confirm === "") {
    header("Location: ../register.php?error=" . urlencode("Semua field wajib diisi.")); exit;
}
if (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $username)) {
    header("Location: ../register.php?error=" . urlencode("Username hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda minus.")); exit;
}
if (strlen($password) < 8) { header("Location: ../register.php?error=" . urlencode("Password minimal 8 karakter.")); exit; }
if (!hash_equals($password, $confirm)) { header("Location: ../register.php?error=" . urlencode("Konfirmasi password tidak cocok.")); exit; }
$check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($check, "s", $username); mysqli_stmt_execute($check);
if (mysqli_fetch_assoc(mysqli_stmt_get_result($check))) { header("Location: ../register.php?error=" . urlencode("Username sudah digunakan.")); exit; }
$hash = password_hash($password, PASSWORD_DEFAULT);
$role = "user";
$stmt = mysqli_prepare($conn, "INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "ssss", $name, $username, $hash, $role);
if (!mysqli_stmt_execute($stmt)) { header("Location: ../register.php?error=" . urlencode("Registrasi gagal. Pastikan tabel users memiliki kolom name, username, password, dan role.")); exit; }
writeAudit('user_registered', 'New user: ' . $username, (int)mysqli_insert_id($conn));
header("Location: ../login.php?success=" . urlencode("Akun berhasil dibuat. Silakan login.")); exit;
