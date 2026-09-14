<main class="workspace">
<header class="topbar">
    <button class="icon-btn menu-toggle" type="button" id="menuToggle" aria-label="Toggle menu" aria-expanded="false"><i data-lucide="menu"></i></button>
    <div class="topbar-title"><h1><?= htmlspecialchars($pageTitle ?? '') ?></h1><p><?= htmlspecialchars($pageSubtitle ?? '') ?></p></div>
    <div class="topbar-meta">
        <div class="clock-block"><span>Local time</span><strong id="liveClock">--:--:--</strong></div>
        <div class="clock-block"><span>Last sync</span><strong id="lastUpdate">—</strong></div>
        <button class="ghost-btn" type="button" id="refreshBtn" title="Refresh telemetry sekarang"><i data-lucide="refresh-cw"></i><span>Refresh</span></button>
    </div>
</header><section class="workspace-body">
