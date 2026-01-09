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
$isAdmin = ($user['role'] === 'admin');
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

    // Validering: SerNr må være mellom 1 og 999
    if ($serNr < 1 || $serNr > 999) {
        die('FEIL: SerNr må være mellom 1 og 999. Neste tilgjengelige SerNr (' . $serNr . ') er utenfor tillatt område.');
    }

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
    // Nye rader skal starte i 'Ingen' hendelsesmodus
    $_SESSION['primus_iCh'] = 1;

    redirect('primus_detalj.php?Foto_ID=' . $nyFotoId);
}

// --------------------------------------------------
// Paging
// --------------------------------------------------
$side = filter_input(INPUT_GET, 'side', FILTER_VALIDATE_INT) ?: 1;
if ($side < 1) $side = 1;

$perSide = 20;
$offset = ($side - 1) * $perSide;

// --------------------------------------------------
// Data til visning
// --------------------------------------------------
$serier    = primus_hent_bildeserier();
$fotoListe = [];
$totaltAntall = 0;

if ($valgtSerie !== null && $valgtSerie !== '') {
    $fotoListe = primus_hent_foto_for_serie((string)$valgtSerie, $perSide, $offset);
    $totaltAntall = primus_hent_totalt_antall_foto((string)$valgtSerie);
}

$totaltSider = $totaltAntall > 0 ? (int)ceil($totaltAntall / $perSide) : 0;

// BASE_URL for JavaScript
$baseUrlJs = base_url_js();

$pageTitle = 'NMMPrimus – Landingsside';
require_once __DIR__ . '/../../includes/layout_start.php';
?>

<h1>Foto hittil koblet til KulturNav</h1>

<div class="primus-main-page">

<!-- Serie toolbar (kompakt, uten card-wrapper) -->
<div class="primus-serie-toolbar">
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

    <?php if ($isAdmin): ?>
    <button type="button" class="btn btn-primary" onclick="showExportDialog()">
        Eksporter til Excel
    </button>
    <?php endif; ?>
</div>

<!-- Foto liste header med paging -->
<div class="primus-foto-header">
    <span class="primus-foto-title">Foto i valgt serie</span>

    <?php if ($totaltSider > 1): ?>
        <div class="primus-paging-inline">
            <!-- Første side -->
            <?php if ($side > 1): ?>
                <a href="?side=1" class="btn btn-secondary btn-sm" title="Gå til første side">«« Første</a>
            <?php else: ?>
                <span class="btn btn-secondary btn-sm btn-disabled">«« Første</span>
            <?php endif; ?>

            <!-- Forrige side -->
            <?php if ($side > 1): ?>
                <a href="?side=<?= $side - 1 ?>" class="btn btn-secondary btn-sm" title="Forrige side">« Forrige</a>
            <?php else: ?>
                <span class="btn btn-secondary btn-sm btn-disabled">« Forrige</span>
            <?php endif; ?>

            <!-- Side info og hopp til side -->
            <span class="primus-paging-info">
                Side <?= $side ?> av <?= $totaltSider ?>
            </span>

            <form method="get" class="primus-goto-page-form" onsubmit="return validateGotoPage();">
                <label for="goto_side" class="sr-only">Gå til side</label>
                <input
                    type="number"
                    name="side"
                    id="goto_side"
                    min="1"
                    max="<?= $totaltSider ?>"
                    placeholder="Gå til side..."
                    class="primus-goto-input"
                    title="Skriv sidenummer og trykk Enter"
                >
            </form>

            <!-- Neste side -->
            <?php if ($side < $totaltSider): ?>
                <a href="?side=<?= $side + 1 ?>" class="btn btn-secondary btn-sm" title="Neste side">Neste »</a>
            <?php else: ?>
                <span class="btn btn-secondary btn-sm btn-disabled">Neste »</span>
            <?php endif; ?>

            <!-- Siste side -->
            <?php if ($side < $totaltSider): ?>
                <a href="?side=<?= $totaltSider ?>" class="btn btn-secondary btn-sm" title="Gå til siste side">Siste »»</a>
            <?php else: ?>
                <span class="btn btn-secondary btn-sm btn-disabled">Siste »»</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (empty($fotoListe)): ?>

    <?php ui_empty('Ingen foto funnet for valgt serie.'); ?>

