<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/ui.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../foto/foto_modell.php';

require_login();
$db = db();

// --------------------------------------------------
// INPUT
// --------------------------------------------------
$fotoId = filter_input(INPUT_GET, 'Foto_ID', FILTER_VALIDATE_INT);
if (!$fotoId) {
    redirect('primus_main.php');
}

// Access-ekvivalent: aktiv record
$_SESSION['aktiv_foto_id'] = $fotoId;

// --------------------------------------------------
// HENT FOTO
// --------------------------------------------------
$foto = foto_hent_en($db, $fotoId);
if (!$foto) {
    unset($_SESSION['aktiv_foto_id']);
    redirect('primus_main.php');
}

// --------------------------------------------------
// HJELPERE: Serie/SerNr (C7)
// --------------------------------------------------
function serie_fra_bildefil(string $bildeFil): string
{
    $bildeFil = trim($bildeFil);
    if ($bildeFil === '' || !str_contains($bildeFil, '-')) return '';
    [$serie, ] = explode('-', $bildeFil, 2);
    return trim($serie);
}

function bildefil_fra_serie_sernr(string $serie, int $serNr): string
{
    return $serie . '-' . str_pad((string)$serNr, 3, '0', STR_PAD_LEFT);
}

function sernr_ledig_i_serie(PDO $db, string $serie, int $serNr, int $ignorerFotoId): bool
{
    $bf = bildefil_fra_serie_sernr($serie, $serNr);

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM nmmfoto
        WHERE Bilde_Fil = :bf
          AND Foto_ID <> :id
    ");
    $stmt->execute(['bf' => $bf, 'id' => $ignorerFotoId]);

    return ((int)$stmt->fetchColumn()) === 0;
}

// --------------------------------------------------
// FEILMELDING
// --------------------------------------------------
$feil = null;

// --------------------------------------------------
// POST / LAGRING
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Access: cmdKopier
    if (isset($_POST['cmd_kopier'])) {
        $nyFotoId = foto_kopier($db, $fotoId);
        redirect('primus_detalj.php?Foto_ID=' . $nyFotoId);
    }

    $data = $_POST;
    $data['Foto_ID'] = $fotoId;

    // Sikkerhet: NMM_ID skal ikke kunne manipuleres
    $data['NMM_ID'] = $foto['NMM_ID'] ?? null;

    // -----------------------------
    // C7: SerNr oppdaterbart + Bilde_Fil regenereres
    // -----------------------------
    $serNr = isset($_POST['SerNr']) ? (int)$_POST['SerNr'] : 0;

    if ($serNr < 1 || $serNr > 999) {
        $feil = 'SerNr må være mellom 1 og 999.';
    } else {
        $serie = serie_fra_bildefil((string)($foto['Bilde_Fil'] ?? ''));
        if ($serie === '') {
            $feil = 'Kan ikke utlede serie fra eksisterende Bilde_Fil.';
        } elseif (!sernr_ledig_i_serie($db, $serie, $serNr, $fotoId)) {
            $feil = "SerNr $serNr er allerede i bruk i serien $serie.";
        } else {
            $data['SerNr'] = $serNr;
            $data['Bilde_Fil'] = bildefil_fra_serie_sernr($serie, $serNr);
        }
    }

    if ($feil === null) {
        foto_lagre($db, $data);
        $foto = foto_hent_en($db, $fotoId) ?? $foto;
    }
}

