<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

// === KONFIGURER DISSE ===
$email    = 'gerhard@ihlen.net';
$passord = '1Gondor!';
$role     = 'admin'; // eller 'user'
// ========================

$hash = password_hash($passord, PASSWORD_DEFAULT);

$pdo = db();

$stmt = $pdo->prepare(
    'INSERT INTO user (email, password, role, IsActive)
     VALUES (:email, :password, :role, 1)'
);

$stmt->execute([
    'email'    => $email,
    'password' => $hash,
    'role'     => $role,
]);

echo "Bruker opprettet: $email\n";
