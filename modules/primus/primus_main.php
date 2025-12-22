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
// Slett foto (Access-paritet)
// --------------------------------------------------
$slettFotoId = filter_input(INPUT_GET, 'slett_foto', FILTER_VALIDATE_INT);
if ($slettFotoId) {
    $db = db();
    $stmt = $db->prepare("DELETE FROM nmmfoto WHERE Foto_ID = :id");
    $stmt->execute(['id' => $slettFotoId]);
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
// NYTT FOTO (Access: cmdNytt_Click)  -> H2 modus
// --------------------------------------------------
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'nytt_foto'
) {
    $db = db();

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

$pageTitle = 'NMMPrimus – Landingsside';
require_once __DIR__ . '/../../includes/layout_start.php';
?>

<h1>NMMPrimus</h1>

<?php ui_card_start('Serie'); ?>
<form method="post">
    <div class="form-group">
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
<?php ui_card_end(); ?>

<?php ui_card_start('Foto i valgt serie'); ?>

<form method="post" class="mb-3">
    <input type="hidden" name="action" value="nytt_foto">
    <button type="submit" class="btn btn-success">
        Nytt foto i valgt serie
    </button>
</form>

<?php if (empty($fotoListe)): ?>

    <?php ui_empty('Ingen foto funnet for valgt serie.'); ?>

<?php else: ?>

    <div class="primus-main-scroll">
        <style>
        /* PRIMUS MAIN – reduser radavstand i fotoliste */
        .primus-main-scroll table td,
        .primus-main-scroll table th {
            padding: 0.25rem 0.4rem;
            line-height: 1.2;
        }
        </style>

        <?php
        
        ui_table_start([
            'Bildefil',
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

            echo '<td style="white-space:nowrap;">';
            echo '<a class="btn btn-sm btn-danger" ';
            echo 'href="primus_main.php?slett_foto=' . $fotoId . '" ';
            echo 'onclick="return confirm(\'Slette dette bildet?\')">Slett</a>';
            echo '</td>';

            echo '</tr>';
        }

        ui_table_end();
        ?>
    </div>

<?php endif; ?>

<?php ui_card_end(); ?>

<script>
// Dobbeltklikk på eksisterende foto -> H1 modus (kandidatpanel ikke-klikkbart)
document.querySelectorAll('.row-clickable').forEach(function (row) {
    row.addEventListener('dblclick', function () {
        const fotoId = this.dataset.fotoId;
        if (!fotoId) return;

        fetch('/nmmprimus/modules/primus/api/sett_session.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'primus_h2=0'
        }).catch(() => {});

        window.location.href = 'primus_detalj.php?Foto_ID=' + fotoId;
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/layout_slutt.php'; ?>
