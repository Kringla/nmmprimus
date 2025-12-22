<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../primus_modell.php';

require_login();

header('Content-Type: application/json; charset=utf-8');

$nmmId = filter_input(INPUT_POST, 'NMM_ID', FILTER_VALIDATE_INT);
if (!$nmmId) {
    echo json_encode(['ok' => false, 'error' => 'Mangler/ugyldig NMM_ID']);
    exit;
}

$res = primus_hent_kandidat_felter((int)$nmmId);

echo json_encode([
    'ok' => (bool)$res['ok'],
    'data' => $res
], JSON_UNESCAPED_UNICODE);
