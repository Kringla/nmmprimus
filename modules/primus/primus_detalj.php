<?php
declare(strict_types=1);

/**
 * primus_detalj.php
 *
 * Detaljvisning og redigering av foto.
 * Tilsvarer Access-form: frmNMMPrimus.
 */

// VIKTIG: Auth og validering FØR layout
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/primus_modell.php';
require_once __DIR__ . '/../foto/foto_modell.php';

require_login();

$db = db();

$fotoId = filter_input(INPUT_GET, 'Foto_ID', FILTER_VALIDATE_INT);
if (!$fotoId) {
    redirect('primus_main.php');
}

$foto = foto_hent_en($db, $fotoId);
if (!$foto) {
    redirect('primus_main.php');
}

// BASE_URL for JavaScript
$baseUrlJs = base_url_js();

// --------------------------------------------------
// Helpers for form fields
// --------------------------------------------------
function txt(string $n, string $l, string $v = '', bool $readonly = false, string $width = '100%'): void
{
    $ro = $readonly ? 'readonly' : '';
    echo "<div class='form-group' style='width:$width;'>
            <label for='$n'>$l</label>
            <input type='text' name='$n' id='$n' value='" . h($v) . "' $ro>
          </div>";
}

function num(string $n, string $l, int $v = 0, bool $readonly = false, string $width = '100%'): void
{
    $ro = $readonly ? 'readonly' : '';
    echo "<div class='form-group' style='width:$width;'>
            <label for='$n'>$l</label>
            <input type='number' name='$n' id='$n' value='" . h((string)$v) . "' $ro>
          </div>";
}

function area(string $n, string $l, string $v = '', int $r = 3): void
{
    echo "<div class='form-group'>
            <label for='$n'>$l</label>
            <textarea name='$n' id='$n' rows='$r'>" . h($v) . "</textarea>
          </div>";
}

function chk(string $n, string $l, bool $v = false): void
{
    $c = $v ? 'checked' : '';
    echo "<label class='form-check' for='$n'>
            <input type='checkbox' name='$n' id='$n' value='1' $c> $l
          </label>";
}

function combo(string $n, string $l, array $opts, string $v = '', string $width = '100%'): void
{
    echo "<div class='form-group' style='width:$width;'>
            <label for='$n'>$l</label>
            <select name='$n' id='$n' style='width:100%;'>";
    foreach ($opts as $opt) {
        $o = (string)$opt;
        $sel = $o === $v ? 'selected' : '';
        echo "<option value='" . h($o) . "' $sel>" . h($o) . "</option>";
    }
    echo "    </select>
          </div>";
}

// --------------------------------------------------
// H2-modus: kandidatpanel klikkbart kun ved ny rad
// --------------------------------------------------
$h2 = (int)($_SESSION['primus_h2'] ?? 0) === 1;

// --------------------------------------------------
// Defaults i Øvrige (kun ved ny rad / H2)
// Access-paritet iht Primus_Schema.md
// --------------------------------------------------
if ($h2) {
    if (empty($foto['Status'])) {
        $foto['Status'] = 'Original';
    }
    if (empty($foto['Plassering'])) {
        $foto['Plassering'] = '0286:NMM Oslo/Mus:NMM, Bygdøynesveien 37/Bib:Biblioteket - Fotoarkiv Damp- og Motorskip';
    }
    if (empty($foto['Prosess'])) {
        $foto['Prosess'] = 'Positivkopi;300';
    }
    if (empty($foto['Tilstand'])) {
        $foto['Tilstand'] = 'God';
    }
}

$svarthvittValg = ['Svart-hvit', 'Farge', 'Håndkolorert'];
if (!isset($foto['Svarthvitt']) || $foto['Svarthvitt'] === '' || $foto['Svarthvitt'] === null) {
    $foto['Svarthvitt'] = $svarthvittValg[0];
}

// --------------------------------------------------
// Hendelsesmodus (iCh) – session-paritet
// --------------------------------------------------
if (isset($_SESSION['primus_iCh'])) {
    $iCh = (int)$_SESSION['primus_iCh'];
} else {
    $iCh = (int)($foto['iCh'] ?? 1);
}
if ($iCh < 1 || $iCh > 6) {
    $iCh = 1;
}

