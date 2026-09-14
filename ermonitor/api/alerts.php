<?php
require_once __DIR__ . '/../auth/check.php'; require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
$active=[]; $r=mysqli_query($conn,"SELECT id,name,ip_address,os,status,description,updated_at FROM servers WHERE status IN ('warning','offline') ORDER BY FIELD(status,'offline','warning'),id DESC"); if($r) while($row=mysqli_fetch_assoc($r)) $active[]=$row;
$events=[]; $r=mysqli_query($conn,"SELECT server_name,previous_status,status,event_type,message,created_at FROM alert_events ORDER BY id DESC LIMIT 50"); if($r) while($row=mysqli_fetch_assoc($r)) $events[]=$row;
echo json_encode(['success'=>true,'active'=>$active,'events'=>$events],JSON_PRETTY_PRINT);
