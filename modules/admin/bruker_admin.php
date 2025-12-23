<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/user_functions.php';
require_once __DIR__ . '/../../config/constants.php';

require_login();

$cu = current_user();

// Kun admins har tilgang
if ($cu['role'] !== 'admin') {
    redirect(BASE_URL . '/modules/primus/primus_main.php');
}

$melding = '';
$feilmelding = '';

// --------------------------------------------------
// POST: Opprett ny bruker
// --------------------------------------------------
if (is_post() && ($_POST['action'] ?? '') === 'opprett') {
    if (!csrf_validate()) {
        die('Ugyldig forespørsel (CSRF).');
    }

    $email = trim((string)($_POST['email'] ?? ''));
    $passord = trim((string)($_POST['passord'] ?? ''));
    $rolle = (string)($_POST['rolle'] ?? 'user');

    if ($email === '' || $passord === '') {
        $feilmelding = 'E-post og passord er påkrevd.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feilmelding = 'Ugyldig e-postadresse.';
    } elseif (strlen($passord) < 6) {
        $feilmelding = 'Passordet må være minst 6 tegn.';
    } else {
        try {
            user_opprett($email, $passord, $rolle);
            $melding = "Bruker '$email' opprettet.";
        } catch (RuntimeException $e) {
            $feilmelding = $e->getMessage();
        }
    }
}

// --------------------------------------------------
// POST: Oppdater bruker
// --------------------------------------------------
if (is_post() && ($_POST['action'] ?? '') === 'oppdater') {
    if (!csrf_validate()) {
        die('Ugyldig forespørsel (CSRF).');
    }

    $userId = filter_var($_POST['user_id'] ?? '', FILTER_VALIDATE_INT);
    $email = trim((string)($_POST['email'] ?? ''));
    $rolle = (string)($_POST['rolle'] ?? 'user');

    if (!$userId || $email === '') {
        $feilmelding = 'Ugyldig data.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feilmelding = 'Ugyldig e-postadresse.';
    } else {
        try {
            user_oppdater($userId, $email, $rolle);
            $melding = "Bruker oppdatert.";
        } catch (RuntimeException $e) {
            $feilmelding = $e->getMessage();
        }
    }
}

// --------------------------------------------------
// POST: Endre passord
// --------------------------------------------------
if (is_post() && ($_POST['action'] ?? '') === 'endre_passord') {
    if (!csrf_validate()) {
        die('Ugyldig forespørsel (CSRF).');
    }

    $userId = filter_var($_POST['user_id'] ?? '', FILTER_VALIDATE_INT);
    $nyttPassord = trim((string)($_POST['nytt_passord'] ?? ''));

    if (!$userId || $nyttPassord === '') {
        $feilmelding = 'Ugyldig data.';
    } elseif (strlen($nyttPassord) < 6) {
        $feilmelding = 'Passordet må være minst 6 tegn.';
    } else {
        user_endre_passord($userId, $nyttPassord);
        $melding = "Passord endret.";
    }
}

// --------------------------------------------------
// POST: Toggle aktiv/inaktiv
// --------------------------------------------------
if (is_post() && ($_POST['action'] ?? '') === 'toggle_aktiv') {
    if (!csrf_validate()) {
        die('Ugyldig forespørsel (CSRF).');
    }

    $userId = filter_var($_POST['user_id'] ?? '', FILTER_VALIDATE_INT);
    if ($userId) {
        user_toggle_aktiv($userId);
        $melding = "Brukerstatus endret.";
    }
}

// --------------------------------------------------
// POST: Slett bruker
// --------------------------------------------------
if (is_post() && ($_POST['action'] ?? '') === 'slett') {
    if (!csrf_validate()) {
        die('Ugyldig forespørsel (CSRF).');
    }

    $userId = filter_var($_POST['user_id'] ?? '', FILTER_VALIDATE_INT);
    if ($userId) {
        try {
            user_slett($userId);
            $melding = "Bruker slettet.";
        } catch (RuntimeException $e) {
            $feilmelding = $e->getMessage();
        }
    }
}

// --------------------------------------------------
// Hent alle brukere
// --------------------------------------------------
$brukere = user_hent_alle();

$pageTitle = 'Brukeradministrasjon';
require_once __DIR__ . '/../../includes/layout_start.php';
?>

