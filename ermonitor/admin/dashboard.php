<?php
require_once __DIR__ . '/../auth/check.php';
require_once "../config/database.php";

$totalServers = $onlineServers = $warningServers = $offlineServers = 0;

$sql = "
    SELECT
        COUNT(*) AS total,
        SUM(status = 'online') AS online,
        SUM(status = 'warning') AS warning,
        SUM(status = 'offline') AS offline
    FROM servers
";

$result = mysqli_query($conn, $sql);

if ($result) {
    $stats = mysqli_fetch_assoc($result);
    $totalServers = (int) ($stats["total"] ?? 0);
    $onlineServers = (int) ($stats["online"] ?? 0);
    $warningServers = (int) ($stats["warning"] ?? 0);
    $offlineServers = (int) ($stats["offline"] ?? 0);
}

$servers = [];
$result = mysqli_query($conn, "
    SELECT id, name, ip_address, os, status, description, created_at
    FROM servers
    ORDER BY id DESC
");

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $servers[] = $row;
    }
}

$rootPath = "..";
$activeNav = "dashboard";
$pageTitle = "Operations Dashboard";
$pageSubtitle = "Live telemetry from Prometheus and the server fleet";

require_once "../includes/head.php";
require_once "../includes/sidebar.php";
require_once "../includes/topbar.php";

$gaugeCirc = 2 * M_PI * 52;
?>

<div class="kpi-grid">
    <article class="kpi">
        <div class="kpi-head">
            <span class="kpi-label">Fleet</span>
            <span class="kpi-ico total"><i data-lucide="server"></i></span>
        </div>
        <div class="kpi-value" id="totalServers"><?= $totalServers ?></div>
    </article>
    <article class="kpi">
        <div class="kpi-head">
            <span class="kpi-label">Online</span>
            <span class="kpi-ico ok"><i data-lucide="activity"></i></span>
            </div>
        <div class="kpi-value is-ok" id="onlineServers"><?= $onlineServers ?></div>
    </article>
    <article class="kpi">
        <div class="kpi-head">
            <span class="kpi-label">Warning</span>
            <span class="kpi-ico warn"><i data-lucide="alert-triangle"></i></span>
            </div>
        <div class="kpi-value is-warn" id="warningServers"><?= $warningServers ?></div>
    </article>
    <article class="kpi">
        <div class="kpi-head">
            <span class="kpi-label">Offline</span>
            <span class="kpi-ico bad"><i data-lucide="unplug"></i></span>
        </div>
        <div class="kpi-value is-bad" id="offlineServers"><?= $offlineServers ?></div>
    </article>
    </div>

