<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/layout_start.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/ui.php';
require_once __DIR__ . '/../foto/foto_modell.php';

$db = db();

// --------------------------------------------------
// INPUT
// --------------------------------------------------
$fotoId = filter_input(INPUT_GET, 'Foto_ID', FILTER_VALIDATE_INT);
if (!$fotoId) {
    redirect('primus_main.php');
}

// --------------------------------------------------
// POST: lagre foto
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $data['Foto_ID'] = $fotoId;

    foto_lagre($db, $data);
    redirect('primus_detalj.php?Foto_ID=' . $fotoId);
}

// --------------------------------------------------
// HENT AKTIVT FOTO
// --------------------------------------------------
$foto = foto_hent_en($db, $fotoId);
if (!$foto) {
    redirect('primus_main.php');
}
?>

<div class="container">

    <h1>Primus – foto</h1>

    <div class="row">

        <!-- ================================
             VENSTRE: Kandidater
        ================================= -->
        <div class="col-md-4">
            <?php ui_card_start('Kandidater'); ?>
            <div id="kandidatpanel"></div>
            <?php ui_card_end(); ?>
        </div>

        <!-- ================================
             HØYRE: Foto
        ================================= -->
        <div class="col-md-8">
            <?php ui_card_start('Fotoopplysninger'); ?>

            <form method="post" id="foto-form">

                <?php
                // --------------------------------------------------
                // FOTO-FELTER (MOTIV + BILDEHISTORIKK)
                // --------------------------------------------------
                require __DIR__ . '/../foto/foto_arbeidsflate.php';
                ?>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        Lagre
                    </button>
                </div>

<script>
/* =========================================================
 * Hendelser / iCh – feilsøkingsvennlig versjon
 * ========================================================= */

function oppdaterFotoState() {

    const valgt = document.querySelector('input[name="fmeHendelse"]:checked');
    if (!valgt) {
        console.warn('oppdaterFotoState: ingen valgt fmeHendelse');
        return;
    }

    console.log('oppdaterFotoState – valgt:', valgt.value);

    fetch('/nmmprimus/modules/foto/api/foto_state.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'fmeHendelse=' + encodeURIComponent(valgt.value)
    })
    .then(r => {
        console.log('foto_state HTTP status:', r.status);
        return r.text();
    })
    .then(txt => {
        console.log('foto_state raw response:', txt);

        let json;
        try {
            json = JSON.parse(txt);
        } catch (e) {
            console.error('foto_state ga ikke gyldig JSON');
            return;
        }

        console.log('foto_state parsed:', json);

        if (!json.ok) {
            console.warn('foto_state ok=false');
            return;
        }

        const data = json.data;

        // Enable / disable felter
        Object.entries(data.felter || {}).forEach(([id, enabled]) => {
            const el = document.getElementById(id);
            if (!el) {
                console.debug('Fant ikke felt i DOM:', id);
                return;
            }
            el.disabled = !enabled;
            el.style.outline = enabled ? '2px solid green' : '2px solid red'; // midlertidig synliggjøring
        });

        // Tøm felter
        (data.skalTommes || []).forEach(id => {
            const el = document.getElementById(id);
            if (!el) {
                console.debug('Kan ikke tømme – finnes ikke:', id);
                return;
            }
            if (el.type === 'checkbox') el.checked = false;
            else el.value = '';
        });

        // Sett avledede verdier
        Object.entries(data.verdier || {}).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (!el) {
                console.debug('Kan ikke sette verdi – finnes ikke:', id);
                return;
            }
            if (el.type === 'checkbox') {
                el.checked = !!Number(value);
            } else {
                el.value = value;
            }
        });
    });
}

// Bind eksplisitt på radioer (robust)
document.addEventListener('DOMContentLoaded', () => {

    document
        .querySelectorAll('input[name="fmeHendelse"]')
        .forEach(r => r.addEventListener('click', oppdaterFotoState));

    oppdaterFotoState();
});
</script>

            </form>

            <?php ui_card_end(); ?>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_slutt.php'; ?>
