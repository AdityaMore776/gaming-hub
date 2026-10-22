<?php
/*
 * Gaming Hub configuration — EXAMPLE FILE.
 *
 * 1. Copy this file to config/config.php
 * 2. Put your own MySQL details in config/config.php
 *
 * config/config.php is listed in .gitignore so real credentials never reach
 * version control. The config/ folder is also blocked from web access.
 *
 * If config/config.php does not exist, the site falls back to this file,
 * which matches a default XAMPP install (user "root", empty password).
 */
return [
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'gaming_hub',
    'db_user' => 'root',
    'db_pass' => '',

    // Leave as null to detect automatically (works for http://localhost/gaming-hub/
    // and for `php -S localhost:8000`). Set it, e.g. '/gaming-hub', only if links break.
    'base_url' => null,

    // true = show technical error details on screen (development only).
    'debug' => true,
];
