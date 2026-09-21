<?php
require '../config.php'; requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['ok'=>false,'error'=>'Metod nije dozvoljen'],405);
$data=json_decode(file_get_contents('php://input'),true);
if (!is_array($data)||!is_array($data['events']??null)) jsonResponse(['ok'=>false,'error'=>'Neispravni podaci'],422);
$stmt=$db->prepare('INSERT INTO traffic_events(plate,vehicle_type,speed_kmh,speed_limit,status,video_time) VALUES(?,?,?,?,?,?)');
$db->beginTransaction();
foreach($data['events'] as $event) $stmt->execute([normalizePlate((string)($event['plate']??'')),(string)($event['vehicle_type']??'Vozilo'),(float)($event['speed_kmh']??0),(int)($event['speed_limit']??0),(string)($event['status']??''),(float)($event['video_time']??0)]);
$db->commit(); jsonResponse(['ok'=>true,'saved'=>count($data['events'])]);
