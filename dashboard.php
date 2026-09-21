<?php
require 'config.php';
requireLogin();

$uid   = (int)currentUser()['id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'vehicle') {
        try {
            $p = normalizePlate($_POST['plate'] ?? '');
            if (strlen($p) < 5) throw new RuntimeException('Tablica nije ispravna.');

            $db->prepare('INSERT INTO vehicles(user_id,plate,make,color) VALUES(?,?,?,?)')
               ->execute([$uid, $p, trim($_POST['make'] ?? ''), trim($_POST['color'] ?? '')]);
        } catch (Throwable $x) {
            $error = $x->getMessage() === 'Tablica nije ispravna.'
                ? $x->getMessage()
                : 'Tablica već postoji u sistemu.';
        }
    }

    if ($action === 'pay') {
        $vid   = (int)($_POST['vehicle_id'] ?? 0);
        $check = $db->prepare('SELECT id FROM vehicles WHERE id=? AND user_id=?');
        $check->execute([$vid, $uid]);

        if ($check->fetch()) {
            $plan  = $_POST['plan'] ?? 'daily';
            $plans = [
                'hour'    => ['1 sat', 100, '+1 hour'],
                'daily'   => ['24 sata', 500, '+1 day'],
                'monthly' => ['Mesečni paket', 4000, '+30 day'],
            ];
            [$n, $a, $d] = $plans[$plan] ?? $plans['daily'];

            $db->prepare("
                INSERT INTO passes(vehicle_id, plan, amount, status, starts_at, expires_at)
                VALUES (?, ?, ?, 'active', datetime('now'), datetime('now', ?))
            ")->execute([$vid, $n, $a, $d]);
        }
    }
}

$s = $db->prepare("
    SELECT v.*, p.plan, p.expires_at,
           CASE WHEN p.status = 'active' AND datetime(p.expires_at) >= datetime('now')
                THEN 1 ELSE 0 END active
    FROM vehicles v
    LEFT JOIN passes p ON p.id = (
        SELECT id FROM passes WHERE vehicle_id = v.id ORDER BY id DESC LIMIT 1
    )
    WHERE v.user_id = ?
    ORDER BY v.id DESC
");
$s->execute([$uid]);
$vehicles = $s->fetchAll();

$title = 'Kontrolna tabla';
require 'includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">KORISNIČKI PORTAL</p>
        <h1>Zdravo, <?= e(currentUser()['name']) ?></h1>
    </div>
    <a class="btn primary" href="scan.php">Testiraj ulazak</a>
</div>

<?php if ($error): ?>
    <div class="alert bad"><?= e($error) ?></div>
<?php endif; ?>

<div class="grid two">
    <section class="card">
        <h2>Moja vozila</h2>

        <?php if (!$vehicles): ?>
            <p>Još niste dodali vozilo.</p>
        <?php endif; ?>

        <?php foreach ($vehicles as $v): ?>
            <div class="vehicle">
                <div>
                    <strong><?= e($v['plate']) ?></strong>
                    <small><?= e(trim(($v['make'] ?? '') . ' · ' . ($v['color'] ?? ''), ' ·')) ?></small>
                </div>
                <span class="pill <?= $v['active'] ? 'green' : 'red' ?>">
                    <?= $v['active'] ? 'AKTIVNO' : 'NEAKTIVNO' ?>
                </span>
            </div>

            <?php if (!$v['active']): ?>
                <form class="inline" method="post">
                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="pay">
                    <input type="hidden" name="vehicle_id" value="<?= $v['id'] ?>">
                    <select name="plan">
                        <option value="hour">1 sat — 100 RSD</option>
                        <option value="daily">24 sata — 500 RSD</option>
                        <option value="monthly">30 dana — 4.000 RSD</option>
                    </select>
                    <button class="btn small">Simuliraj uplatu</button>
                </form>
            <?php else: ?>
                <small>Važi do <?= e($v['expires_at']) ?></small>
            <?php endif; ?>
        <?php endforeach; ?>
    </section>

    <section class="card">
        <h2>Dodaj vozilo</h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="vehicle">
            <label>Registarska tablica
                <input name="plate" placeholder="KV-123-AB" required>
            </label>
            <label>Marka/model
                <input name="make" placeholder="Volkswagen Golf">
            </label>
            <label>Boja
                <input name="color" placeholder="Crna">
            </label>
            <button class="btn primary">Sačuvaj vozilo</button>
        </form>
    </section>
</div>

<?php require 'includes/footer.php'; ?>