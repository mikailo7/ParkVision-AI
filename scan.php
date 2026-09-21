<?php
require 'config.php';
requireLogin();

$events = $db->query('SELECT * FROM access_events ORDER BY id DESC LIMIT 8')->fetchAll();
$title  = 'Ulazna kamera';
require 'includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">PARKING ULAZ · UŽIVO</p>
        <h1>AI kontrola pristupa</h1>
    </div>
    <span class="live"><i></i> SISTEM ONLINE</span>
</div>

<div class="grid scan-grid">
    <section class="card">
        <div class="dropzone" id="dropzone">
            <input id="image" type="file" accept="image/*" capture="environment">
            <div id="preview">
                <b>Dodaj fotografiju automobila</b>
                <span>klikni ili prevuci JPG/PNG fotografiju sa vidljivom tablicom</span>
            </div>
        </div>
        <label>Rezervna ručna tablica <small>(za demonstraciju bez AI servisa)</small>
            <input id="manualPlate" placeholder="KV-123-AB">
        </label>
        <button id="scanBtn" class="btn primary wide">Prepoznaj i proveri pristup</button>
    </section>

    <section class="card result-card" id="result">
        <div class="radar"></div>
        <p>Rezultat skeniranja će se pojaviti ovde.</p>
    </section>
</div>

<section class="card">
    <h2>Poslednji događaji</h2>
    <div class="table">
        <table>
            <thead>
                <tr>
                    <th>Vreme</th>
                    <th>Tablica</th>
                    <th>Pouzdanost</th>
                    <th>Odluka</th>
                    <th>Razlog</th>
                </tr>
            </thead>
            <tbody id="events">
                <?php foreach ($events as $x): ?>
                    <tr>
                        <td><?= e($x['created_at']) ?></td>
                        <td><b><?= e($x['plate']) ?></b></td>
                        <td><?= $x['confidence'] !== null ? number_format((float)$x['confidence'] * 100, 1) . '%' : '—' ?></td>
                        <td><span class="pill <?= $x['decision'] === 'GRANTED' ? 'green' : 'red' ?>"><?= e($x['decision']) ?></span></td>
                        <td><?= e($x['reason']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script>window.scanPage = true;</script>
<?php require 'includes/footer.php'; ?>