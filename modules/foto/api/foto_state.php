<?php
declare(strict_types=1);

/**
 * API – foto_state.php
 * Ren hendelses-/iCh-motor
 * Ingen databaseavhengighet
 */

require_once __DIR__ . '/../../../includes/foto_flyt.php';

header('Content-Type: application/json; charset=utf-8');

// --------------------------------------------------
// INPUT
// --------------------------------------------------
$fme = filter_input(INPUT_POST, 'fmeHendelse', FILTER_VALIDATE_INT);
if (!$fme || $fme < 1 || $fme > 6) {
    $fme = 1;
}

// --------------------------------------------------
// iCh (1:1 fra Access)
// --------------------------------------------------
$iCh = foto_hendelsesmodus_fra_fme($fme);

// --------------------------------------------------
// Felt-tilstand
// --------------------------------------------------
$felter   = foto_felt_tilstand($iCh);
$verdier  = foto_avledede_verdier($iCh);

// --------------------------------------------------
// Hendelse-tekst (kodebasert, midlertidig)
// --------------------------------------------------
$hendelseMap = [
    1 => '',
    2 => "101,Fotografi",
    3 => "104,Samling",
    4 => "101,Fotografi\n104,Samling",
    5 => "105,Annet",
    6 => "101,Fotografi\n104,Samling\n105,Annet",
];

$verdier['Hendelse'] = $hendelseMap[$iCh] ?? '';

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
]);