<div class="container-fluid">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h1 style="margin:0;">Brukeradministrasjon</h1>
        <a href="<?= h(BASE_URL); ?>" class="btn btn-secondary">Tilbake til meny</a>
    </div>

    <?php if ($melding): ?>
        <div class="alert alert-success"><?= h($melding); ?></div>
    <?php endif; ?>

    <?php if ($feilmelding): ?>
        <div class="alert alert-danger"><?= h($feilmelding); ?></div>
    <?php endif; ?>

    <!-- Opprett ny bruker -->
    <div class="card mb-4">
        <div class="card-header" style="background: var(--blue-head);">
            <strong>Opprett ny bruker</strong>
        </div>
        <div class="card-body">
            <form method="post">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="opprett">

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="email">E-post</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="passord">Passord (min. 6 tegn)</label>
                            <input type="password" name="passord" id="passord" class="form-control" minlength="6" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="rolle">Rolle</label>
                            <select name="rolle" id="rolle" class="form-control">
                                <option value="user">Bruker</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2" style="display:flex; align-items:end;">
                        <button type="submit" class="btn btn-success btn-block">Opprett</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste over brukere -->
    <div class="card">
        <div class="card-header" style="background: var(--blue-head);">
            <strong>Eksisterende brukere</strong>
        </div>
        <div class="card-body">
            <?php if (empty($brukere)): ?>
                <p>Ingen brukere funnet.</p>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>E-post</th>
                            <th>Rolle</th>
                            <th>Status</th>
                            <th>Opprettet</th>
                            <th>Sist brukt</th>
                            <th style="text-align:right;">Handlinger</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brukere as $bruker): ?>
                            <?php
                            $uid = (int)$bruker['user_id'];
                            $isCurrentUser = ($uid === (int)$cu['user_id']);
                            ?>
                            <tr>
                                <td><?= $uid; ?></td>
                                <td>
                                    <?= h($bruker['email']); ?>
                                    <?php if ($isCurrentUser): ?>
                                        <span class="badge badge-primary">Deg</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($bruker['role'] === 'admin'): ?>
                                        <span class="badge badge-danger">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Bruker</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($bruker['IsActive']): ?>
                                        <span class="badge badge-success">Aktiv</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Inaktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= h(date('d.m.Y', strtotime($bruker['created_at']))); ?></td>
                                <td><?= $bruker['LastUsed'] ? h(date('d.m.Y H:i', strtotime($bruker['LastUsed']))) : '-'; ?></td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <!-- Rediger-knapp -->
                                    <button class="btn btn-sm btn-primary" onclick="visRedigerModal(<?= $uid; ?>, '<?= h($bruker['email']); ?>', '<?= h($bruker['role']); ?>')">
                                        Rediger
                                    </button>

                                    <!-- Endre passord -->
                                    <button class="btn btn-sm btn-warning" onclick="visPassordModal(<?= $uid; ?>, '<?= h($bruker['email']); ?>')">
                                        Passord
                                    </button>

                                    <!-- Toggle aktiv/inaktiv -->
                                    <form method="post" style="display:inline;">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="action" value="toggle_aktiv">
                                        <input type="hidden" name="user_id" value="<?= $uid; ?>">
                                        <button type="submit" class="btn btn-sm btn-info">
                                            <?= $bruker['IsActive'] ? 'Deaktiver' : 'Aktiver'; ?>
                                        </button>
                                    </form>

                                    <!-- Slett-knapp (ikke for egen bruker) -->
                                    <?php if (!$isCurrentUser): ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Er du sikker på at du vil slette denne brukeren?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="slett">
                                            <input type="hidden" name="user_id" value="<?= $uid; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Slett</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Rediger bruker -->
<div id="redigerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div style="position:relative; max-width:500px; margin:100px auto; background:#fff; padding:20px; border-radius:8px;">
        <h3>Rediger bruker</h3>
        <form method="post">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="oppdater">
            <input type="hidden" name="user_id" id="edit_user_id">

            <div class="form-group">
                <label for="edit_email">E-post</label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="edit_rolle">Rolle</label>
                <select name="rolle" id="edit_rolle" class="form-control">
                    <option value="user">Bruker</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn btn-primary">Lagre</button>
                <button type="button" class="btn btn-secondary" onclick="skjulRedigerModal()">Avbryt</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Endre passord -->
<div id="passordModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div style="position:relative; max-width:500px; margin:100px auto; background:#fff; padding:20px; border-radius:8px;">
        <h3>Endre passord for <span id="passord_email"></span></h3>
        <form method="post">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="endre_passord">
            <input type="hidden" name="user_id" id="passord_user_id">

            <div class="form-group">
                <label for="nytt_passord">Nytt passord (min. 6 tegn)</label>
                <input type="password" name="nytt_passord" id="nytt_passord" class="form-control" minlength="6" required>
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn btn-warning">Endre passord</button>
                <button type="button" class="btn btn-secondary" onclick="skjulPassordModal()">Avbryt</button>
            </div>
        </form>
    </div>
</div>

<script>
function visRedigerModal(userId, email, rolle) {
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_rolle').value = rolle;
    document.getElementById('redigerModal').style.display = 'block';
}

function skjulRedigerModal() {
    document.getElementById('redigerModal').style.display = 'none';
}

function visPassordModal(userId, email) {
    document.getElementById('passord_user_id').value = userId;
    document.getElementById('passord_email').textContent = email;
    document.getElementById('nytt_passord').value = '';
    document.getElementById('passordModal').style.display = 'block';
}

function skjulPassordModal() {
    document.getElementById('passordModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../../includes/layout_slutt.php'; ?>
