<?php
declare(strict_types=1);

/**
 * functions.php
 *
 * Generelle hjelpefunksjoner (escaping, redirect, CSRF, etc.).
 */

/**
 * HTML-escape.
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Generer CSRF-token og lagre i session.
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Valider CSRF-token fra POST.
 */
function csrf_validate(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    $token = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    
    if ($token === '' || $sessionToken === '') {
        return false;
    }
    
    return hash_equals($sessionToken, $token);
}

/**
 * Output CSRF hidden input field.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

/**
 * Sjekk om request er POST.
 */
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/**
 * Redirect og avslutt.
 */
function redirect(string $path): void
{
    // Absolutt eller relativ URL – her antar vi relativ sti innen nettstedet.
    header('Location: ' . $path);
    exit;
}

/**
 * Trim og normaliser e-post.
 */
function normalize_email(string $email): string
{
    return mb_strtolower(trim($email));
}

/**
 * Hent felt fra $_POST med trimming.
 */
function post_string(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

/**
 * Hent BASE_URL som JSON-safe string for JavaScript.
 */
function base_url_js(): string
{
    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
    return json_encode($baseUrl, JSON_UNESCAPED_SLASHES);
}
