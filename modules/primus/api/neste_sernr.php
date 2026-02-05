<?php
declare(strict_types=1);

// VIKTIG: Denne API-filen er sannsynligvis ubrukt (ingen referanser funnet)
// Men oppdatert til ny bruker-basert logikk for konsistens

session_start();
require_once __DIR__ . '/../primus_modell.php';
header('Content-Type:application/json');

$serie = $_POST['serie'] ?? '';
if ($serie === '') {
    echo json_encode(['ok' => false, 'error' => 'Ingen serie angitt']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId === 0) {
    echo json_encode(['ok' => false, 'error' => 'Ikke innlogget']);
    exit;
}

$serNr = primus_hent_neste_sernr_for_bruker($userId, $serie);

echo json_encode([
    'ok' => true,
    'sernr' => $serNr
]);
