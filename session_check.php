<?php
/**
 * session_check.php
 * Legg denne filen i roten av hvert repo og inkluder den
 * øverst i repoets index.php:
 *
 *   require_once 'session_check.php';
 *
 * Hvis brukeren kom fra portalen er session allerede satt
 * og de slipper rett inn. Ellers videresendes de til
 * repoets egen loginside.
 */

// Portal-SSO gjelder kun i produksjon på skipsweb.no (HTTPS).
// Lokalt (XAMPP/localhost) hopper vi over denne sjekken og lar
// includes/auth.php sin egen session-innlogging styre tilgang,
// ellers oppstår en redirect-loop mot login.php (authenticated
// blir aldri satt når cookie-domenet/HTTPS ikke matcher).
$host = $_SERVER['HTTP_HOST'] ?? '';

if (str_contains($host, 'skipsweb.no')) {
    session_set_cookie_params(['domain' => '.skipsweb.no', 'path' => '/', 'secure' => true, 'httponly' => true]);
    session_start();

    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        // Ikke innlogget via portal — send til repoets egen login
        header('Location: login.php');
        exit;
    }
}
