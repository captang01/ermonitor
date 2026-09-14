<?php
require_once "../../auth/check.php";
requireAdmin();
require_once "../../config/database.php";
require_once "../../includes/audit.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM servers WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$server = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$server) {
    http_response_code(404);
    exit("Server tidak ditemukan.");
}

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
        $sql = "UPDATE servers SET name = ?, ip_address = ?, os = ?, status = ?, description = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssi", $name, $ip_address, $os, $status, $description, $id);

        if (mysqli_stmt_execute($stmt)) {
            writeAudit("server_updated", "Server ID: " . $id . " · " . $name . " (" . $ip_address . ")");
            header("Location: index.php");
            exit;
        }

        $error = "Gagal memperbarui server.";
    }
}

$rootPath = "../..";
$activeNav = "servers";
$pageTitle = "Edit node";
$pageSubtitle = "Update inventory details for " . $server["name"];

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
            <input type="text" name="name" value="<?= htmlspecialchars($server["name"]) ?>" required>
        </div>
        <div class="form-group">
            <label>IP address</label>
            <input type="text" name="ip_address" value="<?= htmlspecialchars($server["ip_address"]) ?>" required>
        </div>
        <div class="form-group">
            <label>Operating system</label>
            <input type="text" name="os" value="<?= htmlspecialchars($server["os"] ?? "") ?>">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="online" <?= $server["status"] === "online" ? "selected" : "" ?>>Online</option>
                <option value="warning" <?= $server["status"] === "warning" ? "selected" : "" ?>>Warning</option>
                <option value="offline" <?= $server["status"] === "offline" ? "selected" : "" ?>>Offline</option>
            </select>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description"><?= htmlspecialchars($server["description"] ?? "") ?></textarea>
        </div>
        <div class="form-actions">
            <a class="btn" href="index.php">Cancel</a>
            <button class="btn btn-primary" type="submit">Save changes</button>
        </div>
    </form>
</section>

<?php require_once "../../includes/footer.php"; ?>
