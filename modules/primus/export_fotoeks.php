<?php
declare(strict_types=1);

/**
 * export_fotoeks.php
 *
 * Admin-only: Export Fotoeksemplar data to CSV.
 * Filters by Serie and SerNr range, only exports Transferred = True.
 * Does NOT update Transferred after export.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/primus_modell.php';

// Only admins can export
require_admin();

if (!is_post()) {
    die('Ugyldig forespørsel');
}

if (!csrf_validate()) {
    die('Ugyldig forespørsel (CSRF)');
}

// Get parameters
$serie = post_string('serie');
$serNrFra = filter_input(INPUT_POST, 'sernr_fra', FILTER_VALIDATE_INT);
$serNrTil = filter_input(INPUT_POST, 'sernr_til', FILTER_VALIDATE_INT);

if (!$serie || !$serNrFra || !$serNrTil) {
    die('Mangler serie eller SerNr-verdier');
}

// Validering: SerNr må være mellom 1 og 999
if ($serNrFra < 1 || $serNrFra > 999) {
    die('FEIL: SerNr fra må være mellom 1 og 999');
}
if ($serNrTil < 1 || $serNrTil > 999) {
    die('FEIL: SerNr til må være mellom 1 og 999');
}

if ($serNrFra > $serNrTil) {
    die('SerNr fra må være mindre enn eller lik SerNr til');
}

// Check 1000 record limit
$recordCount = $serNrTil - $serNrFra + 1;
if ($recordCount > 1000) {
    die('Du kan ikke eksportere mer enn 1000 poster om gangen. Valgt område: ' . $recordCount . ' poster.');
}

// Fetch data (only Transferred = 1)
$fotoListe = primus_hent_foto_for_fotoeks_export($serie, $serNrFra, $serNrTil);

if (empty($fotoListe)) {
    die('Ingen foto funnet som matcher kriteriene (Serie: ' . h($serie) . ', SerNr: ' . $serNrFra . '-' . $serNrTil . ', Transferred = Ja)');
}

// Generate filename with datetime
$filename = 'NMMPrimus_Fotoeks_' . $serie . '_' . date('Ymd_His') . '.csv';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

// Output UTF-8 BOM for Excel to recognize UTF-8 encoding
echo "\xEF\xBB\xBF";

// Open output stream
$output = fopen('php://output', 'w');

// Header row
$headers = [
    'BildeId',
    'ParentID',
    'Objekttype',
    'Plassering',
    'PlassFriTekst',
    'Antall',
];

fputcsv($output, $headers, ';');

// Data rows
foreach ($fotoListe as $row) {
    $dataRow = [
        $row['BildeId'] ?? '',
        $row['ParentID'] ?? '',
        $row['Objekttype'] ?? '',
        $row['Plassering'] ?? '',
        $row['PlassFriTekst'] ?? '',
        $row['Antall'] ?? '',
    ];
    fputcsv($output, $dataRow, ';');
}

fclose($output);
