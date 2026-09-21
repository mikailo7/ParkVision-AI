<?php
require '../config.php';

if (!currentUser()) {
    jsonResponse(['ok' => false, 'error' => 'Niste prijavljeni.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$plate          = normalizePlate($_POST['manual_plate'] ?? '');
$confidence     = null;
$yoloConfidence = null;
$annotatedImage = null;
$imagePath      = null;
$aiUsed         = false;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);

    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        jsonResponse(['ok' => false, 'error' => 'Dozvoljeni su JPG, PNG i WEBP.'], 422);
    }

    $name = bin2hex(random_bytes(8)) . '.' . [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ][$mime];

    $dest = '../uploads/' . $name;
    move_uploaded_file($_FILES['image']['tmp_name'], $dest);
    $imagePath = 'uploads/' . $name;

    if (function_exists('curl_init')) {
        $ch   = curl_init(AI_SERVICE_URL);
        $post = ['image' => new CURLFile(realpath($dest), $mime, $name)];

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
        ]);

        $raw    = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 200 && $raw) {
            $ai = json_decode($raw, true);

            if (!empty($ai['plate'])) {
                $plate          = normalizePlate($ai['plate']);
                $confidence     = (float)($ai['ocr_confidence'] ?? 0);
                $yoloConfidence = (float)($ai['yolo_confidence'] ?? 0);
                $annotatedImage = $ai['annotated_image'] ?? null;
                $aiUsed         = true;
            }
        }
    }
}

if (!$plate) {
    jsonResponse(['ok' => false, 'error' => 'AI nije uspeo da očita tablicu. Unesite je u rezervno polje i pokušajte ponovo.'], 422);
}

$recognizedPlate = $plate;
$pass            = activePassForPlate($db, $plate);
$decision        = $pass ? 'GRANTED' : 'DENIED';

if ($pass && !empty($pass['_fuzzy'])) {
    $plate  = $pass['plate'];
    $reason = 'Aktivna dozvola; OCR oznaka ' . $recognizedPlate . ' usklađena sa ' . $plate;
} else {
    $reason = $pass ? 'Aktivna parking dozvola: ' . $pass['plan'] : 'Vozilo nema aktivnu uplatu/dozvolu';
}

$db->prepare('INSERT INTO access_events(plate,confidence,decision,reason,image_path) VALUES(?,?,?,?,?)')
   ->execute([$plate, $confidence, $decision, $reason, $imagePath]);

jsonResponse([
    'ok'               => true,
    'plate'            => $plate,
    'recognized_plate' => $recognizedPlate,
    'confidence'       => $confidence,
    'yolo_confidence'  => $yoloConfidence,
    'annotated_image'  => $annotatedImage,
    'ai_used'          => $aiUsed,
    'decision'         => $decision,
    'reason'           => $reason,
    'owner'            => $pass['name'] ?? null,
]);