<?php
require '../config.php';

if (!currentUser()) {
    jsonResponse(['ok' => false], 401);
}

jsonResponse([
    'ok'     => true,
    'events' => $db->query('SELECT * FROM access_events ORDER BY id DESC LIMIT 8')->fetchAll(),
]);