<?php else: ?>

    <div class="primus-main-scroll">
        <?php
        ui_table_start([
            'Bildefil <span class="text-small-muted">(Dbl-click for details)</span>',
            'Motivbeskrivelse',
            $isAdmin ? 'Overført <span class="text-small-muted">(klikk for å endre)</span>' : 'Overført',
            '' // slett
        ]);

        foreach ($fotoListe as $row) {
            $fotoId = (int)$row['Foto_ID'];
            $transferred = !empty($row['Transferred']);

            echo '<tr class="row-clickable" data-foto-id="' . h((string)$fotoId) . '">';
            echo '<td>' . h((string)$row['Bilde_Fil']) . '</td>';
            echo '<td>' . h((string)($row['MotivBeskr'] ?? '')) . '</td>';

            // Overført column - checkbox for admin, text for regular users
            echo '<td class="transferred-cell">';
            if ($isAdmin) {
                echo '<input type="checkbox" class="transferred-checkbox" data-foto-id="' . $fotoId . '" ' . ($transferred ? 'checked' : '') . '>';
            } else {
                echo $transferred ? 'Ja' : 'Nei';
            }
            echo '</td>';

            // Slett-knapp som POST-skjema med CSRF
            echo '<td class="nowrap">';
            echo '<form method="post" class="inline-form" onsubmit="return confirm(\'Slette dette bildet?\');">';
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
</div> <!-- /.primus-main-page -->

<?php if ($isAdmin): ?>
<!-- Export Dialog Modal -->
<div id="exportDialog" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Eksporter til Excel</h2>
            <span class="close" onclick="closeExportDialog()">&times;</span>
        </div>
        <form method="post" action="export_excel.php" target="_blank" id="exportForm">
            <?= csrf_field(); ?>
            <input type="hidden" name="serie" value="<?= h($valgtSerie); ?>">
            <div class="modal-body">
                <p><strong>Serie:</strong> <?= h($valgtSerie) ?></p>
                <div class="form-group">
                    <label for="export_sernr_fra">SerNr fra (lav):</label>
                    <input type="number" name="sernr_fra" id="export_sernr_fra" required min="1" max="999" class="w-100px">
                </div>
                <div class="form-group">
                    <label for="export_sernr_til">SerNr til (høy):</label>
                    <input type="number" name="sernr_til" id="export_sernr_til" required min="1" max="999" class="w-100px">
                </div>
                <p class="export-info" id="exportInfo"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeExportDialog()">Avbryt</button>
                <button type="submit" class="btn btn-primary">Eksporter</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
/* Redusert H1 størrelse */
.primus-main-page h1 {
    font-size: 1.75rem;
    margin-bottom: 0.75rem;
}

/* Serie toolbar (kompakt, cyan bakgrunn) */
.primus-serie-toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0.75rem 1rem;
    background: var(--blue-head);
    border-radius: 4px;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}
.primus-serie-toolbar .form-group {
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
    font-weight: 500;
}

/* Foto header med paging på samme linje */
.primus-foto-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: var(--blue-head);
    border-radius: 4px;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
    gap: 12px;
}
.primus-foto-title {
    font-weight: 600;
    font-size: 1.05rem;
}
.primus-paging-inline {
    display: flex;
    align-items: center;
    gap: 8px;
}
.primus-paging-inline .btn-sm {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
}
.primus-goto-page-form {
    margin: 0;
    display: inline-block;
}
.primus-goto-input {
    width: 110px;
    padding: 0.25rem 0.6rem;
    border: 1px solid #ced4da;
    border-radius: 3px;
    font-size: 0.875rem;
    text-align: center;
}
.primus-goto-input:focus {
    outline: none;
    border-color: #3585fe;
    box-shadow: 0 0 0 2px rgba(53, 133, 254, 0.2);
}
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
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
.primus-paging-info {
    font-size: 0.875rem;
    color: #333;
    font-weight: 500;
}

/* Export Dialog Modal */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    /* Hidden by default; shown by JS via showExportDialog() */
    display: none;
    align-items: center;
    justify-content: center;
}
.modal-content {
    background-color: #fff;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid #dbe3ef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
}
.modal-header .close {
    font-size: 28px;
    font-weight: bold;
    color: #999;
    cursor: pointer;
    line-height: 1;
}
.modal-header .close:hover {
    color: #333;
}
.modal-body {
    padding: 20px;
}
.modal-body .form-group {
    margin-bottom: 16px;
}
.modal-body .form-group label {
    display: block;
    margin-bottom: 4px;
    font-weight: 500;
}
.modal-body .export-info {
    margin-top: 16px;
    padding: 12px;
    background: #e8f4ff;
    border: 1px solid #3585fe;
    border-radius: 4px;
    font-size: 0.9em;
}
.modal-footer {
    padding: 16px 20px;
    border-top: 1px solid #dbe3ef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<script>
// Validering for "Gå til side" skjema
function validateGotoPage() {
    var input = document.getElementById('goto_side');
    var side = parseInt(input.value);
    var maxSider = parseInt(input.max);

    if (!side || side < 1 || side > maxSider) {
        alert('Vennligst skriv inn et gyldig sidenummer mellom 1 og ' + maxSider);
        input.focus();
        return false;
    }

    return true;
}

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

    <?php if ($isAdmin): ?>
    // Admin: Toggle Transferred checkbox via AJAX
    document.querySelectorAll('.transferred-checkbox').forEach(function (checkbox) {
        checkbox.addEventListener('change', function (e) {
            e.stopPropagation(); // Prevent row double-click

            var fotoId = this.dataset.fotoId;
            var checkbox = this;

            fetch(baseUrl + '/modules/primus/api/toggle_transferred.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'foto_id=' + fotoId
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    checkbox.checked = data.transferred;
                } else {
                    // Revert on error
                    checkbox.checked = !checkbox.checked;
                    alert('Kunne ikke oppdatere status: ' + (data.error || 'Ukjent feil'));
                }
            })
            .catch(function(err) {
                // Revert on error
                checkbox.checked = !checkbox.checked;
                alert('Feil ved oppdatering');
            });
        });
    });

    // Prevent checkbox cell from triggering row double-click
    document.querySelectorAll('.transferred-cell').forEach(function(cell) {
        cell.addEventListener('dblclick', function(e) {
            e.stopPropagation();
        });
    });
    <?php endif; ?>
})();

