<?php
declare(strict_types=1);

/**
 * auth.php
 *
 * Autentisering og sesjonshåndtering for NMMPrimus.
 *
 * - Session-basert innlogging
 * - "Remember me" via vedvarende cookie + token-tabell
 *
 * Forutsetter at config/constants.php definerer BASE_URL.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/constants.php';  // BASE_URL

// Start session hvis ikke startet
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Konfigurasjon for "remember me".
 * (Ligger her for å unngå endringer i config/.)
 */
const REMEMBER_COOKIE_NAME = 'nmmprimus_remember';
const REMEMBER_DAYS = 30;

/**
 * Returnerer cookie-path basert på BASE_URL.
 */
function remember_cookie_path(): string
{
    $base = defined('BASE_URL') ? (string)BASE_URL : '';
    $base = trim($base);

    if ($base === '' || $base === '/') {
        return '/';
    }

    // BASE_URL forventes å starte med '/', men vi sikrer det.
    if ($base[0] !== '/') {
        $base = '/' . $base;
    }

    return rtrim($base, '/');
}

/**
 * Setter en sikker cookie (best-effort lokalt; Secure settes automatisk ved HTTPS).
 */
function set_remember_cookie(string $value, int $expiresTs): void
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443);

    setcookie(
        REMEMBER_COOKIE_NAME,
        $value,
        [
            'expires'  => $expiresTs,
            'path'     => remember_cookie_path(),
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

/**
 * Slett remember-cookie.
 */
function clear_remember_cookie(): void
{
    setcookie(
        REMEMBER_COOKIE_NAME,
        '',
        [
            'expires'  => time() - 3600,
            'path'     => remember_cookie_path(),
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

/**
 * Finn bruker etter e-post.
 */
function find_user_by_email(string $email): ?array
{
    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT user_id, email, password, role, IsActive, LastUsed
         FROM user
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);

    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Finn bruker etter id.
 */
function find_user_by_id(int $userId): ?array
{
    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT user_id, email, password, role, IsActive, LastUsed
         FROM user
         WHERE user_id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $userId]);

    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Oppdater LastUsed.
 */
function touch_user_last_used(int $userId): void
{
    $pdo = db();
    $stmt = $pdo->prepare('UPDATE user SET LastUsed = NOW() WHERE user_id = :id');
    $stmt->execute(['id' => $userId]);
}

/**
 * Opprett remember-token i DB og sett cookie.
 */
function issue_remember_me_token(int $userId): void
{
    $pdo = db();

    $selector  = bin2hex(random_bytes(12));  // 24 hex
    $validator = bin2hex(random_bytes(32));  // 64 hex
    $hash      = hash('sha256', $validator);

    $expiresTs = time() + (REMEMBER_DAYS * 86400);
    $expiresAt = date('Y-m-d H:i:s', $expiresTs);

    $stmt = $pdo->prepare(
        'INSERT INTO user_remember_tokens (user_id, selector, validator_hash, expires_at, created_at, last_used_at)
         VALUES (:user_id, :selector, :validator_hash, :expires_at, NOW(), NOW())'
    );
    $stmt->execute([
        'user_id'        => $userId,
        'selector'       => $selector,
        'validator_hash' => $hash,
        'expires_at'     => $expiresAt,
    ]);

    set_remember_cookie($selector . ':' . $validator, $expiresTs);
}

/**
 * Slett remember-token (basert på selector).
 */
function delete_remember_token_by_selector(string $selector): void
{
    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM user_remember_tokens WHERE selector = :selector');
    $stmt->execute(['selector' => $selector]);
}

/**
 * Forsøk "remember me"-innlogging dersom session mangler.
 * Returnerer true dersom innlogging ble gjenopprettet.
 */
function try_remember_me_login(): bool
{
    if (is_logged_in()) {
        return true;
    }

    $cookie = $_COOKIE[REMEMBER_COOKIE_NAME] ?? '';
    if (!is_string($cookie) || $cookie === '') {
        return false;
    }

    $parts = explode(':', $cookie, 2);
    if (count($parts) !== 2) {
        clear_remember_cookie();
        return false;
    }

    [$selector, $validator] = $parts;

    // Grov validering for å unngå unødvendig DB-trafikk
    if ($selector === '' || $validator === '' || !ctype_xdigit($selector) || !ctype_xdigit($validator)) {
        clear_remember_cookie();
        return false;
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT token_id, user_id, validator_hash, expires_at
         FROM user_remember_tokens
         WHERE selector = :selector
         LIMIT 1'
    );
    $stmt->execute(['selector' => $selector]);
    $row = $stmt->fetch();

    if (!$row) {
        clear_remember_cookie();
        return false;
    }

    // Utløpt?
    if (!empty($row['expires_at']) && strtotime((string)$row['expires_at']) < time()) {
        delete_remember_token_by_selector($selector);
        clear_remember_cookie();
        return false;
    }

    $expected = (string)$row['validator_hash'];
    $given    = hash('sha256', $validator);

    if (!hash_equals($expected, $given)) {
        // Potensiell token-lekkasje: invalider denne tokenen.
        delete_remember_token_by_selector($selector);
        clear_remember_cookie();
        return false;
    }

    $userId = (int)$row['user_id'];
    $user   = find_user_by_id($userId);

    if (!$user || (int)$user['IsActive'] !== 1) {
        delete_remember_token_by_selector($selector);
        clear_remember_cookie();
        return false;
    }

    // Regenerer session-id ved reautentisering
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['user_id'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'] ?? 'user';

    touch_user_last_used((int)$user['user_id']);

    // Rotér token (best practice): samme rad oppdateres med nytt selector/validator.
    $newSelector  = bin2hex(random_bytes(12));
    $newValidator = bin2hex(random_bytes(32));
    $newHash      = hash('sha256', $newValidator);
    $newExpiresTs = time() + (REMEMBER_DAYS * 86400);
    $newExpiresAt = date('Y-m-d H:i:s', $newExpiresTs);

    $upd = $pdo->prepare(
        'UPDATE user_remember_tokens
         SET selector = :new_selector,
             validator_hash = :new_hash,
             expires_at = :new_expires_at,
             last_used_at = NOW()
         WHERE token_id = :token_id'
    );
    $upd->execute([
        'new_selector'   => $newSelector,
        'new_hash'       => $newHash,
        'new_expires_at' => $newExpiresAt,
        'token_id'       => (int)$row['token_id'],
    ]);

    set_remember_cookie($newSelector . ':' . $newValidator, $newExpiresTs);

    return true;
}

/**
 * Logg inn bruker (setter session). Hvis $rememberMe er true, settes også remember-cookie.
 */
function login(string $email, string $password, bool $rememberMe = false): bool
{
    $email = mb_strtolower(trim($email));

    $user = find_user_by_email($email);
    if (!$user) {
        return false;
    }

    if ((int)$user['IsActive'] !== 1) {
        return false;
    }

    if (!password_verify($password, (string)$user['password'])) {
        return false;
    }

    // Regenerer session-id ved innlogging
    session_regenerate_id(true);

    touch_user_last_used((int)$user['user_id']);

    // Sett session
    $_SESSION['user_id'] = (int)$user['user_id'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'] ?? 'user';

    if ($rememberMe) {
        issue_remember_me_token((int)$user['user_id']);
    }

    return true;
}

/**
 * Logg ut (session + remember me).
 */
function logout(): void
{
    // Invalider remember-token hvis cookie finnes
    $cookie = $_COOKIE[REMEMBER_COOKIE_NAME] ?? '';
    if (is_string($cookie) && $cookie !== '') {
        $parts = explode(':', $cookie, 2);
        if (count($parts) === 2) {
            $selector = $parts[0];
            if ($selector !== '' && ctype_xdigit($selector)) {
                delete_remember_token_by_selector($selector);
            }
        }
    }

    clear_remember_cookie();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool)$params['secure'],
            (bool)$params['httponly']
        );
    }

    session_destroy();
}

/**
 * Er bruker innlogget?
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']) && is_int($_SESSION['user_id']);
}

/**
 * Hent info om gjeldende bruker.
 */
function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    return [
        'user_id' => $_SESSION['user_id'],
        'email'   => $_SESSION['email'] ?? null,
        'role'    => $_SESSION['role'] ?? 'user',
    ];
}

/**
 * Krev innlogging (inkl. "remember me").
 */
function require_login(): void
{
    if (!is_logged_in()) {
        try_remember_me_login();
    }

    if (!is_logged_in()) {
        redirect(BASE_URL . '/login.php');
    }
}

/**
 * Krev adminrolle.
 */
function require_admin(): void
{
    require_login();

    if (($_SESSION['role'] ?? 'user') !== 'admin') {
        redirect(BASE_URL . '/index.php');
    }
}
