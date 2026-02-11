<?php
declare(strict_types=1);

/**
 * API – kandidat_sok.php
 *
 * Søk etter fartøy i nmm_skip. Returnerer JSON-liste.
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../primus_modell.php';

header('Content-Type: application/json; charset=utf-8');
require_login();

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Kun POST']);
    exit;
}

$sok = trim((string)($_POST['sok'] ?? ''));
$kandidater = primus_hent_skip_liste($sok);

echo json_encode(['ok' => true, 'data' => $kandidater], JSON_UNESCAPED_UNICODE);
