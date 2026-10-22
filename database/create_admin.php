<?php
/*
 * Create an administrator account, or promote an existing account to admin.
 * Command line only, so there is no public web form that can create admins.
 *
 * Usage (from the project folder):
 *     php database/create_admin.php <username> <email>
 *
 * You will be asked for a password (or set it with the GH_ADMIN_PASSWORD
 * environment variable). If the email already belongs to an account, that
 * account is promoted to admin and its password is reset.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

define('APP_ROOT', dirname(__DIR__));
$configFile = APP_ROOT . '/config/config.php';
$GLOBALS['config'] = require (is_file($configFile) ? $configFile : APP_ROOT . '/config/config.example.php');
function config(string $key, mixed $default = null): mixed { return $GLOBALS['config'][$key] ?? $default; }
require APP_ROOT . '/includes/db.php';

[$script, $username, $email] = array_pad($argv, 3, null);
if (!$username || !$email) {
    fwrite(STDERR, "Usage: php database/create_admin.php <username> <email>\n");
    exit(1);
}
if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
    fwrite(STDERR, "Username must be 3-30 letters, numbers or underscores.\n");
    exit(1);
}
$email = strtolower($email);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "That is not a valid email address.\n");
    exit(1);
}

$password = getenv('GH_ADMIN_PASSWORD') ?: '';
if ($password === '') {
    echo 'Password for the admin account (min 10 characters): ';
    $password = trim((string) fgets(STDIN));
}
if (strlen($password) < 10 || strlen($password) > 72 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    fwrite(STDERR, "Use 10-72 characters with at least one letter and one number.\n");
    exit(1);
}
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $existing = db_one('SELECT id, username FROM users WHERE email = ?', [$email]);
    if ($existing) {
        db_query("UPDATE users SET role = 'admin', password_hash = ? WHERE id = ?", [$hash, $existing['id']]);
        echo "Promoted existing account '{$existing['username']}' ({$email}) to admin and set the new password.\n";
    } else {
        if (db_value('SELECT 1 FROM users WHERE username = ?', [$username])) {
            fwrite(STDERR, "The username '$username' is taken by an account with a different email.\n");
            exit(1);
        }
        db_query("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')", [$username, $email, $hash]);
        echo "Created admin account '$username' ($email).\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Database error: ' . $e->getMessage() . "\n");
    exit(1);
}
