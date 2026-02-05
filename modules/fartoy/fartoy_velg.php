<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../primus/primus_modell.php';

require_login();

// Håndter både eksisterende foto (Foto_ID) og nye foto (ny_rad)
$fotoId = filter_input(INPUT_GET, 'Foto_ID', FILTER_VALIDATE_INT);
$nyRad = isset($_GET['ny_rad']) && (int)$_GET['ny_rad'] === 1;
$ret    = (string)($_GET['ret'] ?? '');
$mode   = (string)($_GET['mode'] ?? '');

// Minst én av $fotoId eller $nyRad må være satt
if ((!$fotoId && !$nyRad) || $ret === '') {
    redirect(BASE_URL . '/modules/primus/primus_main.php');
}

$sok = trim((string)($_GET['sok'] ?? ''));
$liste = primus_hent_skip_liste($sok);

require_once __DIR__ . '/../../includes/layout_start.php';
?>
<div class="container-fluid">
    <h1>Velg fartøy</h1>

    <div class="card">
        <div class="card-body">

            <form method="get" class="mb-3 flex-row-end">
                <?php if ($nyRad): ?>
                    <input type="hidden" name="ny_rad" value="1">
                <?php else: ?>
                    <input type="hidden" name="Foto_ID" value="<?= h((string)$fotoId) ?>">
                <?php endif; ?>
                <input type="hidden" name="ret" value="<?= h($ret) ?>">
                <?php if ($mode !== ''): ?>
                    <input type="hidden" name="mode" value="<?= h($mode) ?>">
                <?php endif; ?>

                <div>
                    <label for="sok">Søk fartøynavn</label><br>
                    <input type="text"
                           id="sok"
                           name="sok"
                           value="<?= h($sok) ?>"
                           class="max-w-40ch"
                           autofocus>
                </div>

                <div>
                    <button class="btn btn-primary" type="submit">Søk</button>
                    <a class="btn btn-secondary" href="<?= h($ret) ?>">Tilbake</a>
                </div>
            </form>

            <?php if (empty($liste)): ?>
                <p>Ingen fartøy funnet.</p>
            <?php else: ?>

                <!-- Scroll-container: maks 25 rader -->
                <div class="table-scroll-container">

                    <table class="table table-hover table-sm table-sticky-header">
                        <thead>
                        <tr>
                            <th>Type</th>
                            <th>Navn</th>
                            <th>Bygd</th>
                            <th>Kallesignal</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($liste as $r): ?>
                            <?php
                            $id  = (string)($r['NMM_ID'] ?? '');
                            $fty = (string)($r['FTY'] ?? '');
                            $fna = (string)($r['FNA'] ?? '');
                            $byg = (string)($r['BYG'] ?? '');
                            $kal = (string)($r['KAL'] ?? '');

                            // Use appropriate parameter name based on mode
                            $param = ($mode === 'add_avbildet') ? 'add_avbildet_nmm_id' : 'add_nmm_id';

                            // Build return URL - handle both relative and absolute paths
                            $returnUrl = $ret;
                            if (!str_starts_with($ret, 'http') && !str_starts_with($ret, '/')) {
                                // Relative path - prepend ../primus/
                                $returnUrl = '../primus/' . $ret;
                            }
                            ?>
                            <tr>
                                <td><?= h($fty) ?></td>
                                <td><?= h($fna) ?></td>
                                <td><?= h($byg) ?></td>
                                <td><?= h($kal) ?></td>
                                <td>
                                    <!-- SIKKERHET: Bruk POST for state-endringer -->
                                    <form method="post" action="<?= h($returnUrl) ?>" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="<?= h($param) ?>" value="<?= h($id) ?>">
                                        <?php if ($nyRad): ?>
                                            <input type="hidden" name="ny_rad" value="1">
                                        <?php else: ?>
                                            <input type="hidden" name="Foto_ID" value="<?= h((string)$fotoId) ?>">
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-success btn-sm">
                                            Velg
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>

                <div class="mt-2 text-muted">
                    Viser inntil 25 rader. Scroll for flere treff.
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_slutt.php'; ?>
