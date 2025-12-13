<?php
declare(strict_types=1);

/**
 * primus_main.php
 *
 * Landingsside for NMMPrimus.
 * Tilsvarer Access-form: frmNMMPrimusMain.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/ui.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/primus_modell.php';

require_login();

$user = current_user();
$userId = (int)$user['user_id'];

// --------------------------------------------------
// Finn valgt serie
// --------------------------------------------------

$serieFraPost = post_string('serie');

if ($serieFraPost !== '') {
    // Bruker har valgt ny serie
    $valgtSerie = $serieFraPost;
    primus_lagre_sist_valgte_serie($userId, $valgtSerie);
} else {
    // Hent sist valgte serie for bruker
    $valgtSerie = primus_hent_sist_valgte_serie($userId);
}

// Fallback: første serie i databasen
if ($valgtSerie === null || $valgtSerie === '') {
    $valgtSerie = primus_hent_forste_serie();
}

// --------------------------------------------------
// Data til visning
// --------------------------------------------------

$serier    = primus_hent_bildeserier();
$fotoListe = $valgtSerie !== null
    ? primus_hent_foto_for_serie($valgtSerie)
    : [];

$pageTitle = 'NMMPrimus – Landingsside';
require_once __DIR__ . '/../../includes/layout_start.php';
?>

<h1>NMMPrimus</h1>

<?php
ui_card_start('Serie');

// --------------------------------------------------
// Serie-combobox
// --------------------------------------------------
?>
<form method="post">

    <div class="form-group">
        <label for="serie">Serie</label>
        <select name="serie" id="serie" onchange="this.form.submit()">
            <?php foreach ($serier as $s): ?>
                <?php
                $serieVerdi = (string)$s['Serie'];
                $selected = ($serieVerdi === $valgtSerie) ? 'selected' : '';
                ?>
                <option value="<?= h($serieVerdi); ?>" <?= $selected; ?>>
                    <?= h($serieVerdi); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

</form>

<?php
ui_card_end();

// --------------------------------------------------
// Fototabell
// --------------------------------------------------

ui_card_start('Foto i valgt serie');

if (empty($fotoListe)) {

    ui_empty('Ingen foto funnet for valgt serie.');

} else {

    ui_table_start([
        'Bildefil',
        'Motivbeskrivelse',
        'Overført'
    ]);

    foreach ($fotoListe as $row) {

        echo '<tr class="row-clickable" data-foto-id="' . h((string)$row['Foto_ID']) . '">';

        echo '<td>' . h((string)$row['Bilde_Fil']) . '</td>';
        echo '<td>' . h((string)$row['MotivBeskr']) . '</td>';
        echo '<td>' . ( $row['Transferred'] ? 'Ja' : 'Nei' ) . '</td>';

        echo '</tr>';
    }

    ui_table_end();
}

ui_card_end();
?>

<script>
// Forberedt for detaljside (Trinn 5)
document.querySelectorAll('.row-clickable').forEach(function (row) {
    row.addEventListener('dblclick', function () {
        const fotoId = this.dataset.fotoId;
        if (fotoId) {
            // Midlertidig – detaljside kommer i neste trinn
            window.location.href = 'primus_detalj.php?Foto_ID=' + fotoId;
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/layout_slutt.php'; ?>
