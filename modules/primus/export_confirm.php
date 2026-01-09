<?php
declare(strict_types=1);

/**
 * export_confirm.php
 *
 * Admin confirmation page after Excel export.
 * Allows admin to confirm and mark exported records as Transferred = True.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/ui.php';
require_once __DIR__ . '/primus_modell.php';

// Only admins can access this page
require_admin();

// Get export data from session
$fotoIds = $_SESSION['export_foto_ids'] ?? [];
$serie = $_SESSION['export_serie'] ?? '';
$serNrFra = $_SESSION['export_sernr_fra'] ?? 0;
$serNrTil = $_SESSION['export_sernr_til'] ?? 0;
$count = $_SESSION['export_count'] ?? 0;

// If no export data, redirect to main page
if (empty($fotoIds)) {
    redirect(BASE_URL . '/modules/primus/primus_main.php');
}

$message = '';
$messageType = '';

// Handle confirmation
if (is_post()) {
    if (!csrf_validate()) {
        die('Ugyldig forespørsel (CSRF)');
    }

    $action = post_string('action');

    if ($action === 'confirm') {
        // Mark all exported photos as Transferred = True
        $success = primus_marker_som_transferred($fotoIds);

        if ($success) {
            $message = 'Suksess! ' . count($fotoIds) . ' foto markert som overført.';
            $messageType = 'success';
            // Clear session data
            unset($_SESSION['export_foto_ids']);
            unset($_SESSION['export_serie']);
            unset($_SESSION['export_sernr_fra']);
            unset($_SESSION['export_sernr_til']);
            unset($_SESSION['export_count']);
        } else {
            $message = 'Feil: Kunne ikke oppdatere foto i databasen.';
            $messageType = 'error';
        }
    } elseif ($action === 'cancel') {
        // Just clear session without updating
        unset($_SESSION['export_foto_ids']);
        unset($_SESSION['export_serie']);
        unset($_SESSION['export_sernr_fra']);
        unset($_SESSION['export_sernr_til']);
        unset($_SESSION['export_count']);
        $message = 'Eksport avbrutt. Ingen endringer lagret.';
        $messageType = 'info';
    }
}

$pageTitle = 'Bekreft eksport';
require_once __DIR__ . '/../../includes/layout_start.php';
?>

<h1>Bekreft eksport til Excel</h1>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= h($message) ?>
    </div>

    <?php if ($messageType === 'success' || $messageType === 'info'): ?>
        <p><a href="primus_main.php" class="btn btn-primary">Tilbake til Primus</a></p>
    <?php endif; ?>

<?php else: ?>

    <?php ui_card_start('Eksportinformasjon'); ?>

    <table class="info-table">
        <tr>
            <th>Serie:</th>
            <td><?= h($serie) ?></td>
        </tr>
        <tr>
            <th>SerNr område:</th>
            <td><?= h((string)$serNrFra) ?> - <?= h((string)$serNrTil) ?></td>
        </tr>
        <tr>
            <th>Antall eksporterte foto:</th>
            <td><?= h((string)$count) ?></td>
        </tr>
    </table>

    <div class="export-confirm-info">
        <p><strong>Eksporten er fullført og filen er lastet ned.</strong></p>
        <p>Hvis du bekrefter at eksporten var vellykket, vil alle de <?= h((string)$count) ?> eksporterte fotoene bli markert som "Overført" i databasen.</p>
        <p><strong>Viktig:</strong> Kontroller at Excel-filen ble lastet ned korrekt før du bekrefter.</p>
    </div>

    <div class="export-confirm-buttons">
        <form method="post" class="inline-form-with-margin">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="confirm">
            <button type="submit" class="btn btn-success btn-lg">
                ✓ Bekreft eksport og marker som overført
            </button>
        </form>

        <form method="post" class="inline-form">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="cancel">
            <button type="submit" class="btn btn-secondary btn-lg"
                    onclick="return confirm('Er du sikker på at du vil avbryte? Ingen endringer vil bli lagret.');">
                ✗ Avbryt (ikke marker som overført)
            </button>
        </form>
    </div>

    <?php ui_card_end(); ?>

<?php endif; ?>

<style>
.info-table {
    width: 100%;
    max-width: 600px;
    margin-bottom: 20px;
    border-collapse: collapse;
}
.info-table th {
    text-align: left;
    padding: 8px 12px;
    background: #f7f9fc;
    border: 1px solid #dbe3ef;
    width: 200px;
}
.info-table td {
    padding: 8px 12px;
    border: 1px solid #dbe3ef;
}
.export-confirm-info {
    background: #e8f4ff;
    border: 1px solid #3585fe;
    border-radius: 4px;
    padding: 16px;
    margin: 20px 0;
}
.export-confirm-info p {
    margin: 8px 0;
}
.export-confirm-buttons {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #dbe3ef;
}
.alert {
    padding: 16px;
    margin-bottom: 20px;
    border-radius: 4px;
    border: 1px solid;
}
.alert-success {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}
.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}
.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border-color: #bee5eb;
}
.btn-lg {
    padding: 12px 24px;
    font-size: 16px;
}
</style>

<?php require_once __DIR__ . '/../../includes/layout_slutt.php'; ?>
