<?php
require '../config.php'; requireAdmin();
$rows=$db->query('SELECT * FROM traffic_events ORDER BY id DESC LIMIT 100')->fetchAll();
jsonResponse(['ok'=>true,'events'=>$rows]);
