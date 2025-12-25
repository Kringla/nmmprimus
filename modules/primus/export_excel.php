<?php
declare(strict_types=1);

/**
 * export_excel.php
 *
 * Admin-only: Export photos to Excel (SpreadsheetML format)
 * Filters by Serie and SerNr range, only exports Transferred = False
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

// Fetch data
$fotoListe = primus_hent_foto_for_export($serie, $serNrFra, $serNrTil);

if (empty($fotoListe)) {
    die('Ingen foto funnet som matcher kriteriene (Serie: ' . h($serie) . ', SerNr: ' . $serNrFra . '-' . $serNrTil . ', Transferred = False)');
}

// Store foto IDs in session for confirmation
$fotoIds = array_column($fotoListe, 'Foto_ID');
$_SESSION['export_foto_ids'] = $fotoIds;
$_SESSION['export_serie'] = $serie;
$_SESSION['export_sernr_fra'] = $serNrFra;
$_SESSION['export_sernr_til'] = $serNrTil;
$_SESSION['export_count'] = count($fotoListe);

// Generate filename with datetime
$filename = 'ExportToPrimus_' . date('Ymd_His') . '.csv';

// Set headers for CSV download (Excel will open CSV files)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

// Helper function to convert bit field to readable value
function bitToString($value): string {
    if ($value === null) return '';
    return !empty($value) ? 'Ja' : 'Nei';
}

// Output UTF-8 BOM for Excel to recognize UTF-8 encoding
echo "\xEF\xBB\xBF";

// Open output stream
$output = fopen('php://output', 'w');

// Header row
$headers = [
    'BildeId',
    'URL_Bane',
    'MotivBeskr',
    'MotivType',
    'MotivEmne',
    'MotivKriteria',
    'Svarthvitt',
    'Aksesjon',
    'Samling',
    'Fotografi',
    'FotoFirma',
    'Foto_Fra',
    'Foto_Til',
    'FotoSted',
    'Prosess',
    'Referansenr',
    'FotografsRefNr',
    'Plassering',
    'Status',
    'Tilstand',
    'FriKopi',
    'Fart_UUID',
    'Merknad'
];

fputcsv($output, $headers, ';');

// Data rows
foreach ($fotoListe as $row) {
    $dataRow = [
        $row['BildeId'] ?? '',
        $row['URL_Bane'] ?? '',
        $row['MotivBeskr'] ?? '',
        $row['MotivType'] ?? '',
        $row['MotivEmne'] ?? '',
        $row['MotivKriteria'] ?? '',
        $row['Svarthvitt'] ?? '',
        bitToString($row['Aksesjon']),
        $row['Samling'] ?? '',
        bitToString($row['Fotografi']),
        $row['FotoFirma'] ?? '',
        $row['Foto_Fra'] ?? '',
        $row['Foto_Til'] ?? '',
        $row['FotoSted'] ?? '',
        $row['Prosess'] ?? '',
        $row['Referansenr'] ?? '',
        $row['FotografsRefNr'] ?? '',
        $row['Plassering'] ?? '',
        $row['Status'] ?? '',
        $row['Tilstand'] ?? '',
        bitToString($row['FriKopi']),
        $row['Fart_UUID'] ?? '',
        $row['Merknad'] ?? ''
    ];
    fputcsv($output, $dataRow, ';');
}

fclose($output);
