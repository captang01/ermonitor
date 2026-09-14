<?php
require_once __DIR__ . '/auth/session.php';
if (isset($_SESSION["user_id"])) {
    header("Location: admin/dashboard.php");
} else {
    header("Location: login.php");
}
exit;
