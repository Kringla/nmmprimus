<?php
declare(strict_types=1);

/**
 * error_handler.php
 *
 * Sentralisert error logging og error handling.
 */

/**
 * Sett opp error handler og logging.
 */
function setup_error_handling(): void
{
    // Detekter miljø
    $env = getenv('APP_ENV') ?: 'development';
    $isProduction = ($env === 'production');

    // I produksjon: ikke vis feil til bruker
    if ($isProduction) {
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        error_reporting(E_ALL);
    } else {
        // I development: vis feil
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
    }

    // Sett custom error handler
    set_error_handler('custom_error_handler');
    set_exception_handler('custom_exception_handler');
    register_shutdown_function('custom_shutdown_handler');
}

/**
 * Custom error handler.
 */
function custom_error_handler(
    int $errno,
    string $errstr,
    string $errfile,
    int $errline
): bool {
    // Ikke logg E_DEPRECATED i development
    if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
        return false;
    }

    $error_type = get_error_type_string($errno);
    $message = sprintf(
        "[%s] %s in %s on line %d",
        $error_type,
        $errstr,
        $errfile,
        $errline
    );

    log_error($message);

    // I produksjon, vis generisk feilmelding
    $env = getenv('APP_ENV') ?: 'development';
    if ($env === 'production') {
        // Ikke stopp for warnings/notices
        if ($errno === E_WARNING || $errno === E_NOTICE || $errno === E_USER_WARNING || $errno === E_USER_NOTICE) {
            return true;
        }

        // For alvorlige feil, vis generisk melding
        http_response_code(500);
        echo "<h1>En feil oppstod</h1>";
        echo "<p>Beklager, en teknisk feil har oppstått. Vennligst prøv igjen senere.</p>";
        exit;
    }

    return false; // La PHP håndtere feil som normalt i development
}

/**
 * Custom exception handler.
 */
function custom_exception_handler(Throwable $exception): void
{
    $message = sprintf(
        "[EXCEPTION] %s: %s in %s on line %d\nStack trace:\n%s",
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );

    log_error($message);

    // I produksjon, vis generisk feilmelding
    $env = getenv('APP_ENV') ?: 'development';
    if ($env === 'production') {
        http_response_code(500);
        echo "<h1>En feil oppstod</h1>";
        echo "<p>Beklager, en teknisk feil har oppstått. Vennligst prøv igjen senere.</p>";
        exit;
    } else {
        // I development, vis detaljert feil
        http_response_code(500);
        echo "<h1>Exception: " . htmlspecialchars(get_class($exception)) . "</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . " (line " . $exception->getLine() . ")</p>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        exit;
    }
}

/**
 * Shutdown handler for fatal errors.
 */
function custom_shutdown_handler(): void
{
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $message = sprintf(
            "[FATAL] %s in %s on line %d",
            $error['message'],
            $error['file'],
            $error['line']
        );

        log_error($message);

        // I produksjon, vis generisk feilmelding
        $env = getenv('APP_ENV') ?: 'development';
        if ($env === 'production') {
            http_response_code(500);
            echo "<h1>En feil oppstod</h1>";
            echo "<p>Beklager, en teknisk feil har oppstått. Vennligst prøv igjen senere.</p>";
        }
    }
}

/**
 * Logg feilmelding til fil.
 */
function log_error(string $message): void
{
    $logDir = __DIR__ . '/../logs';
    $logFile = $logDir . '/error.log';

    // Opprett logs-mappe hvis den ikke finnes
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    // Format melding med timestamp
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = sprintf("[%s] %s\n", $timestamp, $message);

    // Skriv til logg
    @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);

    // Roter loggfil hvis den blir for stor (> 10MB)
    if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) {
        rotate_log_file($logFile);
    }
}

/**
 * Roter loggfil.
 */
function rotate_log_file(string $logFile): void
{
    $rotatedFile = $logFile . '.' . date('Y-m-d_His');
    @rename($logFile, $rotatedFile);

    // Hold maks 5 roterte filer
    $logDir = dirname($logFile);
    $files = glob($logDir . '/error.log.*');
    if ($files !== false && count($files) > 5) {
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        // Slett eldste filer
        $toDelete = array_slice($files, 0, count($files) - 5);
        foreach ($toDelete as $file) {
            @unlink($file);
        }
    }
}

/**
 * Konverter error-nummer til lesbar string.
 */
function get_error_type_string(int $errno): string
{
    $errorTypes = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED',
    ];

    return $errorTypes[$errno] ?? 'UNKNOWN';
}

/**
 * Logg custom melding (for application-specific logging).
 */
function log_message(string $level, string $message, array $context = []): void
{
    $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $logMessage = sprintf("[%s] %s%s", strtoupper($level), $message, $contextStr);
    log_error($logMessage);
}

// Initialiser error handling automatisk når filen inkluderes
setup_error_handling();