// --------------------------------------------------
// UI-hjelpere
// --------------------------------------------------
function felt_str(array $row, string $key): string
{
    $v = $row[$key] ?? '';
    if ($v === null) return '';
    return (string)$v;
}
function felt_int(array $row, string $key): int
{
    return (int)($row[$key] ?? 0);
}
function felt_bool(array $row, string $key): bool
{
    return (bool)($row[$key] ?? false);
}
function input_text(string $id, string $label, string $value, bool $readonly = false): void
{
    $ro = $readonly ? 'readonly' : '';
    echo '<div class="mb-3">';
    echo '<label class="form-label" for="' . h($id) . '">' . h($label) . '</label>';
    echo '<input type="text" class="form-control" id="' . h($id) . '" name="' . h($id) . '" value="' . h($value) . '" ' . $ro . '>';
    echo '</div>';
}
function input_number(string $id, string $label, int $value, bool $readonly = false): void
{
    $ro = $readonly ? 'readonly' : '';
    echo '<div class="mb-3">';
    echo '<label class="form-label" for="' . h($id) . '">' . h($label) . '</label>';
    echo '<input type="number" class="form-control" id="' . h($id) . '" name="' . h($id) . '" value="' . h((string)$value) . '" ' . $ro . '>';
    echo '</div>';
}
function input_date(string $id, string $label, string $value): void
{
    // DB kan inneholde datetime/tekst; vi forsøker å vise som YYYY-MM-DD hvis mulig
    $val = trim($value);
    if ($val !== '') {
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $val, $m)) {
            $val = $m[0];
        }
    }
    echo '<div class="mb-3">';
    echo '<label class="form-label" for="' . h($id) . '">' . h($label) . '</label>';
    echo '<input type="date" class="form-control" id="' . h($id) . '" name="' . h($id) . '" value="' . h($val) . '">';
    echo '</div>';
}
function input_textarea(string $id, string $label, string $value, int $rows = 3, bool $readonly = false): void
{
    $ro = $readonly ? 'readonly' : '';
    echo '<div class="mb-3">';
    echo '<label class="form-label" for="' . h($id) . '">' . h($label) . '</label>';
    echo '<textarea class="form-control" id="' . h($id) . '" name="' . h($id) . '" rows="' . h((string)$rows) . '" ' . $ro . '>' . h($value) . '</textarea>';
    echo '</div>';
}
function input_checkbox(string $id, string $label, bool $checked): void
{
    $c = $checked ? 'checked' : '';
    echo '<div class="form-check mb-2">';
    echo '<input class="form-check-input" type="checkbox" id="' . h($id) . '" name="' . h($id) . '" value="1" ' . $c . '>';
    echo '<label class="form-check-label" for="' . h($id) . '">' . h($label) . '</label>';
    echo '</div>';
}

// --------------------------------------------------
// fmeHendelse default (Access SettFrame)
// --------------------------------------------------
$fme = isset($_POST['fmeHendelse']) ? (int)$_POST['fmeHendelse'] : 0;
if ($fme < 1 || $fme > 6) {
    $aks = !empty($foto['Aksesjon']);
    $fot = !empty($foto['Fotografi']);

    if ($aks) {
        $fme = $fot ? 4 : 3;
    } else {
        $fme = $fot ? 2 : 1;
    }
}

// Serie for live Bilde_Fil
$serieForLive = serie_fra_bildefil((string)($foto['Bilde_Fil'] ?? ''));

// --------------------------------------------------
// PAGE
// --------------------------------------------------
$pageTitle = 'Primus – foto';
require_once __DIR__ . '/../../includes/layout_start.php';
?>

<style>
/* Rød/grønn merking av hendelsesstyrte felt (Access-paritet) */
.foto-styrt-aktiv {
    border: 2px solid #198754 !important;
    background-color: #f6fffa;
}
.foto-styrt-inaktiv {
    border: 2px solid #dc3545 !important;
    background-color: #fff5f5;
}
</style>

