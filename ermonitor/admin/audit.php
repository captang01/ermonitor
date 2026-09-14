<?php
require_once "../auth/check.php"; requireAdmin(); require_once "../config/database.php";
$logs=[]; $auditTableReady=false; $tc=mysqli_query($conn,"SHOW TABLES LIKE 'audit_logs'"); $auditTableReady=$tc && mysqli_num_rows($tc)>0; if($auditTableReady){$result=mysqli_query($conn,"SELECT a.created_at,a.action,a.details,a.ip_address,u.username FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 100"); if($result) while($row=mysqli_fetch_assoc($result)) $logs[]=$row;}
$rootPath=".."; $activeNav="audit"; $pageTitle="Audit log"; $pageSubtitle="Recent authentication and administrator activity";
require_once "../includes/head.php"; require_once "../includes/sidebar.php"; require_once "../includes/topbar.php";
?>
<section class="panel"><div class="panel-head"><div><h2>Security activity</h2><p>Up to 100 most recent recorded events.</p></div><span class="pill pill-online">ADMIN ONLY</span></div><div class="table-scroll"><table class="data"><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead><tbody>
<?php foreach($logs as $log): ?><tr><td class="mono"><?=htmlspecialchars($log['created_at'])?></td><td><?=htmlspecialchars($log['username']?:'system')?></td><td><span class="pill pill-online"><?=htmlspecialchars($log['action'])?></span></td><td><?=htmlspecialchars($log['details']?:'—')?></td><td class="mono"><?=htmlspecialchars($log['ip_address']?:'—')?></td></tr><?php endforeach; ?>
<?php if(!$logs): ?><tr><td colspan="5" class="empty">Belum ada aktivitas yang tercatat.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require_once "../includes/footer.php"; ?>
