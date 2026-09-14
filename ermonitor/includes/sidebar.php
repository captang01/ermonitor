<?php
$activeNav = $activeNav ?? '';
$rootPath = $rootPath ?? '';
$userName = $userName ?? 'User';
$userRole = $userRole ?? 'user';
$userRoleLabel = $userRole === 'admin' ? 'Administrator' : 'User';
$userInitial = $userInitial ?? strtoupper(substr($userName, 0, 1));
$isAdminUser = $userRole === 'admin';
?>

<aside class="sidebar" id="sidebar">
    <div class="brand">
        <a class="brand-mark logo-mark" href="<?= htmlspecialchars($rootPath) ?>/admin/dashboard.php" aria-label="ERMonitor dashboard">
            <img src="<?= htmlspecialchars($rootPath) ?>/assets/img/ermonitor-logo.png" alt="ERMonitor logo">
        </a>
        <div class="brand-copy">
            <strong>ERMonitor</strong>
            <span>NOC · Control Room</span>
        </div>
    </div>

    <nav class="nav">
        <p class="nav-label">Operations</p>
        <a class="nav-link <?= $activeNav === "dashboard" ? "is-active" : "" ?>" href="<?= htmlspecialchars($rootPath) ?>/admin/dashboard.php">
            <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
        </a>
        <a class="nav-link <?= $activeNav === "alerts" ? "is-active" : "" ?>" href="<?= htmlspecialchars($rootPath) ?>/admin/alerts.php">
            <i data-lucide="bell-ring"></i><span>Alerts</span>
        </a>
        <a class="nav-link <?= $activeNav === "servers" ? "is-active" : "" ?>" href="<?= htmlspecialchars($rootPath) ?>/admin/servers/index.php">
            <i data-lucide="server"></i><span>Servers</span>
        </a>

        <?php if ($isAdminUser): ?>
            <p class="nav-label">Administrator</p>
            <a class="nav-link <?= $activeNav === "add-server" ? "is-active" : "" ?>" href="<?= htmlspecialchars($rootPath) ?>/admin/servers/add.php">
                <i data-lucide="plus-square"></i><span>Add Node</span>
            </a>
            <a class="nav-link <?= $activeNav === "users" ? "is-active" : "" ?>" href="<?= htmlspecialchars($rootPath) ?>/admin/users.php">
                <i data-lucide="users"></i><span>Users</span>
            </a>
            <a class="nav-link <?= $activeNav === "audit" ? "is-active" : "" ?>" href="<?= htmlspecialchars($rootPath) ?>/admin/audit.php">
                <i data-lucide="shield-check"></i><span>Audit Log</span>
            </a>
        <?php endif; ?>

        <p class="nav-label">Account</p>
        <a class="nav-link <?= $activeNav === "profile" ? "is-active" : "" ?>" href="<?= htmlspecialchars($rootPath) ?>/profile.php">
            <i data-lucide="circle-user-round"></i><span>My Profile</span>
        </a>
        <a class="nav-link <?= $activeNav === "reports" ? "is-active" : "" ?>" href="<?= htmlspecialchars($rootPath) ?>/admin/reports.php">
            <i data-lucide="file-chart-column"></i><span>Reports</span>
        </a>
    </nav>

    <div class="sidebar-foot">
        <div class="live-chip" id="connectionChip">
            <span class="pulse-dot"></span>
            <div><strong>Telemetry</strong><small id="monitoringStatus">STANDBY</small></div>
        </div>

        <a class="user-chip user-chip-link" href="<?= htmlspecialchars($rootPath) ?>/profile.php">
            <div class="avatar"><?= htmlspecialchars($userInitial) ?></div>
            <div><strong><?= htmlspecialchars($userName) ?></strong><small><?= htmlspecialchars($userRoleLabel) ?></small></div>
            <i data-lucide="chevron-right" class="user-chevron"></i>
        </a>

        <form method="POST" action="<?= htmlspecialchars($rootPath) ?>/auth/logout.php" class="logout-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <button class="logout-link" type="submit"><i data-lucide="log-out"></i> Sign out</button>
        </form>
    </div>
</aside>
