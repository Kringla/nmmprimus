<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/constants.php';

// Krev innlogging for å se hovedsiden
require_login();

$cu = current_user();
$pageTitle = 'NMMPrimus – Oversikt';

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
    I neste trinn legger vi inn søk, visning og moduler fra NMMPrimus-databasen.
</p>

<?php require_once __DIR__ . '/includes/layout_slutt.php'; ?>
