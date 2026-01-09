<?php
declare(strict_types=1);

/**
 * API – sett_session.php
 * 
 * Setter tillatte session-verdier.
 * SIKKERHET: Kun whitelistede nøkler tillates.
 */

require_once __DIR__ . '/../../../includes/auth.php';

// Krev innlogging for å manipulere session
require_login();

header('Content-Type: application/json; charset=utf-8');

// Whitelist av tillatte session-nøkler
$tillatte = [
    'primus_tab',      // Aktiv fane i detalj-visning
    'primus_h2',       // H2-modus (kandidatpanel aktivt)
    'primus_iCh',      // Hendelsesmodus
    'primus_k_sok',    // Kandidat-søk (Søk i fartøynavn)
];

$oppdatert = [];

foreach ($_POST as $key => $value) {
    // Kun tillatte nøkler
    if (!in_array($key, $tillatte, true)) {
        continue;
    }
    
    // Sanitering basert på nøkkel
    switch ($key) {
        case 'primus_tab':
            // Kun tillatte fane-verdier
            if (in_array($value, ['motiv', 'historikk', 'ovrige'], true)) {
                $_SESSION[$key] = $value;
                $oppdatert[] = $key;
            }
            break;
            
        case 'primus_h2':
        case 'primus_iCh':
            // Kun heltall
            $intVal = filter_var($value, FILTER_VALIDATE_INT);
            if ($intVal !== false) {
                $_SESSION[$key] = $intVal;
                $oppdatert[] = $key;
            }
            break;

        case 'primus_k_sok':
            // Kandidat-søk: lagres som en kort streng (trimmet, maks 200 tegn).
            $s = trim((string)$value);
            if ($s === '') {
                // Tøm session-nøkkelen om tom streng
                unset($_SESSION[$key]);
                $oppdatert[] = $key;
            } else {
                $_SESSION[$key] = mb_substr($s, 0, 200);
                $oppdatert[] = $key;
            }
            break;
    }
}

echo json_encode([
    'ok' => true,
    'updated' => $oppdatert
], JSON_UNESCAPED_UNICODE);
