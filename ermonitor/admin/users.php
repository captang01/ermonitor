<?php
require_once "../auth/check.php"; requireAdmin(); require_once "../config/database.php"; require_once "../includes/audit.php";
$message=""; $error=""; $self=(int)$_SESSION["user_id"];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrf($_POST["csrf_token"] ?? null)) $error="Sesi keamanan tidak valid.";
    else {
        $id=(int)($_POST["id"] ?? 0); $action=$_POST["action"] ?? "";
        if ($action === "role" && $id > 0) {
            $role = ($_POST["role"] ?? "user") === "admin" ? "admin" : "user";
            if ($id === $self && $role !== "admin") $error="Akun administrator yang sedang dipakai tidak bisa diturunkan sendiri.";
            else { $stmt=mysqli_prepare($conn,"UPDATE users SET role=? WHERE id=?"); mysqli_stmt_bind_param($stmt,"si",$role,$id); mysqli_stmt_execute($stmt); writeAudit("user_role_changed", "User ID: " . $id . " → " . $role); $message="Role pengguna diperbarui."; }
        } elseif ($action === "delete" && $id > 0) {
            if ($id === $self) $error="Akun yang sedang login tidak bisa dihapus.";
            else { $stmt=mysqli_prepare($conn,"DELETE FROM users WHERE id=?"); mysqli_stmt_bind_param($stmt,"i",$id); mysqli_stmt_execute($stmt); writeAudit("user_deleted", "User ID: " . $id); $message="Pengguna dihapus."; }
        }
    }
}
$result=mysqli_query($conn,"SELECT id,name,username,role FROM users ORDER BY id DESC");
$rootPath=".."; $activeNav="users"; $pageTitle="User management"; $pageSubtitle="Control standard users and administrator access";
require_once "../includes/head.php"; require_once "../includes/sidebar.php"; require_once "../includes/topbar.php";
?>
<section class="panel"><div class="panel-head"><div><h2>Accounts</h2><p>New registrations always start as User. Promote only trusted accounts.</p></div><span class="pill pill-online">ADMIN ONLY</span></div>
<?php if($error): ?><div class="alert-box" style="margin:16px 18px 0"><?=htmlspecialchars($error)?></div><?php endif; ?><?php if($message): ?><div class="success-box" style="margin:16px 18px 0"><?=htmlspecialchars($message)?></div><?php endif; ?>
<div class="table-scroll"><table class="data"><thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Action</th></tr></thead><tbody>
<?php while($u=mysqli_fetch_assoc($result)): ?><tr><td><div class="server-title"><?=htmlspecialchars($u["name"])?></div></td><td class="mono">@<?=htmlspecialchars($u["username"])?></td><td><span class="pill <?= $u["role"] === "admin" ? "pill-online" : "pill-warning" ?>"><?= $u["role"] === "admin" ? "Administrator" : "User" ?></span></td><td><div class="actions">
<form method="POST" class="inline-form"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrfToken())?>"><input type="hidden" name="id" value="<?= (int)$u["id"] ?>"><input type="hidden" name="action" value="role"><select name="role"><option value="user" <?=$u["role"]==='user'?'selected':''?>>User</option><option value="admin" <?=$u["role"]==='admin'?'selected':''?>>Administrator</option></select><button class="btn btn-edit" type="submit">Save role</button></form>
<?php if((int)$u["id"] !== $self): ?><form method="POST" class="inline-form" onsubmit="return confirm('Hapus akun ini?')"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrfToken())?>"><input type="hidden" name="id" value="<?= (int)$u["id"] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-delete" type="submit">Delete</button></form><?php endif; ?></div></td></tr><?php endwhile; ?></tbody></table></div></section>
<?php require_once "../includes/footer.php"; ?>
