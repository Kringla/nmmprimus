<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/nmmprimus/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/nmmprimus/includes/foto_flyt.php';

session_start();

header('Content-Type: application/json; charset=utf-8');

/* -------------------------------------------------------------
 * 1. Input
 * ------------------------------------------------------------- */

$nmmId = filter_input(INPUT_POST, 'NMM_ID', FILTER_VALIDATE_INT);

if (!$nmmId) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'melding' => 'Ugyldig kandidat.'
    ]);
    exit;
}

$db = db();

/* -------------------------------------------------------------
 * 2. Hent kandidatdata
 * ------------------------------------------------------------- */
/*
 * Denne spørringen speiler subformen frmNMMPrimusKand.
 * Kun felter som faktisk brukes i VBA-logikken tas med.
 */

$stmt = $db->prepare("
    SELECT
        NMM_ID,
        UUID,
        FTY,
        FNA,
        XNA,
        BYG,
        VER
    FROM nmm_skip
    WHERE NMM_ID = :nmmId
");
$stmt->execute(['nmmId' => $nmmId]);

$kandidat = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$kandidat) {
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'melding' => 'Kandidat ikke funnet.'
    ]);
    exit;
}

/* -------------------------------------------------------------
 * 3. Bygg kontekst (Access FNA_Click-logikk)
 * ------------------------------------------------------------- */

$motivBeskr = foto_bygg_motivbeskrivelse($kandidat);

/*
 * Sett aktiv arbeidskontekst
 * (erstatter Me.Parent!NMM_ID / SaveSetting)
 */
$_SESSION['aktiv_nmm_id'] = (int) $kandidat['NMM_ID'];
$_SESSION['aktiv_uuid']   = (string) $kandidat['UUID'];
$_SESSION['siste_fna']    = (string) $kandidat['FNA'];

/* -------------------------------------------------------------
 * 4. Returner state til UI
 * ------------------------------------------------------------- */

echo json_encode([
    'ok' => true,
    'data' => [
        'NMM_ID'     => (int) $kandidat['NMM_ID'],
        'UUID'       => (string) $kandidat['UUID'],
        'MotivBeskr' => $motivBeskr
    ]
]);
