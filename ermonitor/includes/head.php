<?php
$rootPath = $rootPath ?? "..";
$pageTitle = $pageTitle ?? "ERMonitor";
$activeNav = $activeNav ?? "dashboard";
$pageSubtitle = $pageSubtitle ?? "Infrastructure control room";
$userName = $_SESSION["name"] ?? "User";
$userRole = $_SESSION["role"] ?? "user";
$userRoleLabel = $userRole === "admin" ? "Administrator" : "User";
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#05070c">
    <meta name="description" content="ERMonitor infrastructure monitoring control room">
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($rootPath) ?>/assets/img/favicon.png">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($rootPath) ?>/assets/img/ermonitor-logo.png">
    <link rel="manifest" href="<?= htmlspecialchars($rootPath) ?>/manifest.webmanifest">
    <title><?= htmlspecialchars($pageTitle) ?> · ERMonitor</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($rootPath) ?>/assets/css/style.css">
</head>
<body data-api="<?= htmlspecialchars($rootPath) ?>/api/monitoring.php">
<div class="app-shell">
    <div class="bg-grid" aria-hidden="true"></div>
    <div class="bg-glow" aria-hidden="true"></div>
