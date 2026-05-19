<?php
declare(strict_types=1);

/**
 * statistikk.php
 *
 * Statistikkoversikt for nmmfoto:
 * - Totalt antall rader, herav Transferred = 1
 * - Antall per bildeserie
 * - Tidsfilter: antall per serie i et brukerdefinert datetime-intervall
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/primus_modell.php';

require_login();

// ----- Tidsfilter-prosessering -----
$tidsresultat  = null;
$filtFra       = '';
$filtTil       = '';
$filtFelt      = 'Opprettet_Tid';
$filtFeil      = '';

if (is_post()) {
    if (!csrf_validate()) {
        die('Ugyldig forespørsel');
    }

    $filtFra  = trim((string)($_POST['filt_fra']  ?? ''));
    $filtTil  = trim((string)($_POST['filt_til']  ?? ''));
    $filtFelt = (string)($_POST['filt_felt'] ?? 'Opprettet_Tid');

    // Konverter datetime-local (YYYY-MM-DDTHH:MM) til MySQL-format (YYYY-MM-DD HH:MM:SS)
    $fraDb = str_replace('T', ' ', $filtFra) . ':00';
    $tilDb = str_replace('T', ' ', $filtTil) . ':59';

    if ($filtFra === '' || $filtTil === '') {
        $filtFeil = 'Begge datoer må fylles ut.';
    } elseif ($fraDb > $tilDb) {
        $filtFeil = 'Fra-tidspunkt kan ikke være etter Til-tidspunkt.';
    } else {
        $tidsresultat = primus_hent_statistikk_i_tidsrom($fraDb, $tilDb, $filtFelt);
    }
}

// ----- Statistikk (alltid) -----
$serieStats = primus_hent_statistikk_per_serie();

// Skill ut totalrad (ROLLUP gir Serie = null som siste rad)
$totalRad = null;
$serieRader = [];
foreach ($serieStats as $rad) {
    if ($rad['Serie'] === null) {
        $totalRad = $rad;
    } else {
        $serieRader[] = $rad;
    }
}

// Skill ut tidsrom-totalrad
$tidsTotal = null;
$tidsRader = [];
if ($tidsresultat !== null) {
    foreach ($tidsresultat as $rad) {
        if ($rad['Serie'] === null) {
            $tidsTotal = $rad;
        } else {
            $tidsRader[] = $rad;
        }
    }
}

$pageTitle = 'Statistikk – nmmfoto';
require_once __DIR__ . '/../../includes/layout_start.php';
?>

<div style="max-width: 700px;">

    <p style="margin-bottom: 1.5rem;">
        <a href="<?= base_url(); ?>/modules/primus/primus_main.php">&larr; Tilbake til oversikt</a>
    </p>

    <!-- ===== GLOBAL OVERSIKT ===== -->
    <section style="margin-bottom: 2rem;">
        <h2 style="margin-bottom: 0.75rem;">Oversikt</h2>
        <?php if ($totalRad): ?>
        <table class="data-table" style="width: auto;">
            <thead>
                <tr>
                    <th>Nøkkeltall</th>
                    <th style="text-align: right;">Antall</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Totalt antall rader i nmmfoto</td>
                    <td style="text-align: right;"><?= h((string)$totalRad['Antall']); ?></td>
                </tr>
                <tr>
                    <td>Herav Transferred = 1</td>
                    <td style="text-align: right;"><?= h((string)$totalRad['Transferred']); ?></td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <!-- ===== PER SERIE ===== -->
    <section style="margin-bottom: 2.5rem;">
        <h2 style="margin-bottom: 0.75rem;">Per bildeserie</h2>
        <table class="data-table" style="width: auto;">
            <thead>
                <tr>
                    <th>Serie</th>
                    <th style="text-align: right;">Antall</th>
                    <th style="text-align: right;">Transferred</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($serieRader as $rad): ?>
                <tr>
                    <td><?= h((string)$rad['Serie']); ?></td>
                    <td style="text-align: right;"><?= h((string)$rad['Antall']); ?></td>
                    <td style="text-align: right;"><?= h((string)$rad['Transferred']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <?php if ($totalRad): ?>
            <tfoot>
                <tr style="font-weight: bold;">
                    <td>Totalt</td>
                    <td style="text-align: right;"><?= h((string)$totalRad['Antall']); ?></td>
                    <td style="text-align: right;"><?= h((string)$totalRad['Transferred']); ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </section>

    <!-- ===== TIDSFILTER ===== -->
    <section>
        <h2 style="margin-bottom: 0.75rem;">Tidsfilter</h2>
        <form method="post" action="">
            <?= csrf_field(); ?>

            <div style="display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 1rem;">

                <div style="display: flex; align-items: center; gap: 1rem;">
                    <label style="min-width: 4rem;">Felt:</label>
                    <label>
                        <input type="radio" name="filt_felt" value="Opprettet_Tid"
                            <?= ($filtFelt === 'Opprettet_Tid') ? 'checked' : ''; ?>>
                        Opprettet_Tid
                    </label>
                    <label>
                        <input type="radio" name="filt_felt" value="Oppdatert_Tid"
                            <?= ($filtFelt === 'Oppdatert_Tid') ? 'checked' : ''; ?>>
                        Oppdatert_Tid
                    </label>
                </div>

                <div style="display: flex; align-items: center; gap: 1rem;">
                    <label for="filt_fra" style="min-width: 4rem;">Fra:</label>
                    <input type="datetime-local" id="filt_fra" name="filt_fra"
                           value="<?= h($filtFra); ?>"
                           style="font-family: inherit;">
                </div>

                <div style="display: flex; align-items: center; gap: 1rem;">
                    <label for="filt_til" style="min-width: 4rem;">Til:</label>
                    <input type="datetime-local" id="filt_til" name="filt_til"
                           value="<?= h($filtTil); ?>"
                           style="font-family: inherit;">
                </div>

            </div>

            <button type="submit" class="btn btn-primary btn-sm">Vis statistikk</button>
        </form>

        <?php if ($filtFeil !== ''): ?>
            <p style="color: red; margin-top: 0.75rem;"><?= h($filtFeil); ?></p>
        <?php endif; ?>

        <?php if ($tidsresultat !== null && $filtFeil === ''): ?>
        <div style="margin-top: 1.25rem;">
            <table class="data-table" style="width: auto;">
                <thead>
                    <tr>
                        <th>Serie</th>
                        <th style="text-align: right;">Antall i tidsrommet</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tidsRader)): ?>
                    <tr>
                        <td colspan="2" style="color: #666; font-style: italic;">
                            Ingen rader funnet i dette tidsrommet.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($tidsRader as $rad): ?>
                        <tr>
                            <td><?= h((string)$rad['Serie']); ?></td>
                            <td style="text-align: right;"><?= h((string)$rad['Antall']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if ($tidsTotal && !empty($tidsRader)): ?>
                <tfoot>
                    <tr style="font-weight: bold;">
                        <td>Totalt</td>
                        <td style="text-align: right;"><?= h((string)$tidsTotal['Antall']); ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
        <?php endif; ?>
    </section>

</div>

<?php require_once __DIR__ . '/../../includes/layout_slutt.php'; ?>
