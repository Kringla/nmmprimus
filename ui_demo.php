<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/ui.php';
require_once __DIR__ . '/config/constants.php';

require_login();

$pageTitle = 'UI-demo';
require_once __DIR__ . '/includes/layout_start.php';

ui_card_start('Varsler');
ui_alert('info', 'Dette er en informasjonsmelding.');
ui_alert('success', 'Dette er en suksessmelding.');
ui_alert('error', 'Dette er en feilmelding.');
ui_card_end();

ui_card_start('Eksempeltabell');
ui_table_start(['Kolonne A', 'Kolonne B', 'Kolonne C']);
echo '<tr><td>Rad 1</td><td>Verdi</td><td>123</td></tr>';
echo '<tr><td>Rad 2</td><td>Verdi</td><td>456</td></tr>';
ui_table_end();
ui_card_end();

ui_card_start('Tom tilstand');
ui_empty('Ingen treff.');
ui_card_end();

require_once __DIR__ . '/includes/layout_slutt.php';
