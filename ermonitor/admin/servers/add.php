<?php
require_once "../../auth/check.php";
requireAdmin();
require_once "../../config/database.php";
require_once "../../includes/audit.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrf($_POST["csrf_token"] ?? null)) {
        $error = "Sesi keamanan tidak valid. Muat ulang halaman.";
    }
    $name = trim($_POST["name"] ?? "");
    $ip_address = trim($_POST["ip_address"] ?? "");
    $os = trim($_POST["os"] ?? "");
    $status = $_POST["status"] ?? "offline";
    $description = trim($_POST["description"] ?? "");

    if ($error !== "") {
        // CSRF validation already failed.
    } elseif ($name === "" || $ip_address === "") {
        $error = "Nama server dan IP address wajib diisi.";
    } elseif (strlen($name) > 120 || strlen($os) > 120 || strlen($description) > 65535) {
        $error = "Data server terlalu panjang.";
    } elseif (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        $error = "IP address tidak valid.";
    } elseif (!in_array($status, ["online", "warning", "offline"], true)) {
        $error = "Status server tidak valid.";
    } else {
        $sql = "INSERT INTO servers (name, ip_address, os, status, description) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", $name, $ip_address, $os, $status, $description);

        if (mysqli_stmt_execute($stmt)) {
            writeAudit("server_added", "Server: " . $name . " (" . $ip_address . ")");
            header("Location: index.php");
            exit;
        }

        $error = "Gagal menambahkan server.";
    }
}

$rootPath = "../..";
$activeNav = "add-server";
$pageTitle = "Add node";
$pageSubtitle = "Register a server into the monitoring fleet";

require_once "../../includes/head.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/topbar.php";
?>

<section class="panel form-panel">
    <?php if ($error): ?>
        <div class="alert-box"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
        <div class="form-group">
            <label>Server name</label>
            <input type="text" name="name" placeholder="Web Server" required>
        </div>
        <div class="form-group">
            <label>IP address</label>
            <input type="text" name="ip_address" placeholder="192.168.1.10" required>
        </div>
        <div class="form-group">
            <label>Operating system</label>
            <input type="text" name="os" placeholder="Ubuntu 24.04 LTS">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="online">Online</option>
                <option value="warning">Warning</option>
                <option value="offline">Offline</option>
            </select>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Role, rack, or notes"></textarea>
        </div>
        <div class="form-actions">
            <a class="btn" href="index.php">Cancel</a>
            <button class="btn btn-primary" type="submit">Add to fleet</button>
        </div>
    </form>
</section>

<?php require_once "../../includes/footer.php"; ?>
