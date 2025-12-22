<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/layout_start.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../primus/primus_modell.php';

require_login();

$fotoId = filter_input(INPUT_GET, 'Foto_ID', FILTER_VALIDATE_INT);
$ret    = (string)($_GET['ret'] ?? '');

if (!$fotoId || $ret === '') {
    redirect('/nmmprimus/modules/primus/primus_main.php');
}

$sok = trim((string)($_GET['sok'] ?? ''));
$liste = primus_hent_skip_liste($sok);

?>
<div class="container-fluid">
    <h1>Velg fartøy</h1>

    <div class="card">
        <div class="card-body">

            <form method="get" class="mb-3" style="display:flex; gap:12px; align-items:end;">
                <input type="hidden" name="Foto_ID" value="<?= h((string)$fotoId) ?>">
                <input type="hidden" name="ret" value="<?= h($ret) ?>">

                <div>
                    <label for="sok">Søk fartøynavn</label><br>
                    <input type="text"
                           id="sok"
                           name="sok"
                           value="<?= h($sok) ?>"
                           style="max-width:40ch; width:100%;"
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
                <div style="
                    max-height: 25em;
                    overflow-y: auto;
                    border: 1px solid #ddd;
                ">

                    <table class="table table-hover table-sm" style="margin-bottom:0;">
                        <thead style="position: sticky; top: 0; background: #fff; z-index: 1;">
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

                            $url = $ret
                                . (str_contains($ret, '?') ? '&' : '?')
                                . 'add_nmm_id=' . rawurlencode($id);
                            ?>
                            <tr>
                                <td><?= h($fty) ?></td>
                                <td><?= h($fna) ?></td>
                                <td><?= h($byg) ?></td>
                                <td><?= h($kal) ?></td>
                                <td>
                                    <a class="btn btn-success btn-sm" href="<?= h($url) ?>">
                                        Velg
                                    </a>
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
