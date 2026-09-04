<?php
declare(strict_types=1);

const APP_NAME = 'ParkVision AI';
const AI_SERVICE_URL = 'http://127.0.0.1:5001/recognize';
const TRAFFIC_AI_URL = 'http://127.0.0.1:5001/traffic/analyze';
const PARKING_CAPACITY = 20;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$dataDir = __DIR__ . '/data';
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($dataDir)) mkdir($dataDir, 0775, true);
if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

$db = new PDO('sqlite:' . $dataDir . '/parking.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->exec('PRAGMA foreign_keys = ON');

require_once __DIR__ . '/includes/functions.php';
initializeDatabase($db);
