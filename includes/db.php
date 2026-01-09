<?php
declare(strict_types=1);

/**
 * db.php
 *
 * PDO-basert databaseforbindelse for NMMPrimus-prosjektet.
 * Støtter automatisk miljø-deteksjon (development/production).
 */

// Initialiser error handling først
require_once __DIR__ . '/error_handler.php';

// Alltid bruk samme filnavn (config.php og constants.php)
// Miljøspesifikke verdier settes i selve config-filene
$configFile = __DIR__ . '/../config/config.php';
$constantsFile = __DIR__ . '/../config/constants.php';

// Sjekk at config-filer eksisterer
if (!file_exists($configFile)) {
    die("Error: Configuration file not found. Please copy config.example.php to config.php and configure your database settings.");
}

if (!file_exists($constantsFile)) {
    die("Error: Constants file not found. Please copy constants.example.php to constants.php.");
}

require_once $configFile;
require_once $constantsFile;

/**
 * Returnerer en global PDO-instans.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_NAME
        );

        $pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    return $pdo;
}
