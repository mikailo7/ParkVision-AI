<?php
require 'config.php';
if (currentUser()) redirect('dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $error = 'Unesite ispravne podatke; lozinka mora imati najmanje 6 znakova.';
    } else {
        try {
            $s = $db->prepare('INSERT INTO users(name,email,password) VALUES(?,?,?)');
            $s->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);

            $_SESSION['user'] = [
                'id'    => (int)$db->lastInsertId(),
                'name'  => $name,
                'email' => $email,
                'role'  => 'user',
            ];

            redirect('dashboard.php');
        } catch (PDOException) {
            $error = 'Ova e-mail adresa je već registrovana.';
        }
    }
}

$title = 'Registracija';
require 'includes/header.php';
?>

<section class="auth card">
    <p class="eyebrow">NOVI NALOG</p>
    <h1>Registracija</h1>

    <?php if ($error): ?>
        <div class="alert bad"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
        <label>Ime i prezime
            <input name="name" required>
        </label>
        <label>E-mail
            <input type="email" name="email" required>
        </label>
        <label>Lozinka
            <input type="password" name="password" minlength="6" required>
        </label>
        <button class="btn primary">Napravi nalog</button>
    </form>
</section>

<?php require 'includes/footer.php'; ?>