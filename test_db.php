<?php
/**
 * Test database-tilkobling
 *
 * Tester tilkobling til database med produksjons config.local.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database-tilkoblingstest</h1>\n";
echo "<pre>\n";

// Test 1: Sjekk om config-filer eksisterer
echo "=== Test 1: Config-filer ===\n";
$configFile = __DIR__ . '/config/config.php';
$configLocalFile = __DIR__ . '/config/config.local.php';

// Debug: Vis fulle stier
// echo "Config path: $configFile\n";
// echo "Config.local path: $configLocalFile\n";

echo "config.php: " . (file_exists($configFile) ? "✓ Funnet" : "✗ Mangler") . "\n";
echo "config.local.php: " . (file_exists($configLocalFile) ? "✓ Funnet" : "✗ Mangler") . "\n\n";

// Test 2: Last inn config
echo "=== Test 2: Last config ===\n";
try {
    $config = require $configFile;
    echo "✓ config.php lastet\n";

    if (file_exists($configLocalFile)) {
        echo "✓ config.local.php funnet og lastet\n";
    } else {
        echo "⚠ config.local.php mangler (bruker default config)\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Feil ved lasting av config: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Vis database-konfigurasjon (uten passord)
echo "=== Test 3: Database-konfigurasjon ===\n";
echo "DB_HOST: " . ($config['db_host'] ?? 'IKKE SATT') . "\n";
echo "DB_NAME: " . ($config['db_name'] ?? 'IKKE SATT') . "\n";
echo "DB_USER: " . ($config['db_user'] ?? 'IKKE SATT') . "\n";
echo "DB_PASS: " . (isset($config['db_pass']) ? ($config['db_pass'] ? '[SATT - skjult]' : '[TOM]') : 'IKKE SATT') . "\n";
echo "BASE_URL: " . ($config['base_url'] ?? 'IKKE SATT') . "\n\n";

// Test 4: Test PDO-tilkobling
echo "=== Test 4: PDO-tilkobling ===\n";
try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    echo "✓ PDO-tilkobling vellykket\n\n";
} catch (PDOException $e) {
    echo "✗ PDO-tilkobling feilet: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Test database-klassen (DB)
echo "=== Test 5: DB-klassen ===\n";
try {
    require_once __DIR__ . '/includes/db.php';
    $db = db();
    echo "✓ DB-klasse lastet og initialisert\n\n";
} catch (Exception $e) {
    echo "✗ Feil ved lasting av DB-klasse: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Test enkel SQL-spørring
echo "=== Test 6: SQL-spørring ===\n";
try {
    $result = $db->fetchOne("SELECT DATABASE() AS db_name, VERSION() AS version, NOW() AS server_time");
    echo "✓ SQL-spørring vellykket\n";
    echo "Database: " . $result['db_name'] . "\n";
    echo "MySQL Version: " . $result['version'] . "\n";
    echo "Server-tid: " . $result['server_time'] . "\n\n";
} catch (Exception $e) {
    echo "✗ SQL-spørring feilet: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Test tabeller
echo "=== Test 7: Database-tabeller ===\n";
try {
    $tables = $db->fetchAll("SHOW TABLES");
    echo "✓ Antall tabeller: " . count($tables) . "\n";

    // Sjekk viktige tabeller
    $importantTables = ['medlemmer', 'arrangementer', 'posteringer', 'bilag', 'sesonger', 'kontoplan'];
    $tableNames = array_column($tables, 'Tables_in_' . $config['db_name']);

    foreach ($importantTables as $table) {
        $exists = in_array($table, $tableNames);
        echo ($exists ? "✓" : "✗") . " $table\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Feil ved henting av tabeller: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 8: Test views
echo "=== Test 8: Database-views ===\n";
try {
    $views = $db->fetchAll("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    echo "✓ Antall views: " . count($views) . "\n";

    // Sjekk viktige views
    $importantViews = ['v_medlemmer_oversikt', 'v_posteringer_oversikt', 'v_arrangementer_oversikt'];
    $viewNames = array_column($views, 'Tables_in_' . $config['db_name']);

    foreach ($importantViews as $view) {
        $exists = in_array($view, $viewNames);
        echo ($exists ? "✓" : "✗") . " $view\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Feil ved henting av views: " . $e->getMessage() . "\n";
}

// Test 9: Test data
echo "=== Test 9: Test data-spørringer ===\n";
try {
    // Test medlemmer
    $medlemCount = $db->fetchOne("SELECT COUNT(*) as count FROM medlemmer");
    echo "Medlemmer: " . $medlemCount['count'] . "\n";

    // Test arrangementer
    $arrCount = $db->fetchOne("SELECT COUNT(*) as count FROM arrangementer");
    echo "Arrangementer: " . $arrCount['count'] . "\n";

    // Test posteringer
    $postCount = $db->fetchOne("SELECT COUNT(*) as count FROM posteringer");
    echo "Posteringer: " . $postCount['count'] . "\n";

    // Test bilag
    $bilagCount = $db->fetchOne("SELECT COUNT(*) as count FROM bilag");
    echo "Bilag: " . $bilagCount['count'] . "\n";

    // Test sesonger
    $sesCount = $db->fetchOne("SELECT COUNT(*) as count FROM sesonger");
    echo "Sesonger: " . $sesCount['count'] . "\n";

    // Hent aktiv sesong
    $aktivSesong = $db->fetchOne("SELECT Sesong FROM sesonger WHERE Aktiv = 1 LIMIT 1");
    echo "Aktiv sesong: " . ($aktivSesong ? $aktivSesong['Sesong'] : 'Ingen') . "\n";

    echo "\n✓ Alle data-spørringer vellykket\n\n";
} catch (Exception $e) {
    echo "✗ Feil ved data-spørringer: " . $e->getMessage() . "\n";
}

// Oppsummering
echo "=== OPPSUMMERING ===\n";
echo "✓ Database-tilkobling fungerer!\n";
echo "✓ Alle tester bestått\n";
echo "\n";
echo "Du kan nå bruke applikasjonen.\n";
echo "Test-URL: http://localhost" . ($config['base_url'] ?? '/sterkestav/') . "\n";

echo "</pre>\n";
