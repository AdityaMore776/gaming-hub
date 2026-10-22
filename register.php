<?php
require __DIR__ . '/includes/init.php';

$next = safe_return_path($_GET['next'] ?? $_POST['next'] ?? null, 'index.php');
if (is_logged_in()) {
    redirect($next);
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = str_param($_POST, 'username', 30);
    $email = strtolower(str_param($_POST, 'email', 190));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
        $errors['username'] = 'Use 3–30 letters, numbers or underscores.';
    } elseif (db_value('SELECT 1 FROM users WHERE username = ?', [$username])) {
        $errors['username'] = 'That username is already taken.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif (db_value('SELECT 1 FROM users WHERE email = ?', [$email])) {
        $errors['email'] = 'An account with this email already exists. Try logging in.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Use at least 8 characters, including a letter and a number.';
    } elseif (strlen($password) > 72) {
        $errors['password'] = 'Use 72 characters or fewer.'; // bcrypt only uses the first 72 bytes
    }
    if ($password !== $confirm) {
        $errors['confirm_password'] = 'The passwords do not match.';
    }

    if (!$errors) {
        try {
            // New accounts are always regular users. Admins are created with database/create_admin.php.
            db_query("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')",
                [$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
            login_user(['id' => (int) db()->lastInsertId()]);
            flash('success', 'Welcome to Gaming Hub, ' . $username . '! Try the quiz to set your preferences.');
            redirect($next);
        } catch (PDOException $ex) {
            if ($ex->getCode() !== '23000') {
                throw $ex;
            }
            $errors['email'] = 'That username or email was just registered. Please choose another.';
        }
    }
}

$field = function (string $name, string $label, string $type, string $value, string $autocomplete, string $help = '') use ($errors): void {
    $invalid = isset($errors[$name]);
    ?>
    <div class="mb-3">
      <label class="form-label" for="<?= e($name) ?>"><?= e($label) ?></label>
      <input class="form-control<?= $invalid ? ' is-invalid' : '' ?>" type="<?= e($type) ?>" id="<?= e($name) ?>" name="<?= e($name) ?>"
             value="<?= e($value) ?>" autocomplete="<?= e($autocomplete) ?>" required
             <?= $invalid ? 'aria-describedby="' . e($name) . '-error"' : ($help ? 'aria-describedby="' . e($name) . '-help"' : '') ?>>
      <?php if ($invalid): ?>
        <div class="invalid-feedback" id="<?= e($name) ?>-error"><?= e($errors[$name]) ?></div>
      <?php elseif ($help): ?>
        <div class="form-text" id="<?= e($name) ?>-help"><?= e($help) ?></div>
      <?php endif; ?>
    </div>
    <?php
};

$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<main class="container">
  <div class="auth-wrap">
    <div class="panel auth-card">
      <h1>Create your account</h1>
      <p class="text-soft">Free, and only used for your wishlist, reviews and preferences on this site.</p>
      <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
      <?php endif; ?>
      <form method="post" action="<?= e(url('register.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <?php
        $field('username', 'Username', 'text', $username, 'username', 'Shown on your reviews. Letters, numbers and underscores.');
        $field('email', 'Email', 'email', $email, 'email');
        $field('password', 'Password', 'password', '', 'new-password', 'At least 8 characters, with a letter and a number.');
        $field('confirm_password', 'Confirm password', 'password', '', 'new-password');
        ?>
        <button class="btn btn-amber w-100 mt-2" type="submit">Create account</button>
      </form>
      <p class="mt-3 mb-0 text-center">Already have an account? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
    </div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
