<?php
require 'config.php';
requireAdmin();

$title = 'Nadzorna kamera';
require 'includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">ADMIN · VIDEO ANALITIKA</p>
        <h1>Nadzorna kamera</h1>
        <p>Automatsko očitavanje tablica i procena brzine vozila na ulazu u parkiralište.</p>
    </div>
    <span class="live"><i></i> KAMERA 01</span>
</div>

<section class="traffic-layout">
    <div class="card traffic-screen">
        <div id="trafficState" class="traffic-message">
            <div class="radar"></div>
            <h2>Priprema nadzorne kamere…</h2>
            <p>Video će se automatski analizirati.</p>
        </div>
        <video id="trafficVideo" controls muted playsinline hidden></video>
    </div>

    <aside class="card traffic-settings">
        <h2>Podešavanje merenja</h2>
        <label>Rastojanje između linija (m)
            <input id="distance" type="number" min="1" step="0.5" value="10">
        </label>
        <label>Ograničenje brzine (km/h)
            <input id="limit" type="number" min="5" value="40">
        </label>
        <button id="analyzeAgain" class="btn primary wide">Ponovo analiziraj snimak</button>
        <small>Rezultat je demonstraciona procena. Tačnost zavisi od kalibracije kamere, poznatog rastojanja i FPS-a.</small>
    </aside>
</section>

<section class="card">
    <div class="traffic-summary">
        <h2>Detektovana vozila</h2>
        <span id="trafficCount" class="pill green">0 vozila</span>
    </div>
    <div class="table">
        <table>
            <thead>
                <tr>
                    <th>Vreme videa</th>
                    <th>Tip</th>
                    <th>Tablica</th>
                    <th>Brzina</th>
                    <th>Ograničenje</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="trafficRows">
                <tr><td colspan="6">Analiza još nije završena.</td></tr>
            </tbody>
        </table>
    </div>
</section>

<script>
window.trafficPage = { analyzeUrl: <?= json_encode(TRAFFIC_AI_URL) ?> };
</script>
<script src="assets/traffic.js"></script>
<?php require 'includes/footer.php'; ?>