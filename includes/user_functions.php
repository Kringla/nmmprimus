<?php
declare(strict_types=1);

/**
 * user_functions.php
 *
 * Hjelpefunksjoner for brukeradministrasjon.
 */

require_once __DIR__ . '/db.php';

/**
 * Hent alle brukere
 */
function user_hent_alle(): array
{
    $pdo = db();
    $stmt = $pdo->query("
        SELECT user_id, email, role, IsActive, created_at, LastUsed
        FROM user
        ORDER BY created_at DESC
    ");
    return $stmt->fetchAll();
}

/**
 * Hent én bruker etter ID
 */
function user_hent_en(int $userId): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT user_id, email, role, IsActive, created_at, LastUsed
        FROM user
        WHERE user_id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Opprett ny bruker
 */
function user_opprett(string $email, string $passord, string $role = 'user'): int
{
    $pdo = db();

    // Valider at e-post ikke allerede eksisterer
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        throw new RuntimeException('E-postadressen er allerede registrert.');
    }

    // Hash passord
    $hash = password_hash($passord, PASSWORD_DEFAULT);

    // Opprett bruker
    $stmt = $pdo->prepare("
        INSERT INTO user (email, password, role, IsActive)
        VALUES (:email, :password, :role, 1)
    ");
    $stmt->execute([
        'email'    => $email,
        'password' => $hash,
        'role'     => $role,
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Oppdater bruker (e-post og rolle)
 */
function user_oppdater(int $userId, string $email, string $role): bool
{
    $pdo = db();

    // Valider at e-post ikke er i bruk av annen bruker
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM user
        WHERE email = :email AND user_id != :id
    ");
    $stmt->execute(['email' => $email, 'id' => $userId]);
    if ($stmt->fetchColumn() > 0) {
        throw new RuntimeException('E-postadressen er allerede i bruk av en annen bruker.');
    }

    $stmt = $pdo->prepare("
        UPDATE user
        SET email = :email, role = :role
        WHERE user_id = :id
    ");
    return $stmt->execute([
        'email' => $email,
        'role'  => $role,
        'id'    => $userId,
    ]);
}

/**
 * Endre passord for bruker
 */
function user_endre_passord(int $userId, string $nyttPassord): bool
{
    $pdo = db();
    $hash = password_hash($nyttPassord, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        UPDATE user
        SET password = :password
        WHERE user_id = :id
    ");
    return $stmt->execute([
        'password' => $hash,
        'id'       => $userId,
    ]);
}

/**
 * Aktiver/deaktiver bruker
 */
function user_toggle_aktiv(int $userId): bool
{
    $pdo = db();
    $stmt = $pdo->prepare("
        UPDATE user
        SET IsActive = NOT IsActive
        WHERE user_id = :id
    ");
    return $stmt->execute(['id' => $userId]);
}

/**
 * Slett bruker
 */
function user_slett(int $userId): bool
{
    $pdo = db();

    // Forhindre sletting av siste admin
    $stmt = $pdo->query("SELECT COUNT(*) FROM user WHERE role = 'admin'");
    $antallAdmins = (int)$stmt->fetchColumn();

    $bruker = user_hent_en($userId);
    if ($bruker && $bruker['role'] === 'admin' && $antallAdmins <= 1) {
        throw new RuntimeException('Kan ikke slette siste administrator.');
    }

    $stmt = $pdo->prepare("DELETE FROM user WHERE user_id = :id");
    return $stmt->execute(['id' => $userId]);
}
