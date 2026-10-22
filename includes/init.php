<?php
/*
 * Loaded at the top of every page:  require __DIR__ . '/includes/init.php';
 * Sets up configuration, error handling, the session, the database and helpers.
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$configFile = APP_ROOT . '/config/config.php';
if (!is_file($configFile)) {
    $configFile = APP_ROOT . '/config/config.example.php';
}
$GLOBALS['config'] = require $configFile;

function config(string $key, mixed $default = null): mixed
{
    return $GLOBALS['config'][$key] ?? $default;
}

error_reporting(E_ALL);
ini_set('display_errors', config('debug') ? '1' : '0');

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Any uncaught error shows a friendly page instead of a stack trace.
set_exception_handler(function (Throwable $e): void {
    error_log('[Gaming Hub] ' . $e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (wants_json()) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => 'Something went wrong on the server.']);
        return;
    }
    $details = config('debug') ? $e->getMessage() : null;
    require APP_ROOT . '/includes/error_page.php';
});

start_session();
