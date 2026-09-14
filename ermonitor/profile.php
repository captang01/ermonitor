<?php
require_once "auth/check.php";
require_once "config/database.php"; require_once "includes/audit.php";
$error = ""; $success = "";
$userId = (int)$_SESSION["user_id"];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrf($_POST["csrf_token"] ?? null)) { $error = "Sesi keamanan tidak valid. Muat ulang halaman."; }
    else {
        $action = $_POST["action"] ?? "profile";
        if ($action === "profile") {
            $name = trim($_POST["name"] ?? "");
            if ($name === "") $error = "Nama tidak boleh kosong.";
            else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name = ? WHERE id = ?"); mysqli_stmt_bind_param($stmt, "si", $name, $userId);
                if (mysqli_stmt_execute($stmt)) { writeAudit("profile_updated", "Display name updated"); $_SESSION["name"] = $name; $success = "Profil berhasil diperbarui."; } else $error = "Gagal memperbarui profil.";
            }
        } elseif ($action === "password") {
            $current = $_POST["current_password"] ?? ""; $new = $_POST["new_password"] ?? ""; $confirm = $_POST["new_password_confirm"] ?? "";
            $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ? LIMIT 1"); mysqli_stmt_bind_param($stmt, "i", $userId); mysqli_stmt_execute($stmt); $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if (!$row || !password_verify($current, $row["password"])) $error = "Password saat ini salah.";
            elseif (strlen($new) < 8) $error = "Password baru minimal 8 karakter.";
            elseif (!hash_equals($new, $confirm)) $error = "Konfirmasi password baru tidak cocok.";
            else { $hash = password_hash($new, PASSWORD_DEFAULT); $up = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?"); mysqli_stmt_bind_param($up, "si", $hash, $userId); mysqli_stmt_execute($up); writeAudit("password_changed", "Account password updated"); $success = "Password berhasil diubah."; }
        }
    }
}
$stmt = mysqli_prepare($conn, "SELECT id, name, username, role FROM users WHERE id = ? LIMIT 1"); mysqli_stmt_bind_param($stmt, "i", $userId); mysqli_stmt_execute($stmt); $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$rootPath = "."; $activeNav = "profile"; $pageTitle = "My Profile"; $pageSubtitle = "Account settings and security";
require_once "includes/head.php"; require_once "includes/sidebar.php"; require_once "includes/topbar.php";
?>
<div class="profile-grid">
<section class="panel profile-hero"><div class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($user["name"] ?? "U",0,1))) ?></div><div><span class="eyebrow">SIGNED IN AS</span><h2><?= htmlspecialchars($user["name"] ?? "User") ?></h2><p>@<?= htmlspecialchars($user["username"] ?? "") ?> · <?= $user["role"] === "admin" ? "Administrator" : "User" ?></p></div></section>
<?php if ($error): ?><div class="alert-box"><?= htmlspecialchars($error) ?></div><?php endif; ?><?php if ($success): ?><div class="success-box"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<section class="panel form-panel"><div class="panel-head"><div><h2>Profile details</h2><p>Update the display name used across ERMonitor.</p></div></div><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>"><input type="hidden" name="action" value="profile"><div class="form-group"><label>Full name</label><input type="text" name="name" value="<?= htmlspecialchars($user["name"] ?? "") ?>" maxlength="80" required></div><div class="form-group"><label>Username</label><input type="text" value="<?= htmlspecialchars($user["username"] ?? "") ?>" disabled></div><div class="form-group"><label>Role</label><input type="text" value="<?= $user["role"] === "admin" ? "Administrator" : "User" ?>" disabled></div><div class="form-actions"><button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Save profile</button></div></form></section>
<section class="panel form-panel"><div class="panel-head"><div><h2>Change password</h2><p>Use at least 8 characters for the new password.</p></div></div><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>"><input type="hidden" name="action" value="password"><div class="form-group"><label>Current password</label><input type="password" name="current_password" required autocomplete="current-password"></div><div class="form-group"><label>New password</label><input type="password" name="new_password" minlength="8" required autocomplete="new-password"></div><div class="form-group"><label>Confirm new password</label><input type="password" name="new_password_confirm" minlength="8" required autocomplete="new-password"></div><div class="form-actions"><button class="btn btn-primary" type="submit"><i data-lucide="key-round"></i> Update password</button></div></form></section>
</div>
<?php require_once "includes/footer.php"; ?>
