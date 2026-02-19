<?php
declare(strict_types=1);

/**
 * layout_start.php
 *
 * Felles HTML-start for alle sider i NMMPrimus.
 * BASE_URL kommer fra config/constants.php.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/constants.php';

$cu = current_user();

// Default tittel hvis ingen er satt
if (!isset($pageTitle)) {
    $pageTitle = 'NMMPrimus';
}
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="utf-8">
    <title><?= h($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Global CSS -->
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/app.css">
</head>

<body>

<header class="site-header">
    <div class="site-header-top">
        <div class="container site-header-row">
            <div class="site-brand" >
                <a class="site-brand-title" href="<?= BASE_URL; ?>/index.php"style="margin-left: 2ch";>NMMPrimus</a>
            </div>
            <nav class="site-nav">
                <?php if ($cu): ?>
                    <span class="nav-user">
                        <?= h((string)$cu['email']); ?> (<?= h((string)$cu['role']); ?>)
                    </span>
                    <a class="btn btn-link nav-cta" href="<?= BASE_URL; ?>/logout.php">Logg ut</a>
                <?php else: ?>
                    <a class="btn btn-link nav-cta" href="<?= BASE_URL; ?>/login.php">Logg inn</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
    <?php if (empty($pageHideSubtitleBar)): ?>
    <div class="site-header-bottom">
        <div class="container site-header-bottom-inner">
            <div class="site-lockup"><?= h($pageTitle); ?></div>
        </div>
    </div>
    <?php endif; ?>
</header>

<main class="page-main">
    <div class="container">
