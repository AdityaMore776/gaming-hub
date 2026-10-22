<?php
/*
 * Sessions, login state, role checks and CSRF protection.
 */

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('gaminghub_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,     // JavaScript cannot read the session cookie
        'secure'   => $secure,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

/** The logged-in user's row, or null for guests. */
function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $user = db_one('SELECT id, username, email, role, bio, created_at FROM users WHERE id = ?', [$_SESSION['user_id']]);
            if ($user === null) {
                unset($_SESSION['user_id']); // account was deleted
            }
        }
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function login_user(array $user): void
{
    session_regenerate_id(true); // prevents session fixation
    $_SESSION['user_id'] = (int) $user['id'];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Send guests to the login page (or a 401 JSON reply for fetch requests). */
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        if (wants_json()) {
            json_response(['ok' => false, 'login' => true, 'message' => 'Please log in first.'], 401);
        }
        flash('info', 'Please log in to continue.');
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
    return $user;
}

/** Server-side role check. Hiding admin links is not enough on its own. */
function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        $pageTitle = 'Access denied';
        require APP_ROOT . '/includes/header.php';
        echo '<main class="container py-5"><div class="empty-state"><h1 class="h3">Admins only</h1>'
            . '<p>You do not have permission to view this page.</p>'
            . '<a class="btn btn-amber" href="' . e(url('index.php')) . '">Back to home</a></div></main>';
        require APP_ROOT . '/includes/footer.php';
        exit;
    }
    return $user;
}

// ------------------------------------------------------------------ CSRF

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Stop the request unless it carries the session's CSRF token. */
function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        if (wants_json()) {
            json_response(['ok' => false, 'message' => 'Your session expired. Please reload the page.'], 403);
        }
        flash('danger', 'Your session expired or the form was submitted twice. Please try again.');
        redirect(safe_return_path($_POST['return_to'] ?? null, 'index.php'));
    }
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method not allowed.');
    }
    verify_csrf();
}