// --------------------------------------------------
// Aktiv fane (primus_tab) – session-paritet
// --------------------------------------------------
$aktivTab = 'motiv';
if (isset($_SESSION['primus_tab'])) {
    $t = (string)$_SESSION['primus_tab'];
    if (in_array($t, ['motiv', 'historikk', 'ovrige'], true)) {
        $aktivTab = $t;
    }
}

// --------------------------------------------------
// Kandidatsøk (venstre panel) – kun visning
// --------------------------------------------------
$kandidatSok = trim((string)($_GET['k_sok'] ?? ''));
$kandidater = $h2 ? primus_hent_skip_liste($kandidatSok) : [];

// --------------------------------------------------
// "Legg til i 'Avbildet'" (additiv via fartoy_velg.php)
// Returnerer ?add_avbildet_nmm_id=...
// --------------------------------------------------
$addAvbId = filter_input(INPUT_GET, 'add_avbildet_nmm_id', FILTER_VALIDATE_INT);
if ($addAvbId) {
    $felt = primus_hent_kandidat_felter((int)$addAvbId);
    if (!empty($felt['Avbildet']) && $felt['ok']) {
        $ny = trim((string)$felt['Avbildet']);

        $eks = (string)($foto['Avbildet'] ?? '');
        $eksTrim = trim($eks);

        $separator = ";\n";
        $liste = [];

        if ($eksTrim !== '') {
            foreach (preg_split("/;\s*\n/", $eksTrim) ?: [] as $del) {
                $del = trim((string)$del);
                if ($del !== '') $liste[] = $del;
            }
        }

        // Dedupe
        $finnes = false;
        foreach ($liste as $del) {
            if (mb_strtolower($del) === mb_strtolower($ny)) {
                $finnes = true;
                break;
            }
        }
        if (!$finnes) {
            $liste[] = $ny;
        }

        $foto['Avbildet'] = implode($separator, $liste);

        // Save the updated Avbildet to database
        $stmt = $db->prepare("UPDATE nmmfoto SET Avbildet = :avbildet WHERE Foto_ID = :foto_id");
        $stmt->execute([
            'avbildet' => $foto['Avbildet'],
            'foto_id' => $fotoId
        ]);
    }

    // Fjern query-param fra URL (men behold Foto_ID og evt k_sok)
    $base = 'primus_detalj.php?Foto_ID=' . (int)$fotoId;
    if ($kandidatSok !== '') {
        $base .= '&k_sok=' . rawurlencode($kandidatSok);
    }
    redirect($base);
}