<div class="container">
    <h1>Primus – foto</h1>

    <?php if ($feil): ?>
        <div class="alert alert-danger"><?= h($feil) ?></div>
    <?php endif; ?>

    <?php ui_card_start('Foto – detaljer'); ?>

    <form method="post" id="foto-form">
        <input type="hidden" name="Foto_ID" value="<?= h((string)$fotoId); ?>">
        <input type="hidden" name="NMM_ID" value="<?= h((string)($foto['NMM_ID'] ?? '')); ?>">

        <!-- Feltliste: URL_Bane ikke synlig, men må persisteres -->
        <input type="hidden" id="URL_Bane" name="URL_Bane" value="<?= h(felt_str($foto, 'URL_Bane')) ?>">

        <?php
        input_number('SerNr', 'SerNr', felt_int($foto, 'SerNr'));
        input_text('Bilde_Fil', 'Bilde_Fil', felt_str($foto, 'Bilde_Fil'), true);
        ?>

        <div class="mb-3">
            <div class="form-label">Hendelsesmodus</div>
            <?php
            $hendelser = [
                1 => '1 – Ingen',
                2 => '2 – Fotografi',
                3 => '3 – Samling',
                4 => '4 – Foto+Saml',
                5 => '5 – Annet',
                6 => '6 – Alle',
            ];
            foreach ($hendelser as $val => $txt) {
                $checked = ($fme === $val) ? 'checked' : '';
                echo '<div class="form-check form-check-inline">';
                echo '<input class="form-check-input" type="radio" name="fmeHendelse" id="fmeHendelse_' . h((string)$val) . '" value="' . h((string)$val) . '" ' . $checked . '>';
                echo '<label class="form-check-label" for="fmeHendelse_' . h((string)$val) . '">' . h($txt) . '</label>';
                echo '</div>';
            }
            ?>
        </div>

        <?php
        input_textarea('MotivBeskr', 'MotivBeskr', felt_str($foto, 'MotivBeskr'), 3);
        input_textarea('MotivBeskrTillegg', 'MotivBeskrTillegg', felt_str($foto, 'MotivBeskrTillegg'), 3);

        input_textarea('MotivType', 'MotivType', felt_str($foto, 'MotivType'), 4);
        input_textarea('MotivEmne', 'MotivEmne', felt_str($foto, 'MotivEmne'), 4);
        input_textarea('MotivKriteria', 'MotivKriteria', felt_str($foto, 'MotivKriteria'), 4);

        input_textarea('Avbildet', 'Avbildet', felt_str($foto, 'Avbildet'), 3);

        // Hendelse skal POST'es (readonly, ikke disabled)
        input_textarea('Hendelse', 'Hendelse', felt_str($foto, 'Hendelse'), 2, true);

        input_checkbox('Aksesjon', 'Aksesjon', felt_bool($foto, 'Aksesjon'));
        input_text('Samling', 'Samling', felt_str($foto, 'Samling'));
        input_checkbox('Fotografi', 'Fotografi', felt_bool($foto, 'Fotografi'));
        input_text('Fotograf', 'Fotograf', felt_str($foto, 'Fotograf'));
        input_text('FotoFirma', 'FotoFirma', felt_str($foto, 'FotoFirma'));
        input_date('FotoTidFra', 'FotoTidFra', felt_str($foto, 'FotoTidFra'));
        input_date('FotoTidTil', 'FotoTidTil', felt_str($foto, 'FotoTidTil'));
        input_text('FotoSted', 'FotoSted', felt_str($foto, 'FotoSted'));
        input_text('Prosess', 'Prosess', felt_str($foto, 'Prosess'));

        input_text('ReferNeg', 'ReferNeg', felt_str($foto, 'ReferNeg'));
        input_text('ReferFArk', 'ReferFArk', felt_str($foto, 'ReferFArk'));
        input_text('Plassering', 'Plassering', felt_str($foto, 'Plassering'));
        input_checkbox('Svarthvitt', 'Svarthvitt', felt_bool($foto, 'Svarthvitt'));

        input_text('Status', 'Status', felt_str($foto, 'Status'));
        input_text('Tilstand', 'Tilstand', felt_str($foto, 'Tilstand'));
        input_checkbox('FriKopi', 'FriKopi', felt_bool($foto, 'FriKopi'));

        // Feltliste: UUID ikke synlig, men må persisteres
        ?>
        <input type="hidden" id="UUID" name="UUID" value="<?= h(felt_str($foto, 'UUID')) ?>">

        <?php
        input_textarea('Merknad', 'Merknad', felt_str($foto, 'Merknad'), 4);
        ?>

        <div class="mt-3">
            <button type="submit" id="btn-lagre" class="btn btn-primary">Lagre</button>
            <a href="primus_main.php" class="btn btn-outline-secondary ms-2">Avbryt</a>
            <button type="submit" name="cmd_kopier" value="1" class="btn btn-outline-secondary ms-2">
                Kopier foto
            </button>
        </div>
    </form>

    <?php ui_card_end(); ?>
