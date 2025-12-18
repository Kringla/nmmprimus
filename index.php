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

// Automatisk videresending til hovedsiden etter 5 sekunder
header('Refresh: 5; url=' . $redirectTarget);

require_once __DIR__ . '/includes/layout_start.php';
?>

<h1>Velkommen til NMMPrimus</h1>

<p>
    Du er innlogget som
    <strong><?= h($cu['email']); ?></strong>
    med rolle
    <strong><?= h($cu['role']); ?></strong>.
</p>

<p>
    Dette er grunnversjonen av systemet.
    I neste trinn legger vi inn sok, visning og moduler fra NMMPrimus-databasen.
</p>

<p>
    Du sendes automatisk videre om 5 sekunder. Hvis ikke, klikk
    <a href="<?= h($redirectTarget); ?>">her for å åpne primus_main.php</a>.
</p>

<script>
    (function () {
        const redirectTarget = <?= json_encode($redirectTarget, JSON_UNESCAPED_SLASHES); ?>;
        setTimeout(function () {
            window.location.href = redirectTarget;
        }, 5000);
    })();
</script>

<?php require_once __DIR__ . '/includes/layout_slutt.php'; ?>