// --------------------------------------------------
// POST: kopier foto (Access: cmdKopier)
// --------------------------------------------------
if (is_post() && ($_POST['action'] ?? '') === 'kopier_foto') {
    if (!csrf_validate()) {
        die('Ugyldig forespørsel (CSRF).');
    }

    // Lagre eksisterende rad først
    if ($db->inTransaction()) {
        $db->commit();
    }

    // Hent serie fra eksisterende foto
    $eksisterende = foto_hent_en($db, $fotoId);
    $bildeFil = (string)($eksisterende['Bilde_Fil'] ?? '');
    $serie = '';

    if (strlen($bildeFil) >= 8) {
        $serie = substr($bildeFil, 0, 8);
    }

    // Kopier foto (nullstiller Bildehistorikk og Øvrige)
    $nyFotoId = foto_kopier($db, $fotoId);

    // Oppdater SerNr og Bilde_Fil for den nye kopien
    if ($serie !== '') {
        $nesteSerNr = primus_hent_neste_sernr($serie);

        $stmt = $db->prepare("
            UPDATE nmmfoto
            SET SerNr = :sernr,
                Bilde_Fil = :bilde_fil,
                URL_Bane = :url_bane
            WHERE Foto_ID = :foto_id
        ");
        $stmt->execute([
            'sernr' => $nesteSerNr,
            'bilde_fil' => $serie . '-' . $nesteSerNr,
            'url_bane' => $serie . ' -001-999 Damp og Motor',
            'foto_id' => $nyFotoId
        ]);
    }

    // H2-modus = 0 (venstre panel IKKE synlig/klikkbart)
    $_SESSION['primus_h2'] = 0;
    $_SESSION['primus_iCh'] = 1; // Hendelsesmodus = Ingen

    redirect('primus_detalj.php?Foto_ID=' . $nyFotoId);
}

// --------------------------------------------------
// POST: lagre foto (først her skjer DB-lagring)
// --------------------------------------------------
if (is_post()) {
    if (!csrf_validate()) {
        die('Ugyldig forespørsel (CSRF).');
    }

    $data = $_POST;
    $data['Foto_ID'] = $fotoId;

    // KORRIGERT: Bilde_Fil = NMMSerie-SerNr
    if (!empty($data['NMMSerie']) && isset($data['SerNr'])) {
        $data['Bilde_Fil'] = $data['NMMSerie'] . '-' . (int)$data['SerNr'];
    }

    foto_lagre($db, $data);
    redirect('primus_main.php');
}

// --------------------------------------------------
// Toppfelt: NMMSerie + SerNr + Bilde_Fil
// SerNr avledes av Bilde_Fil og NMMSerie (8 første tegn = NMMSerie)
// --------------------------------------------------
$sernr = (int)($foto['SerNr'] ?? 0);
$bild = (string)($foto['Bilde_Fil'] ?? '');
$serieFraBild = (strlen($bild) >= 8) ? substr($bild, 0, 8) : '';

$nmmSerie = (string)($foto['NMMSerie'] ?? $serieFraBild);
if ($nmmSerie === '' && $serieFraBild !== '') {
    $nmmSerie = $serieFraBild;
}
if ($nmmSerie !== '' && str_starts_with($bild, $nmmSerie)) {
    // plukk ut alle siffer etter serien (tåler både "NSM.2113-652" og "NSM.2113652")
    $rest = substr($bild, 8);
    if (preg_match('/^-?(\d+)/', $rest, $m)) {
        $sernr = (int)$m[1];
    }
}

// --------------------------------------------------
// ValgtFartøy + FTO: alltid basert på valgt NMM_ID
// (hvis NMM_ID finnes)
// --------------------------------------------------
$valgtFartoyVis = (string)($foto['ValgtFartøy'] ?? '');
$ftoVis = (string)($foto['FTO'] ?? '');

$aktNmmId = (int)($foto['NMM_ID'] ?? 0);
if ($aktNmmId > 0) {
    $felt = primus_hent_kandidat_felter($aktNmmId);
    if (!empty($felt['ok'])) {
        $valgtFartoyVis = (string)$felt['ValgtFartoy'];
        $ftoVis = (string)$felt['FTO'];
    }
}

// NÅ kan vi inkludere layout (etter all logikk)
$pageTitle = 'Primus – foto';
require_once __DIR__ . '/../../includes/layout_start.php';
?>

<div class="container-fluid">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h1 style="margin:0;">Primus – foto</h1>
        <div style="display:flex; gap:12px;">
            <button type="submit" form="foto-form" class="btn btn-primary">Oppdater</button>
            <form method="post" style="display:inline; margin:0;">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="kopier_foto">
                <button type="submit" class="btn btn-info" onclick="return confirm('Kopiere dette fotoet?');">Kopier foto</button>
            </form>
            <a href="primus_main.php" class="btn btn-secondary">Tilbake</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <form method="post" id="foto-form">
                <?= csrf_field(); ?>

                <!-- Kandidatstyrt NMM_ID (H2). I H1 beholder vi DB-verdi. -->
                <input type="hidden" name="NMM_ID" id="NMM_ID" value="<?= h((string)($foto['NMM_ID'] ?? '')) ?>">

                <!-- TOPP: ValgtFartøy -->
                <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:center; justify-content:center; margin-bottom:16px;">
                    <div class="form-group" style="width:420px; margin:0;">
                        <label for="ValgtFartoy_vis" style="text-align:center; display:block;">Valgt fartøy</label>
                        <input type="text" name="ValgtFartoy_vis" id="ValgtFartoy_vis" value="<?= h($valgtFartoyVis) ?>" readonly style="text-align:center; font-size:1.2em; font-weight:bold; font-family:Arial, sans-serif; width:100%;">
                    </div>
                </div>

                <hr>

                <!-- Serie / fil -->
                <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:flex-end;">
                    <div class="form-group" style="flex:0 0 15ch;">
                        <label for="NMMSerie">NSM serie</label>
                        <select name="NMMSerie" id="NMMSerie" style="width:100%;">
                            <?php
                            // Enkel serie-liste fra bildeserie-tabellen (scroll i select)
                            $serier = primus_hent_bildeserier();
                            foreach ($serier as $s) {
                                $sv = (string)($s['Serie'] ?? '');
                                if ($sv === '') continue;
                                $sel = ($sv === $nmmSerie) ? 'selected' : '';
                                echo '<option value="' . h($sv) . '" ' . $sel . '>' . h($sv) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <?php
                    $bildeFilVis = (string)($foto['Bilde_Fil'] ?? '');
                    if ($nmmSerie !== '' && $sernr > 0) {
                        $bildeFilVis = $nmmSerie . '-' . (string)$sernr;
                    }
                    ?>
                    <div class="form-group" style="flex:0 0 7ch;">
                        <label for="SerNr">Serienr</label>
                        <input type="number" name="SerNr" id="SerNr" value="<?= h((string)$sernr) ?>">
                    </div>
                    <div class="form-group" style="flex:0 0 15ch;">
                        <label for="Bilde_Fil">Bildefil</label>
                        <input type="text" name="Bilde_Fil" id="Bilde_Fil" value="<?= h($bildeFilVis) ?>" readonly>
                    </div>
                    <div class="form-group" style="flex:1 1 auto; min-width:300px;">
                        <label for="FTO_vis">Bilde kommentarer</label>
                        <input type="text" name="FTO_vis" id="FTO_vis" value="<?= h($ftoVis) ?>" readonly>
                    </div>
                </div>

                <hr>

                <div style="display:flex; gap:16px; align-items:flex-start;">

                    <!-- VENSTRE: kandidater (kun H2) -->
                    <div style="width:420px; flex:0 0 auto;">
                        <div class="card">
                            <div class="card-header">
                                <strong>Kandidater</strong>
                                <?php if (!$h2): ?>
                                    <div style="font-size:12px; opacity:.7;">
                                        (Kun aktiv ved "Nytt foto")
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">

                                <?php if ($h2): ?>
                                    <div style="margin-bottom:10px;">
                                        <input type="hidden" id="k_sok_foto_id" value="<?= h((string)$fotoId) ?>">
                                        <div class="form-group">
                                            <label for="k_sok">Søk (FNA)</label>
                                            <input type="text" id="k_sok" value="<?= h($kandidatSok) ?>">
                                        </div>
                                        <button class="btn btn-secondary" type="button" id="btn-kandidat-sok">Søk</button>
                                    </div>

                                    <div style="max-height:520px; overflow:auto; border:1px solid #ddd; padding:6px;">
                                        <table class="table table-sm" style="margin:0;">
                                            <thead style="position:sticky; top:0; background:#fff;">
                                                <tr>
                                                    <th>Fartøy</th>
                                                    <th style="white-space:nowrap;">BYG</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($kandidater as $k): ?>
                                                <?php
                                                $kid = (int)$k['NMM_ID'];
                                                $navn = trim((string)$k['FTY'] . ' ' . (string)$k['FNA']);
                                                $byg = (string)($k['BYG'] ?? '');
                                                ?>
                                                <tr class="kandidat-rad" data-nmm-id="<?= h((string)$kid) ?>" style="cursor:pointer;">
                                                    <td><?= h($navn) ?></td>
                                                    <td style="white-space:nowrap;"><?= h($byg) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div style="opacity:.7;">
                                        Kandidatvalg er deaktivert for eksisterende fartøy.
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                    <!-- HØYRE: faner -->
                    <div style="flex:1 1 auto; min-width:520px;">

                        <div class="primus-tabs">

                            <div class="primus-tabbar">
                                <button type="button"
                                        class="primus-tab <?= $aktivTab === 'motiv' ? 'is-active' : '' ?>"
                                        data-tab="motiv">Motiv</button>

                                <button type="button"
                                        class="primus-tab <?= $aktivTab === 'historikk' ? 'is-active' : '' ?>"
                                        data-tab="historikk">Bildehistorikk</button>

                                <button type="button"
                                        class="primus-tab <?= $aktivTab === 'ovrige' ? 'is-active' : '' ?>"
                                        data-tab="ovrige">Øvrige</button>
                            </div>

                            <div class="primus-pane <?= $aktivTab === 'motiv' ? 'is-active' : '' ?>" id="motiv">
                                <?php
                                area('MotivBeskr', 'Motivbeskrivelse', (string)($foto['MotivBeskr'] ?? ''), 4);
                                area('MotivBeskrTillegg', 'Tillegg, Motivbeskrivelse', (string)($foto['MotivBeskrTillegg'] ?? ''), 3);

                                area('Avbildet', 'Avbildet', (string)($foto['Avbildet'] ?? ''), 3);
                                ?>
                                <div class="mb-2">
                                    <?php
                                    $retUrl = 'primus_detalj.php?Foto_ID=' . (int)$fotoId;
                                    if ($kandidatSok !== '') {
                                        $retUrl .= '&k_sok=' . rawurlencode($kandidatSok);
                                    }
                                    ?>
                                    <a class="btn btn-secondary"
                                       href="../fartoy/fartoy_velg.php?Foto_ID=<?= (int)$fotoId ?>&ret=<?= rawurlencode($retUrl) ?>&mode=add_avbildet">
                                        Legg til i 'Avbildet'
                                    </a>
                                </div>

                                <hr>

                                <?php
                                area('MotivType', 'Motivtype', (string)($foto['MotivType'] ?? ''), 3);
                                ?>
                                <div class="mb-2">
                                    <button type="button" class="btn btn-secondary" id="btn-leggtil-skipsportrett">
                                        Legg til 'Skipsportrett'
                                    </button>
                                </div>

                                <?php
                                area('MotivEmne', 'Motivemne', (string)($foto['MotivEmne'] ?? ''), 3);
                                area('MotivKriteria', 'Søkekriteria', (string)($foto['MotivKriteria'] ?? ''), 3);
                                ?>
                            </div>

                            <div class="primus-pane <?= $aktivTab === 'historikk' ? 'is-active' : '' ?>" id="historikk">
                                <strong>Hendelsesmodus</strong>
                                <div class="hendelser-rad">
                                    <?php
                                    $lbl = [1 => 'Ingen', 2 => 'Fotografi', 3 => 'Samling', 4 => 'Foto+Samling', 5 => 'Annet', 6 => 'Alle'];
                                    foreach ($lbl as $v => $t):
                                    ?>
                                        <label class="form-check">
                                            <input type="radio" name="iCh" value="<?= $v ?>" <?= $iCh === $v ? 'checked' : '' ?>>
                                            <?= $v ?> – <?= h($t) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <?php
                                area('Hendelse', 'Hendelse', (string)($foto['Hendelse'] ?? ''), 2);
                                txt('Samling', 'Samling', (string)($foto['Samling'] ?? ''));
                                chk('FriKopi', 'Fri kopi', (bool)($foto['FriKopi'] ?? false));
                                txt('Fotograf', 'Fotograf', (string)($foto['Fotograf'] ?? ''));
                                txt('FotoFirma', 'Fotofirma', (string)($foto['FotoFirma'] ?? ''));
                                txt('FotoTidFra', 'Tid (Fra)', (string)($foto['FotoTidFra'] ?? ''));
                                txt('FotoTidTil', 'Tid (Til)', (string)($foto['FotoTidTil'] ?? ''));
                                txt('FotoSted', 'Sted tatt', (string)($foto['FotoSted'] ?? ''));
                                ?>
                            </div>

                            <div class="primus-pane <?= $aktivTab === 'ovrige' ? 'is-active' : '' ?>" id="ovrige">
                                <?php
                                txt('ReferNeg', 'Referanse, NMM', (string)($foto['ReferNeg'] ?? ''));
                                txt('ReferFArk', 'Referanse, fotograf', (string)($foto['ReferFArk'] ?? ''));
                                ?>
                                <hr>
                                <?php
                                txt('Plassering', 'Plassering', (string)($foto['Plassering'] ?? ''));
                                txt('Prosess', 'Prosess', (string)($foto['Prosess'] ?? ''));
                                ?>
                                <hr>
                                <?php
                                $tilstandValg = ['God', 'Dårlig'];
                                $tilstandVerdi = (string)($foto['Tilstand'] ?? 'God');
                                if ($tilstandVerdi === '' || $tilstandVerdi === null) {
                                    $tilstandVerdi = 'God';
                                }

                                combo('Svarthvitt', 'Svarthvitt', $svarthvittValg, (string)($foto['Svarthvitt'] ?? ''));
                                txt('Status', 'Status', (string)($foto['Status'] ?? ''));
                                combo('Tilstand', 'Tilstand', $tilstandValg, $tilstandVerdi);
                                ?>
                                <hr>
                                <?php area('Merknad', 'Merknad', (string)($foto['Merknad'] ?? ''), 4); ?>
                            </div>

                        </div>

                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
(function(){
    var baseUrl = <?= $baseUrlJs ?>;

    // ---------------- Tabs ----------------
    document.querySelectorAll('.primus-tab').forEach(function(tab) {
        tab.addEventListener('click', function () {
            var valgt = this.dataset.tab;
            if (!valgt) return;

            document.querySelectorAll('.primus-tab')
                .forEach(function(t) { t.classList.toggle('is-active', t === tab); });

            document.querySelectorAll('.primus-pane')
                .forEach(function(p) { p.classList.toggle('is-active', p.id === valgt); });

            fetch(baseUrl + '/modules/primus/api/sett_session.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'primus_tab=' + encodeURIComponent(valgt)
            }).catch(function(){});
        });
    });

    // ---------------- Kandidatsøk (GET uten nested form) ----------------
    var sokBtn = document.getElementById('btn-kandidat-sok');
    if (sokBtn) {
        sokBtn.addEventListener('click', function () {
            var sok = document.getElementById('k_sok');
            if (!sok) return;

            var url = 'primus_detalj.php?Foto_ID=<?= (int)$fotoId ?>';
            if (sok.value.trim() !== '') {
                url += '&k_sok=' + encodeURIComponent(sok.value.trim());
            }
            window.location.href = url;
        });
    }

    // ---------------- iCh → foto_state ----------------
    function oppdaterFotoState(){
        var valgt = document.querySelector('input[name="iCh"]:checked');
        if(!valgt) return;

        fetch(baseUrl + '/modules/primus/api/sett_session.php', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'primus_iCh=' + encodeURIComponent(valgt.value)
        }).catch(function(){});

        fetch(baseUrl + '/modules/foto/api/foto_state.php', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'fmeHendelse=' + encodeURIComponent(valgt.value)
        })
        .then(function(r) { return r.ok ? r.json() : null; })
        .then(function(json) {
            if(!json || !json.ok || !json.data || !json.data.felter) return;

            Object.keys(json.data.felter).forEach(function(id) {
                var el = document.getElementById(id);
                if(!el) return;
                el.disabled = false;
                el.removeAttribute('data-foto-state');
            });

            Object.keys(json.data.felter).forEach(function(id) {
                var enabled = json.data.felter[id];
                var el = document.getElementById(id);
                if(!el) return;

                el.disabled = !enabled;
                el.dataset.fotoState = enabled ? 'aktiv' : 'inaktiv';

                if(!enabled){
                    if(el.type === 'checkbox' || el.type === 'radio') {
                        el.checked = false;
                    } else {
                        el.value = '';
                    }
                } else if(id === 'Fotograf' && !el.value.trim()) {
                    el.value = '10F:';
                }
            });

            // Apply verdier (field values like Hendelse)
            if(json.data.verdier) {
                Object.keys(json.data.verdier).forEach(function(id) {
                    var el = document.getElementById(id);
                    if(!el) return;
                    el.value = json.data.verdier[id] || '';
                });
            }
        })
        .catch(function(){});
    }

    document.querySelectorAll('input[name="iCh"]')
        .forEach(function(r) { r.addEventListener('change', oppdaterFotoState); });

    oppdaterFotoState();

    // ---------------- Kandidatklikk (kun H2) ----------------
    var h2 = <?= $h2 ? 'true' : 'false' ?>;

    function settFelt(id, val){
        var el = document.getElementById(id);
        if(!el) return;
        el.value = val || '';
    }

    function settVis(id, val){
        var el = document.getElementById(id);
        if(!el) return;
        el.value = val || '';
    }

    if (h2) {
        document.querySelectorAll('.kandidat-rad').forEach(function(row) {
            row.addEventListener('click', function(){
                var nmmId = this.dataset.nmmId;
                if (!nmmId) return;

                fetch(baseUrl + '/modules/primus/api/kandidat_data.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: 'NMM_ID=' + encodeURIComponent(nmmId)
                })
                .then(function(r) { return r.ok ? r.json() : null; })
                .then(function(json) {
                    if(!json || !json.ok || !json.data) return;

                    var d = json.data;

                    // Kandidatstyrt kontekst
                    settFelt('NMM_ID', nmmId);

                    // Overskriv felt i skjema (Access: SummaryFields)
                    settVis('ValgtFartoy_vis', d.ValgtFartoy || '');
                    settVis('FTO_vis', d.FTO || '');

                    settFelt('Avbildet', d.Avbildet || '');
                    settFelt('MotivType', d.MotivType || '-');
                    settFelt('MotivEmne', d.MotivEmne || '-');
                    settFelt('MotivKriteria', d.MotivKriteria || '-');

                    // Access-paritet: bygg MotivBeskr fra kandidatfelter
                    var fty = (d.FTY || '').trim();
                    var fna = (d.FNA || '').trim();
                    var byg = (d.BYG || '').trim();
                    var ver = (d.VER || '').trim();
                    var xna = parseInt(d.XNA || '0', 10);

                    if (fty === '' || fna === '') {
                        var fto = (d.FTO || '').trim();
                        settFelt('MotivBeskr', fto !== '' ? fto : '');
                    } else {
                        var mb = '';
                        if (xna > 0) {
                            mb = fty + ' ' + fna + ' (Ex. ' + xna + ')(' + byg + ', ' + ver + ')';
                        } else {
                            mb = fty + ' ' + fna + ' (' + byg + ', ' + ver + ')';
                        }
                        settFelt('MotivBeskr', mb);
                    }
                })
                .catch(function(){});
            });
        });
    }
})();
</script>

