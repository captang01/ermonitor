<?php
require_once "../../auth/check.php";
require_once "../../config/database.php";

$result = mysqli_query($conn, "SELECT * FROM servers ORDER BY id DESC");

$rootPath = "../..";
$activeNav = "servers";
$pageTitle = "Server fleet";
$pageSubtitle = "Inventory, health flags, and node administration";

require_once "../../includes/head.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/topbar.php";
?>

<div class="toolbar">
    <p class="mono" style="color:var(--muted)">Registered nodes in the monitoring database</p>
    <?php if (isAdmin()): ?>
        <a class="btn btn-primary" href="add.php"><i data-lucide="plus"></i> Add node</a>
    <?php endif; ?>
</div>

<section class="panel table-panel">
    <div class="table-scroll">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <table class="data">
                <thead>
                    <tr>
                        <th>Server</th>
                        <th>IP Address</th>
                        <th>Operating System</th>
                        <th>Status</th>
                        <th>Created</th>
                        <?php if (isAdmin()): ?><th>Action</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php while ($server = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>
                            <div class="server-title"><?= htmlspecialchars($server["name"]) ?></div>
                        </td>
                        <td class="mono"><?= htmlspecialchars($server["ip_address"]) ?></td>
                        <td><?= htmlspecialchars($server["os"] ?? "—") ?></td>
                        <td>
                            <span class="pill pill-<?= htmlspecialchars($server["status"]) ?>">
                                <?= htmlspecialchars($server["status"]) ?>
                            </span>
                        </td>
                        <td class="mono"><?= date("d M Y", strtotime($server["created_at"])) ?></td>
                        <?php if (isAdmin()): ?>
                        <td>
                            <div class="actions">
                                <a class="btn btn-edit" href="edit.php?id=<?= (int) $server["id"] ?>">Edit</a>
                                <form method="POST" action="delete.php" class="inline-form" onsubmit="return confirm('Hapus server ini dari fleet?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $server["id"] ?>">
                                    <button class="btn btn-delete" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty">Belum ada server. Tambahkan node pertama ke control room.</div>
        <?php endif; ?>
    </div>
</section>

<?php require_once "../../includes/footer.php"; ?>
