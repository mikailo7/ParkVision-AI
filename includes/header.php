<?php $user = currentUser(); ?>
<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? APP_NAME) ?> · <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/traffic.css">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="index.php"><span>PV</span> ParkVision AI</a>
        <nav>
            <?php if ($user): ?>
                <a href="dashboard.php">Kontrolna tabla</a>
                <a href="scan.php">Ulazna kamera</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="traffic-camera.php">Nadzorna kamera</a>
                    <a href="admin.php">Administracija</a>
                <?php endif; ?>
                <a href="logout.php">Odjava</a>
            <?php else: ?>
                <a href="login.php">Prijava</a>
                <a class="nav-cta" href="register.php">Registracija</a>
            <?php endif; ?>
        </nav>
    </header>
    <main class="container">