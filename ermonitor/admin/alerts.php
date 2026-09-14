<?php
require_once "../auth/check.php";
require_once "../config/database.php";
$alerts = [];
$result = mysqli_query($conn, "SELECT id,name,ip_address,os,status,description,updated_at FROM servers WHERE status IN ('warning','offline') ORDER BY FIELD(status,'offline','warning'), id DESC");
if ($result) while ($row = mysqli_fetch_assoc($result)) $alerts[] = $row;
$events = [];
$eventsTableReady = false;
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'alert_events'");
$eventsTableReady = $tableCheck && mysqli_num_rows($tableCheck) > 0;
if ($eventsTableReady) {
    $result = mysqli_query($conn, "SELECT server_name,previous_status,status,event_type,message,created_at FROM alert_events ORDER BY id DESC LIMIT 20");
    if ($result) while ($row = mysqli_fetch_assoc($result)) $events[] = $row;
}
$rootPath=".."; $activeNav="alerts"; $pageTitle="Alerts"; $pageSubtitle="Active incidents and alert transition history";
require_once "../includes/head.php"; require_once "../includes/sidebar.php"; require_once "../includes/topbar.php";
?>
<section class="panel"><div class="panel-head"><div><h2>Active incidents</h2><p><?=count($alerts)?> server<?=count($alerts)===1?'':'s'?> currently flagged</p></div><a class="ghost-btn" href="servers/index.php">Open fleet</a></div>
<?php if($alerts): ?><div class="alert-list"><?php foreach($alerts as $alert): ?><article class="alert-item <?=$alert['status']==='offline'?'is-offline':''?>"><div class="alert-ico"><i data-lucide="<?=$alert['status']==='offline'?'unplug':'alert-triangle'?>"></i></div><div><h3><?=htmlspecialchars($alert['name'])?></h3><p><?=htmlspecialchars($alert['ip_address'])?> · <?=htmlspecialchars($alert['os']?:'OS unknown')?> · status <span class="mono"><?=htmlspecialchars($alert['status'])?></span></p></div><time class="mono"><?=htmlspecialchars($alert['updated_at']??'')?></time></article><?php endforeach; ?></div>
<?php else: ?><div class="empty">All registered servers are online. No active incidents.</div><?php endif; ?></section>
<section class="panel"><div class="panel-head"><div><h2>Recent alert events</h2><p>Status transitions recorded by ERMonitor.</p></div></div>
<?php if(!$eventsTableReady): ?><div class="alert-box" style="margin:16px 18px 0">Database Step 7 belum selesai. Jalankan <span class="mono">tools\upgrade-step7.bat</span> sekali untuk mengaktifkan riwayat alert.</div><?php endif; ?><div class="table-scroll"><table class="data"><thead><tr><th>Time</th><th>Server</th><th>Transition</th><th>Event</th><th>Message</th></tr></thead><tbody>
<?php foreach($events as $event): ?><tr><td class="mono"><?=htmlspecialchars($event['created_at'])?></td><td><div class="server-title"><?=htmlspecialchars($event['server_name'])?></div></td><td class="mono"><?=htmlspecialchars($event['previous_status']?:'new')?> → <?=htmlspecialchars($event['status'])?></td><td><span class="pill pill-<?=$event['event_type']==='recovered'?'online':($event['status']==='offline'?'offline':'warning')?>"><?=htmlspecialchars($event['event_type'])?></span></td><td><?=htmlspecialchars($event['message']?:'—')?></td></tr><?php endforeach; ?>
<?php if(!$events): ?><tr><td colspan="5" class="empty">Belum ada riwayat perubahan status.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require_once "../includes/footer.php"; ?>
