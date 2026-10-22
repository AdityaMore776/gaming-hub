<?php
require __DIR__ . '/includes/init.php';

$next = safe_return_path($_GET['next'] ?? $_POST['next'] ?? null, 'index.php');
if (is_logged_in()) {
    redirect($next);
}

$errors = [];
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $login = str_param($_POST, 'login', 190);
    $password = (string) ($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        $errors[] = 'Enter your email (or username) and password.';
    } else {
        // Small delay after repeated failures in this session slows down password guessing.
        $failures = (int) ($_SESSION['login_failures'] ?? 0);
        if ($failures >= 5) {
            sleep(min(5, $failures - 4));
        }

        $user = db_one('SELECT id, password_hash FROM users WHERE email = ? OR username = ?', [$login, $login]);
        if ($user && password_verify($password, $user['password_hash'])) {
            // Upgrade the hash automatically if PHP's default algorithm changes.
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                db_query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            unset($_SESSION['login_failures']);
            login_user($user);
            flash('success', 'Welcome back!');
            redirect($next);
        }
        $_SESSION['login_failures'] = $failures + 1;
        $errors[] = 'That email/username and password do not match an account.';
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<main class="container">
  <div class="auth-wrap">
    <div class="panel auth-card">
      <h1>Log in</h1>
      <p class="text-soft">Save games to your wishlist, write reviews and keep your quiz preferences.</p>

      <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert"><?= e(implode(' ', $errors)) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= e(url('login.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="mb-3">
          <label class="form-label" for="login">Email or username</label>
          <input class="form-control" id="login" name="login" value="<?= e($login) ?>" autocomplete="username" required autofocus>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-amber w-100" type="submit">Log in</button>
      </form>
      <p class="mt-3 mb-0 text-center">New here? <a href="<?= e(url('register.php' . ($next !== url('index.php') ? '?next=' . urlencode($next) : ''))) ?>">Create an account</a></p>
    </div>
    <?php if (config('debug')): ?>
      <div class="demo-hint mt-3">
        <strong>Demo account (development only):</strong> demo@gaminghub.local / Demo@2026<br>
        Admin and other demo logins are listed in README.md.
      </div>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
