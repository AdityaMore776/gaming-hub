<?php
/*
 * Router for PHP's built-in development server:
 *
 *     php -S localhost:8000 router.php
 *
 * Apache (XAMPP) uses the .htaccess files instead. This file blocks the same
 * private folders so they cannot be downloaded through the browser.
 */
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

$blocked = '#^/(includes|config|database)(/|$)|/\.|^/uploads/.*\.(php|phtml|phar|html?|svg|js)$|^/router\.php$#i';
if (preg_match($blocked, $path)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}

return false; // let the built-in server handle the request normally