<?php if ($isAdmin): ?>
// Export dialog functions
function showExportDialog() {
    document.getElementById('exportDialog').style.display = 'flex';
    document.getElementById('export_sernr_fra').focus();
}

function closeExportDialog() {
    document.getElementById('exportDialog').style.display = 'none';
    document.getElementById('exportForm').reset();
    document.getElementById('exportInfo').style.display = 'none';
}

// Auto-set høy = lav + 1 when lav is entered
document.getElementById('export_sernr_fra').addEventListener('input', function() {
    var lavVerdi = parseInt(this.value);
    if (!isNaN(lavVerdi)) {
        document.getElementById('export_sernr_til').value = lavVerdi + 1;
    }
});

// Calculate and show record count estimate
function validateSerNrRange() {
    var lavVerdi = parseInt(document.getElementById('export_sernr_fra').value);
    var hoeyVerdi = parseInt(document.getElementById('export_sernr_til').value);
    var infoDiv = document.getElementById('exportInfo');

    if (!isNaN(lavVerdi) && !isNaN(hoeyVerdi)) {
        // Check if høy < lav (error condition)
        if (hoeyVerdi < lavVerdi) {
            infoDiv.textContent = 'FEIL: SerNr til (' + hoeyVerdi + ') må være større eller lik SerNr fra (' + lavVerdi + ')';
            infoDiv.style.display = 'block';
            infoDiv.style.background = '#ffe8e8';
            infoDiv.style.borderColor = '#ff4444';
            infoDiv.style.color = '#721c24';
            return false;
        }

        var antall = hoeyVerdi - lavVerdi + 1;
        if (antall > 0) {
            infoDiv.textContent = 'Område: ' + antall + ' poster (kun foto med Transferred = Nei vil eksporteres)';
            infoDiv.style.display = 'block';
            infoDiv.style.color = '#333';
            if (antall > 1000) {
                infoDiv.style.background = '#ffe8e8';
                infoDiv.style.borderColor = '#ff4444';
                infoDiv.textContent += ' - FEIL: Maks 1000 poster tillatt!';
                return false;
            } else {
                infoDiv.style.background = '#e8f4ff';
                infoDiv.style.borderColor = '#3585fe';
                return true;
            }
        } else {
            infoDiv.style.display = 'none';
        }
    } else {
        infoDiv.style.display = 'none';
    }
    return true;
}

document.getElementById('export_sernr_til').addEventListener('input', validateSerNrRange);
document.getElementById('export_sernr_fra').addEventListener('input', validateSerNrRange);

// Close dialog on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeExportDialog();
    }
});

// Close dialog when clicking outside
document.getElementById('exportDialog').addEventListener('click', function(e) {
    if (e.target === this) {
        closeExportDialog();
    }
});

// Validate before form submission
document.getElementById('exportForm').addEventListener('submit', function(e) {
    var lavVerdi = parseInt(document.getElementById('export_sernr_fra').value);
    var hoeyVerdi = parseInt(document.getElementById('export_sernr_til').value);

    // Check if values are valid numbers
    if (isNaN(lavVerdi) || isNaN(hoeyVerdi)) {
        e.preventDefault();
        alert('Vennligst fyll ut både SerNr fra og SerNr til');
        return false;
    }

    // Check SerNr range (1-999)
    if (lavVerdi < 1 || lavVerdi > 999) {
        e.preventDefault();
        alert('FEIL: SerNr fra må være mellom 1 og 999');
        return false;
    }
    if (hoeyVerdi < 1 || hoeyVerdi > 999) {
        e.preventDefault();
        alert('FEIL: SerNr til må være mellom 1 og 999');
        return false;
    }

    // Check if høy < lav
    if (hoeyVerdi < lavVerdi) {
        e.preventDefault();
        alert('FEIL: SerNr til (' + hoeyVerdi + ') må være større eller lik SerNr fra (' + lavVerdi + ')');
        return false;
    }

    // Check if range exceeds 1000
    var antall = hoeyVerdi - lavVerdi + 1;
    if (antall > 1000) {
        e.preventDefault();
        alert('FEIL: Du kan ikke eksportere mer enn 1000 poster om gangen.\nValgt område: ' + antall + ' poster');
        return false;
    }

    // If validation passes, redirect to confirmation page after download
    setTimeout(function() {
        window.location.href = 'export_confirm.php';
    }, 2000);
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../includes/layout_slutt.php'; ?>
