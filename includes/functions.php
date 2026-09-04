<?php
declare(strict_types=1);

function initializeDatabase(PDO $db): void
{
    $sql = file_get_contents(__DIR__ . '/../database/schema.sql');
    $db->exec($sql);

    if ((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $stmt = $db->prepare('INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)');
        $stmt->execute(['Administrator', 'admin@parking.local', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
        $stmt->execute(['Mihajlo Đurađević', 'miki@parking.local', password_hash('miki123', PASSWORD_DEFAULT), 'user']);
        $uid = (int)$db->lastInsertId();
        $db->prepare('INSERT INTO vehicles(user_id,plate,make,color) VALUES(?,?,?,?)')->execute([$uid, 'KV123AB', 'Volkswagen Golf', 'Crna']);
        $vid = (int)$db->lastInsertId();
        $db->prepare("INSERT INTO passes(vehicle_id,plan,amount,status,starts_at,expires_at) VALUES(?,?,?,?,datetime('now'),datetime('now','+30 day'))")
            ->execute([$vid, 'Mesečni paket', 4000, 'active']);
    }

    // Svaki zahtev automatski zatvara sve parking pakete kojima je isteklo vreme.
    $db->exec("UPDATE passes SET status='expired' WHERE status='active' AND datetime(expires_at) < datetime('now')");
}

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function normalizePlate(string $plate): string {
    $plate = strtoupper(trim($plate));
    $plate = strtr($plate, ['Č'=>'C', 'Ć'=>'C', 'Š'=>'S', 'Ž'=>'Z', 'Đ'=>'D']);
    return (string)preg_replace('/[^A-Z0-9]/', '', $plate);
}
function currentUser(): ?array { return $_SESSION['user'] ?? null; }
function requireLogin(): void { if (!currentUser()) { header('Location: login.php'); exit; } }
function requireAdmin(): void { requireLogin(); if ((currentUser()['role'] ?? '') !== 'admin') { http_response_code(403); exit('Pristup odbijen.'); } }
function csrfToken(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function verifyCsrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Neispravan CSRF token.'); } }
function redirect(string $url): never { header('Location: ' . $url); exit; }
function jsonResponse(array $data, int $status = 200): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }

function activePassForPlate(PDO $db, string $plate): ?array
{
    $stmt = $db->prepare("SELECT p.*,v.plate,u.name FROM passes p JOIN vehicles v ON v.id=p.vehicle_id JOIN users u ON u.id=v.user_id WHERE v.plate=? AND p.status='active' AND datetime(p.starts_at)<=datetime('now') AND datetime(p.expires_at)>=datetime('now') ORDER BY p.id DESC LIMIT 1");
    $stmt->execute([normalizePlate($plate)]);
    $exact = $stmt->fetch();
    if ($exact) { $exact['_fuzzy'] = false; return $exact; }

    // Whitelist korekcija: dozvoljena je samo jedna razlika i samo jedan jedinstven kandidat.
    $active = $db->query("SELECT p.*,v.plate,u.name FROM passes p JOIN vehicles v ON v.id=p.vehicle_id JOIN users u ON u.id=v.user_id WHERE p.status='active' AND datetime(p.starts_at)<=datetime('now') AND datetime(p.expires_at)>=datetime('now') ORDER BY p.id DESC")->fetchAll();
    $recognized = normalizePlate($plate);
    $matches = array_values(array_filter($active, fn(array $row): bool =>
        strlen($row['plate']) === strlen($recognized) && levenshtein($row['plate'], $recognized) === 1
    ));
    if (count($matches) === 1) { $matches[0]['_fuzzy'] = true; return $matches[0]; }
    return null;
}