</div>

<script>
(function () {
    // -----------------------------
    // C7: live Bilde_Fil (NSM.2009-056)
    // -----------------------------
    const serNrEl = document.getElementById('SerNr');
    const bildeEl = document.getElementById('Bilde_Fil');
    const serie = <?= json_encode($serieForLive, JSON_UNESCAPED_UNICODE) ?>;

    if (serNrEl && bildeEl && serie) {
        serNrEl.addEventListener('input', () => {
            const n = parseInt(serNrEl.value, 10);
            if (!Number.isFinite(n) || n < 1 || n > 999) return;
            bildeEl.value = serie + '-' + String(n).padStart(3, '0');
        });
    }

    // -----------------------------
    // Feltstyring via foto_state.php
    // -----------------------------
    function oppdaterFotoState() {
        const valgt = document.querySelector('input[name="fmeHendelse"]:checked');
        if (!valgt) return;

        fetch('/nmmprimus/modules/foto/api/foto_state.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: 'fmeHendelse=' + encodeURIComponent(valgt.value)
        })
        .then(r => r.json())
        .then(json => {
            if (!json || !json.ok) return;
            const data = json.data || {};

            // enable/disable + rød/grønn merking
            Object.entries(data.felter || {}).forEach(([id, enabled]) => {
                const el = document.getElementById(id);
                if (!el) return;

                // Rød/grønn merking
                el.classList.remove('foto-styrt-aktiv', 'foto-styrt-inaktiv');
                el.classList.add(enabled ? 'foto-styrt-aktiv' : 'foto-styrt-inaktiv');

                // Hendelse: readonly, aldri disabled (må POST'es)
                if (id === 'Hendelse') {
                    el.readOnly = true;
                    el.disabled = false;
                    return;
                }

                el.disabled = !enabled;
            });

            // tøm
            (data.skalTommes || []).forEach(id => {
                if (id === 'Hendelse') return;
                const el = document.getElementById(id);
                if (!el) return;

                if (el.type === 'checkbox') el.checked = false;
                else el.value = '';
            });

            // avledede verdier
            Object.entries(data.verdier || {}).forEach(([id, value]) => {
                const el = document.getElementById(id);
                if (!el) return;

                if (el.type === 'checkbox') el.checked = !!Number(value);
                else el.value = (value ?? '');
            });
        });
    }

    // initial + on change
    document.querySelectorAll('input[name="fmeHendelse"]').forEach(r => {
        r.addEventListener('change', oppdaterFotoState);
    });
    oppdaterFotoState();

    // -----------------------------
    // KRITISK: disabled controls POST'es ikke.
    // Robust løsning: preventDefault -> enable -> programmatisk submit.
    // -----------------------------
    const form = document.getElementById('foto-form');
    if (form) {
        form.addEventListener('submit', (e) => {
            const submitter = e.submitter;
            const erKopier = submitter && submitter.name === 'cmd_kopier';
            if (erKopier) return;

            e.preventDefault();

            form.querySelectorAll(':disabled').forEach(el => {
                el.disabled = false;
            });

            HTMLFormElement.prototype.submit.call(form);
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../../includes/layout_slutt.php'; ?>