<div class="monitor-grid">
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Host telemetry</h2>
                <p id="telemetrySource">Detecting this computer…</p>
    </div>
            <span class="badge-live" id="monitoringLive">
                <span class="pulse-dot"></span>
                LIVE
            </span>
            </div>
        <div class="gauges">
            <div class="gauge">
                <svg viewBox="0 0 120 120" aria-hidden="true">
                    <circle cx="60" cy="60" r="52" fill="none" stroke="#151d2a" stroke-width="10"></circle>
                    <circle id="cpuGauge" cx="60" cy="60" r="52" fill="none" stroke="#2ee9d0" stroke-width="10"
                        stroke-linecap="round" transform="rotate(-90 60 60)"
                        stroke-dasharray="<?= $gaugeCirc ?>" stroke-dashoffset="<?= $gaugeCirc ?>"></circle>
                    <text x="60" y="58" text-anchor="middle" fill="#e8eef7" font-size="18" font-family="IBM Plex Mono, monospace" id="cpuValue">0.0</text>
                    <text x="60" y="76" text-anchor="middle" fill="#5b6b82" font-size="10" font-family="IBM Plex Mono, monospace">%</text>
                </svg>
                <div class="gauge-label">CPU</div>
                <div class="gauge-meta" id="cpuStatusLabel">idle</div>
            </div>
            <div class="gauge">
                <svg viewBox="0 0 120 120" aria-hidden="true">
                    <circle cx="60" cy="60" r="52" fill="none" stroke="#151d2a" stroke-width="10"></circle>
                    <circle id="ramGauge" cx="60" cy="60" r="52" fill="none" stroke="#a78bfa" stroke-width="10"
                        stroke-linecap="round" transform="rotate(-90 60 60)"
                        stroke-dasharray="<?= $gaugeCirc ?>" stroke-dashoffset="<?= $gaugeCirc ?>"></circle>
                    <text x="60" y="58" text-anchor="middle" fill="#e8eef7" font-size="18" font-family="IBM Plex Mono, monospace" id="ramValue">0.0</text>
                    <text x="60" y="76" text-anchor="middle" fill="#5b6b82" font-size="10" font-family="IBM Plex Mono, monospace">%</text>
                </svg>
                <div class="gauge-label">Memory</div>
                <div class="gauge-meta" id="ramMeta">— / — GB</div>
            </div>
    </div>
        <div class="resource-stack" style="padding-top:0">
            <div>
                <div class="resource-head">
                    <span>CPU load</span>
                    <strong id="cpuBarLabel">0.0%</strong>
            </div>
                <div class="track track-cpu"><span id="cpuBar"></span></div>
            </div>
            <div>
                <div class="resource-head">
                    <span>RAM load</span>
                    <strong id="ramBarLabel">0.0%</strong>
                </div>
                <div class="track track-ram"><span id="ramBar"></span></div>
                </div>
            </div>
    </section>

    <section class="panel chart-panel">
        <div class="panel-head">
        <div>
                <h2>Resource timeline</h2>
                <p>CPU &amp; RAM sampled every 1 second</p>
            </div>
            <div class="chart-legend">
                <span class="chart-legend-item"><i class="dot dot-cpu"></i>CPU</span>
                <span class="chart-legend-item"><i class="dot dot-ram"></i>RAM</span>
            </div>
            </div>
        <div class="chart-wrap">
            <canvas id="usageChart"></canvas>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Node health</h2>
                <p>Probe target status</p>
            </div>
            <span class="pill pill-offline" id="serverStatus">offline</span>
        </div>
        <div class="node-status">
            <div class="info-row">
                <span>Hostname</span>
                <strong id="nodeName">detecting…</strong>
    </div>
            <div class="info-row">
                <span>Address</span>
                <strong id="nodeIp">—</strong>
                </div>
            <div class="info-row">
                <span>Instance</span>
                <strong id="nodeInstance">auto</strong>
                </div>
            <div class="info-row">
                <span>Probe</span>
                <strong id="nodeUp">checking</strong>
            </div>
            <div class="info-row">
                <span>CPU state</span>
                <strong id="cpuHealth">—</strong>
            </div>
            <div class="info-row">
                <span>RAM state</span>
                <strong id="ramHealth">—</strong>
        </div>
                </div>
    </section>
                </div>

<section class="panel analyst-panel">
    <div class="panel-head">
        <div><h2>Automatic analysis</h2><p>Kesimpulan telemetry terbaru</p></div>
        <span class="pill pill-online" id="analysisStatus">WAITING</span>
    </div>
    <div class="analysis-grid">
        <div class="analysis-card"><span>CPU</span><strong id="analysisCpu">—</strong><small id="analysisCpuText">Menunggu data</small></div>
        <div class="analysis-card"><span>Memory</span><strong id="analysisRam">—</strong><small id="analysisRamText">Menunggu data</small></div>
        <div class="analysis-card"><span>Assessment</span><strong id="analysisOverall">—</strong><small id="analysisOverallText">Menunggu telemetry</small></div>
    </div>
</section>

<section class="panel table-panel">
    <div class="panel-head">
        <div>
            <h2>Monitored servers</h2>
            <p>Inventory stored in ERMonitor</p>
                </div>
        <a class="ghost-btn" href="servers/index.php">Manage fleet</a>
            </div>
    <div class="table-scroll">
        <table class="data">
            <thead>
                <tr>
                    <th>Server</th>
                    <th>IP Address</th>
                    <th>OS</th>
                    <th>Status</th>
                    <th>Added</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($servers) > 0): ?>
                <?php foreach ($servers as $server): ?>
                    <tr>
                        <td>
                            <div class="server-title"><?= htmlspecialchars($server["name"]) ?></div>
                            <?php if (!empty($server["description"])): ?>
                                <small class="mono"><?= htmlspecialchars($server["description"]) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="mono"><?= htmlspecialchars($server["ip_address"]) ?></td>
                        <td><?= htmlspecialchars($server["os"] ?? "—") ?></td>
                        <td>
                            <span class="pill pill-<?= htmlspecialchars($server["status"]) ?>">
                                <?= htmlspecialchars($server["status"]) ?>
                            </span>
                        </td>
                        <td class="mono"><?= date("d M Y", strtotime($server["created_at"])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="empty">No nodes yet. Add a server to start tracking the fleet.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    </section>

<?php require_once "../includes/footer.php"; ?>
