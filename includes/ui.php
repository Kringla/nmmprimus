<?php
declare(strict_types=1);

/**
 * ui.php
 *
 * Små, gjenbrukbare UI-hjelpere.
 * Kun presentasjon – ingen logikk, ingen DB.
 */

function ui_card_start(?string $title = null): void
{
    echo '<div class="card">';
    if ($title !== null) {
        echo '<div class="card-header">' . h($title) . '</div>';
    }
    echo '<div class="card-body">';
}

function ui_card_end(): void
{
    echo '</div></div>';
}

function ui_alert(string $type, string $message): void
{
    $class = 'alert-info';
    if ($type === 'success') {
        $class = 'alert-success';
    } elseif ($type === 'error') {
        $class = 'alert-error';
    }

    echo '<div class="alert ' . $class . '">';
    echo h($message);
    echo '</div>';
}

function ui_table_start(array $headers): void
{
    echo '<table><thead><tr>';
    foreach ($headers as $h) {
        echo '<th>' . h((string)$h) . '</th>';
    }
    echo '</tr></thead><tbody>';
}

function ui_table_end(): void
{
    echo '</tbody></table>';
}

function ui_empty(string $message = 'Ingen data å vise.'): void
{
    echo '<p class="text-muted">' . h($message) . '</p>';
}
