<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/constants.php';

// Krev innlogging for å se hovedsiden
require_login();

$cu = current_user();
$pageTitle = 'NMMPrimus - Oversikt';
$redirectTarget = BASE_URL . '/modules/primus/primus_main.php';
$isAdmin = ($cu['role'] === 'admin');

// Automatisk videresending for vanlige brukere (ikke admins)
if (!$isAdmin) {
    header('Refresh: 3; url=' . $redirectTarget);
}

require_once __DIR__ . '/includes/layout_start.php';
?>

<h1>Velkommen til NMMPrimus</h1>

<p>
    Du er innlogget som
    <strong><?= h($cu['email']); ?></strong>
    med rolle
    <strong><?= h($cu['role']); ?></strong>.
</p>

<?php if ($isAdmin): ?>
    <!-- Admin-meny -->
    <div class="card max-w-600">
        <div class="card-header card-header-blue">
            <strong>Administratormeny</strong>
        </div>
        <div class="card-body">
            <div class="flex-col-gap">
                <a href="<?= h($redirectTarget); ?>" class="btn btn-primary btn-lg">
                    Gå til Primus
                </a>
                <a href="<?= h(BASE_URL . '/modules/admin/bruker_admin.php'); ?>" class="btn btn-secondary btn-lg">
                    Brukeradministrasjon
                </a>
                <a href="<?= h(BASE_URL . '/logout.php'); ?>" class="btn btn-outline-secondary">
                    Logg ut
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Vanlig bruker: auto-redirect -->
    <p>
        Du sendes automatisk videre om 3 sekunder. Hvis ikke, klikk
        <a href="<?= h($redirectTarget); ?>">her for å åpne primus_main.php</a>.
    </p>

    <script>
        (function () {
            const redirectTarget = <?= json_encode($redirectTarget, JSON_UNESCAPED_SLASHES); ?>;
            setTimeout(function () {
                window.location.href = redirectTarget;
            }, 3000);
        })();
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/layout_slutt.php'; ?>
