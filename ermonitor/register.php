<?php
require_once __DIR__ . '/auth/session.php';
if (isset($_SESSION["user_id"])) { header("Location: admin/dashboard.php"); exit; }
$error = $_GET["error"] ?? "";
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#05070c">
<link rel="icon" type="image/png" href="assets/img/favicon.png"><link rel="apple-touch-icon" href="assets/img/ermonitor-logo.png"><link rel="manifest" href="manifest.webmanifest">
<title>Create account · ERMonitor</title><link rel="stylesheet" href="assets/css/style.css">
</head>
<body><div class="login-body"><div class="bg-grid"></div><div class="bg-glow"></div>
<div class="login-card auth-card-animated">
<div class="login-mark logo-mark"><img src="assets/img/ermonitor-logo.png" alt="ERMonitor logo"></div><h1>Create account</h1><p class="subtitle">Join ERMonitor as a standard monitoring user</p>
<?php if ($error): ?><div class="alert-box"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form action="auth/register.php" method="POST" autocomplete="off">
<div class="form-group"><label>Full name</label><input type="text" name="name" maxlength="80" required autocomplete="name"></div>
<div class="form-group"><label>Username</label><input type="text" name="username" maxlength="40" pattern="[A-Za-z0-9_.-]{3,40}" required autocomplete="username"></div>
<div class="form-group"><label>Password</label><input type="password" name="password" minlength="8" required autocomplete="new-password"></div>
<div class="form-group"><label>Confirm password</label><input type="password" name="password_confirm" minlength="8" required autocomplete="new-password"></div>
<button class="btn btn-primary auth-submit" type="submit">Register as User</button>
</form>
<div class="auth-divider"><span>ALREADY REGISTERED?</span></div><a class="btn auth-secondary" href="login.php">Back to sign in</a>
</div></div></body></html>
