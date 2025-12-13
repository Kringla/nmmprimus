<?php
declare(strict_types=1);

/**
 * functions.php
 *
 * Generelle hjelpefunksjoner (escaping, redirect, etc.).
 */

/**
 * HTML-escape.
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
