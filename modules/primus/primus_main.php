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
$db = db();

// --------------------------------------------------
// Slett foto (Access-paritet) - NÅ VIA POST MED CSRF
// --------------------------------------------------
if (is_post() && ($_POST['action'] ?? '') === 'slett_foto') {
    if (!csrf_validate()) {
        die('Ugyldig forespørsel (CSRF).');
    }
    
    $slettFotoId = filter_var($_POST['foto_id'] ?? '', FILTER_VALIDATE_INT);
    if ($slettFotoId) {
        $stmt = $db->prepare("DELETE FROM nmmfoto WHERE Foto_ID = :id");
        $stmt->execute(['id' => $slettFotoId]);
    }
    redirect('primus_main.php');
}

// --------------------------------------------------
// Finn valgt serie
// --------------------------------------------------
$serieFraPost = post_string('serie');

if ($serieFraPost !== '') {
    $valgtSerie = $serieFraPost;
    primus_lagre_sist_valgte_serie($userId, $valgtSerie);
} else {
    $valgtSerie = primus_hent_sist_valgte_serie($userId);
}

if ($valgtSerie === null || $valgtSerie === '') {
    $valgtSerie = primus_hent_forste_serie();
}

// --------------------------------------------------
// NYTT FOTO (Access: cmdNytt_Click) -> H2 modus
// --------------------------------------------------
if (is_post() && ($_POST['action'] ?? '') === 'nytt_foto') {
    if (!csrf_validate()) {
        die('Ugyldig forespørsel (CSRF).');
    }
    
    $serie = (string)$valgtSerie;
    $serNr = primus_hent_neste_sernr($serie);

    // Bilde_Fil: serie + '-' + serienr (Access-paritet)
    $bildeFil = $serie . '-' . (int)$serNr;

    $stmt = $db->prepare("
        INSERT INTO nmmfoto (SerNr, Bilde_Fil, Transferred)
        VALUES (:sernr, :bilde_fil, b'0')
    ");
    $stmt->execute([
        'sernr'     => $serNr,
        'bilde_fil' => $bildeFil,
    ]);

    $nyFotoId = (int)$db->lastInsertId();

    // H2: venstre kandidatpanel skal være klikkbart
    $_SESSION['primus_h2'] = 1;

    redirect('primus_detalj.php?Foto_ID=' . $nyFotoId);
}

// --------------------------------------------------
// Data til visning
// --------------------------------------------------
$serier    = primus_hent_bildeserier();
$fotoListe = ($valgtSerie !== null && $valgtSerie !== '')
    ? primus_hent_foto_for_serie((string)$valgtSerie)
    : [];

// BASE_URL for JavaScript
$baseUrlJs = base_url_js();

$pageTitle = 'NMMPrimus – Landingsside';
require_once __DIR__ . '/../../includes/layout_start.php';
?>

<h1>NMMPrimus</h1>

<div class="primus-main-page">

<?php ui_card_start('Serie'); ?>
<div class="primus-serie-row">
    <form method="post" class="form-inline">
        <?= csrf_field(); ?>
        <div class="form-group primus-serie-field">
            <label for="serie">Serie</label>
            <select name="serie" id="serie" onchange="this.form.submit()">
                <?php foreach ($serier as $s): ?>
                    <?php
                    $serieVerdi = (string)($s['Serie'] ?? '');
                    if ($serieVerdi === '') continue;
                    $selected = ($serieVerdi === (string)$valgtSerie) ? 'selected' : '';
                    ?>
                    <option value="<?= h($serieVerdi); ?>" <?= $selected; ?>>
                        <?= h($serieVerdi); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <form method="post" class="form-inline">
        <?= csrf_field(); ?>
        <input type="hidden" name="action" value="nytt_foto">
        <button type="submit" class="btn btn-success">
            Nytt foto i valgt serie
        </button>
    </form>
</div>
<?php ui_card_end(); ?>

<?php ui_card_start('Foto i valgt serie'); ?>

<?php if (empty($fotoListe)): ?>

    <?php ui_empty('Ingen foto funnet for valgt serie.'); ?>

<?php else: ?>

    <div class="primus-main-scroll">
        <?php
        ui_table_start([
            'Bildefil <span style="font-size:0.85em; font-weight:normal; opacity:0.7;">(Dbl-click for details)</span>',
            'Motivbeskrivelse',
            'Overført',
            '' // slett
        ]);

        foreach ($fotoListe as $row) {
            $fotoId = (int)$row['Foto_ID'];

            echo '<tr class="row-clickable" data-foto-id="' . h((string)$fotoId) . '">';
            echo '<td>' . h((string)$row['Bilde_Fil']) . '</td>';
            echo '<td>' . h((string)($row['MotivBeskr'] ?? '')) . '</td>';
            echo '<td>' . (!empty($row['Transferred']) ? 'Ja' : 'Nei') . '</td>';

            // Slett-knapp som POST-skjema med CSRF
            echo '<td style="white-space:nowrap;">';
            echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'Slette dette bildet?\');">';
            echo csrf_field();
            echo '<input type="hidden" name="action" value="slett_foto">';
            echo '<input type="hidden" name="foto_id" value="' . $fotoId . '">';
            echo '<button type="submit" class="btn btn-sm btn-danger">Slett</button>';
            echo '</form>';
            echo '</td>';

            echo '</tr>';
        }

        ui_table_end();
        ?>
    </div>

<?php endif; ?>

<?php ui_card_end(); ?>
</div> <!-- /.primus-main-page -->

<style>
.primus-main-page .card-header {
    background: var(--blue-head);
}
.primus-serie-row {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}
.primus-serie-row .form-group {
    margin: 0;
}
.primus-serie-field {
    display: flex;
    align-items: center;
    gap: 6px;
}
.primus-serie-field label {
    margin: 0;
    white-space: nowrap;
}
/* PRIMUS MAIN - reduser radavstand og gi scrolling */
.primus-main-scroll {
    max-height: 620px;
    overflow-y: auto;
    border: 1px solid #dbe3ef;
    border-radius: 4px;
}
.primus-main-scroll table {
    width: 100%;
    border-collapse: collapse;
}
.primus-main-scroll table td,
.primus-main-scroll table th {
    padding: 0.25rem 0.4rem;
    line-height: 1.2;
}
.primus-main-scroll table th {
    background: #f7f9fc;
    position: sticky;
    top: 0;
    z-index: 1;
}
.primus-main-scroll table tr {
    border-bottom: 1px solid #3585feff;
}
.primus-main-scroll table tr:last-child {
    border-bottom: none;
}
.row-clickable {
    cursor: pointer;
}
.row-clickable:hover {
    background: #e8f4ff;
}
</style>

<script>
(function(){
    var baseUrl = <?= $baseUrlJs ?>;
    
    // Dobbeltklikk på eksisterende foto -> H1 modus (kandidatpanel ikke-klikkbart)
    document.querySelectorAll('.row-clickable').forEach(function (row) {
        row.addEventListener('dblclick', function () {
            var fotoId = this.dataset.fotoId;
            if (!fotoId) return;

            fetch(baseUrl + '/modules/primus/api/sett_session.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'primus_h2=0'
            }).catch(function(){});

            window.location.href = 'primus_detalj.php?Foto_ID=' + fotoId;
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/layout_slutt.php'; ?>
