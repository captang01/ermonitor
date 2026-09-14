<?php
require_once __DIR__ . '/auth/session.php';
if (isset($_SESSION["user_id"])) {
    header("Location: admin/dashboard.php");
    exit;
}
$error = $_GET["error"] ?? "";
$success = $_GET["success"] ?? "";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#05070c">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="apple-touch-icon" href="assets/img/ermonitor-logo.png">
    <link rel="manifest" href="manifest.webmanifest">
    <title>Sign in · ERMonitor</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-body">
    <div class="bg-grid" aria-hidden="true"></div><div class="bg-glow" aria-hidden="true"></div>
    <div class="login-card auth-card-animated">
        <div class="login-mark logo-mark"><img src="assets/img/ermonitor-logo.png" alt="ERMonitor logo"></div>
        <h1>ERMonitor</h1>
        <p class="subtitle">Secure access to the infrastructure control room</p>
        <?php if ($error): ?><div class="alert-box"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-box"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <form action="auth/login.php" method="POST">
            <div class="form-group"><label>Username</label><input type="text" name="username" placeholder="operator" required autofocus autocomplete="username"></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="••••••••" required autocomplete="current-password"></div>
            <button class="btn btn-primary auth-submit" type="submit">Enter dashboard</button>
        </form>
        <div class="auth-divider"><span>NEW OPERATOR</span></div>
        <a class="btn auth-secondary" href="register.php">Create user account</a>
        <p class="auth-note">Registration creates a standard User account. Administrator access is managed by an existing administrator.</p>
    </div>
</div>

</body>
</html>
