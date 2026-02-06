<?php
declare(strict_types=1);

/**
 * API – foto_state.php
 * Ren hendelses-/iCh-motor
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/foto_flyt.php';

// Krev innlogging
require_login();

header('Content-Type: application/json; charset=utf-8');

// --------------------------------------------------
// INPUT
// --------------------------------------------------
// Accept both fmeHendelse (old) and iCh (new) as input
$iCh = filter_input(INPUT_POST, 'iCh', FILTER_VALIDATE_INT);
if (!$iCh || $iCh < 1 || $iCh > 4) {
    // Fallback to fmeHendelse if iCh not provided
    $fme = filter_input(INPUT_POST, 'fmeHendelse', FILTER_VALIDATE_INT);
    if ($fme && $fme >= 1 && $fme <= 4) {
        $iCh = foto_hendelsesmodus_fra_fme($fme);
    } else {
        $iCh = 1;
    }
}

// --------------------------------------------------
// Felt-tilstand
// --------------------------------------------------
$felter   = foto_felt_tilstand($iCh);
$verdier  = foto_avledede_verdier($iCh);

// --------------------------------------------------
// Hendelse-tekst (1:1 Access, DB-basert)
// --------------------------------------------------

// Mapping iCh → Kode(r) (Access-ekvivalent)
$kodeMap = [
    1 => [],
    2 => [101],
    3 => [104],
    4 => [101, 104],
];

$verdier['Hendelse'] = '';

$koder = $kodeMap[$iCh] ?? [];

if ($koder) {
    $db = db();

    $placeholders = implode(',', array_fill(0, count($koder), '?'));
    $stmt = $db->prepare("
        SELECT Kode, Hendelsestype, ROWID
        FROM _zhendelsestyper
        WHERE Kode IN ($placeholders)
        ORDER BY Kode
    ");
    $stmt->execute($koder);

    $linjer = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Access-format: Kode,Hendelsestype,ROWID
        $linjer[] = $r['Kode'] . ',' . $r['Hendelsestype'] . ',' . $r['ROWID'];
    }

    // vbCrLf-ekvivalent i textarea/JS
    $verdier['Hendelse'] = implode("\n", $linjer);
}


// --------------------------------------------------
// Felter som skal tømmes
// --------------------------------------------------
$skalTommes = [];
foreach ($felter as $id => $aktiv) {
    if ($aktiv === false && $id !== 'Hendelse') {
        $skalTommes[] = $id;
    }
}

// --------------------------------------------------
// RESPONSE
// --------------------------------------------------
echo json_encode([
    'ok'   => true,
    'data' => [
        'iCh'        => $iCh,
        'felter'     => $felter,
        'skalTommes' => $skalTommes,
        'verdier'    => $verdier,
    ],
], JSON_UNESCAPED_UNICODE);