<script type="text/javascript">
/* ---------------------------------------------
   OPPDATER Bilde_Fil klient-side (visuelt)
   --------------------------------------------- */
function oppdaterBildeFil() {
    var serie = document.getElementById('NMMSerie');
    var serNr = document.getElementById('SerNr');
    var bildeFil = document.getElementById('Bilde_Fil');

    if (serie && serNr && bildeFil) {
        bildeFil.value = String(serie.value) + '-' + String(serNr.value);
    }
}

var serieEl = document.getElementById('NMMSerie');
if (serieEl) {
    serieEl.addEventListener('change', oppdaterBildeFil);
}

var serNrEl = document.getElementById('SerNr');
if (serNrEl) {
    serNrEl.addEventListener('input', oppdaterBildeFil);
}
</script>

<script>
/* ---------------------------------------------
   Tillegg, Motivbeskrivelse -> Motivbeskrivelse
   --------------------------------------------- */
(function() {
    var tillegg = document.getElementById('MotivBeskrTillegg');
    var motiv = document.getElementById('MotivBeskr');

    if (!tillegg || !motiv) return;

    function appendTillegg() {
        var tilleggVal = tillegg.value.trim();
        if (tilleggVal === '') return;

        var motivVal = motiv.value.trim();
        if (motivVal === '') {
            motiv.value = tilleggVal;
        } else {
            motiv.value = motivVal + ' ' + tilleggVal;
        }

        // Keep value in Tillegg field for storage
    }

    // On blur (when field loses focus)
    tillegg.addEventListener('blur', appendTillegg);

    // On Enter key
    tillegg.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            appendTillegg();
        }
    });
})();
</script>

<script>
/* ---------------------------------------------
   Legg til 'Skipsportrett' button
   --------------------------------------------- */
(function() {
    var btn = document.getElementById('btn-leggtil-skipsportrett');
    if (!btn) return;

    btn.addEventListener('click', function() {
        var motivType = document.getElementById('MotivType');
        if (!motivType) return;

        var current = motivType.value.trim();
        // Format from Access: ID;MotivType;UUID
        var toAdd = '1060;Skipsportrett;4D9A6929-3BE1-42E4-B5F4-A2782C75A054';

        // Check if 'Skipsportrett' already exists
        if (current.toLowerCase().includes('skipsportrett')) {
            return; // Already present
        }

        // Append with newline separator if there's existing content
        if (current === '' || current === '-') {
            motivType.value = toAdd;
        } else {
            motivType.value = current + '\n' + toAdd;
        }
    });
})();
</script>

<?php
require_once __DIR__ . '/../../includes/layout_slutt.php';
