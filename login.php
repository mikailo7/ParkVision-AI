<?php
require 'config.php';
if (currentUser()) redirect('dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $s = $db->prepare('SELECT * FROM users WHERE email=?');
    $s->execute([strtolower(trim($_POST['email'] ?? ''))]);
    $u = $s->fetch();

    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        unset($u['password']);
        $_SESSION['user'] = $u;
        redirect('dashboard.php');
    }

    $error = 'Pogrešan e-mail ili lozinka.';
}

$title = 'Prijava';
require 'includes/header.php';
?>

<section class="auth card">
    <p class="eyebrow">DOBRODOŠLI</p>
    <h1>Prijava</h1>

    <?php if ($error): ?>
        <div class="alert bad"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
        <label>E-mail
            <input type="email" name="email" value="miki@parking.local" required>
        </label>
        <label>Lozinka
            <input type="password" name="password" value="miki123" required>
        </label>
        <button class="btn primary">Prijavi se</button>
    </form>

    <small>Demo admin: admin@parking.local / admin123</small>
</section>

<?php require 'includes/footer.php'; ?>