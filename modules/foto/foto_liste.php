<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/ui.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/foto_modell.php';

require_login();

$db = db();

// Antall rader (kan justeres senere / via GET)
$limit = 100;

$fotoListe = foto_hent_liste($db, $limit);

$pageTitle = 'Foto – oversikt';
require_once __DIR__ . '/../../includes/layout_start.php';

ui_card_start('Foto – oversikt');

if (empty($fotoListe)) {

    ui_empty('Ingen foto funnet.');

} else {

    ui_table_start([
        'Foto-ID',
        'NMM-ID',
        'Bildefil',
        'Motivbeskrivelse',
        'Fotograf',
        'Fototid (fra)',
        'Status',
    ]);

    foreach ($fotoListe as $row) {
        echo '<tr>';

        echo '<td>' . h((string)$row['Foto_ID']) . '</td>';
        echo '<td>' . h((string)$row['NMM_ID']) . '</td>';
        echo '<td>' . h((string)$row['Bilde_Fil']) . '</td>';
        echo '<td>' . h((string)$row['MotivBeskr']) . '</td>';
        echo '<td>' . h((string)$row['Fotograf']) . '</td>';
        echo '<td>' . h((string)$row['FotoTidFra']) . '</td>';
        echo '<td>' . h((string)$row['Status']) . '</td>';

        echo '</tr>';
    }

    ui_table_end();
}

ui_card_end();

require_once __DIR__ . '/../../includes/layout_slutt.php';
