<?php
require __DIR__ . '/../config.php';

function check(bool $condition, string $message): void {
    if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
    echo "OK: $message\n";
}

check(normalizePlate('kv-123-ab') === 'KV123AB', 'normalizacija tablice');
check(activePassForPlate($db, 'KV-123-AB') !== null, 'aktivna demo dozvola');
check(activePassForPlate($db, 'BG-999-ZZ') === null, 'nepoznata tablica je odbijena');
check((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() >= 2, 'inicijalni korisnici postoje');
echo "Svi smoke testovi su prošli.\n";

