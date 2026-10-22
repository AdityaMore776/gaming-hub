<?php
require __DIR__ . '/includes/init.php';

$user = require_login();
$uid = (int) $user['id'];
$errors = [];

// ------------------------------------------------------------ form handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form = $_POST['form'] ?? '';

    if ($form === 'account') {
        $username = str_param($_POST, 'username', 30);
        $bio = str_param($_POST, 'bio', 280);
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
            $errors['username'] = 'Use 3–30 letters, numbers or underscores.';
        } elseif (db_value('SELECT 1 FROM users WHERE username = ? AND id <> ?', [$username, $uid])) {
            $errors['username'] = 'That username is already taken.';
        }
        if (!$errors) {
            db_query('UPDATE users SET username = ?, bio = ? WHERE id = ?', [$username, $bio === '' ? null : $bio, $uid]);
            flash('success', 'Your profile was updated.');
            redirect('profile.php');
        }
    }

    if ($form === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $hash = db_value('SELECT password_hash FROM users WHERE id = ?', [$uid]);
        if (!password_verify($current, (string) $hash)) {
            $errors['current_password'] = 'Your current password is not correct.';
        }
        if (strlen($new) < 8 || strlen($new) > 72 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            $errors['new_password'] = 'Use 8–72 characters, including a letter and a number.';
        } elseif ($new !== ($_POST['confirm_password'] ?? '')) {
            $errors['new_password'] = 'The new passwords do not match.';
        }
        if (!$errors) {
            db_query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $uid]);
            session_regenerate_id(true);
            flash('success', 'Your password was changed.');
            redirect('profile.php');
        }
    }

    if ($form === 'favourites') {
        $genreIds = array_column(all_genres(), 'id');
        $platformIds = array_column(all_platforms(), 'id');
        $pickedGenres = array_intersect(array_map('intval', (array) ($_POST['genres'] ?? [])), array_map('intval', $genreIds));
        $pickedPlatforms = array_intersect(array_map('intval', (array) ($_POST['platforms'] ?? [])), array_map('intval', $platformIds));

        db()->beginTransaction();
        db_query('DELETE FROM user_genres WHERE user_id = ?', [$uid]);
        db_query('DELETE FROM user_platforms WHERE user_id = ?', [$uid]);
        foreach ($pickedGenres as $gid) {
            db_query('INSERT INTO user_genres (user_id, genre_id) VALUES (?, ?)', [$uid, $gid]);
        }
        foreach ($pickedPlatforms as $pid) {
            db_query('INSERT INTO user_platforms (user_id, platform_id) VALUES (?, ?)', [$uid, $pid]);
        }
        db()->commit();
        flash('success', 'Your favourite genres and platforms were saved.');
        redirect('profile.php#favourites');
    }
}

// ------------------------------------------------------------ page data
$user = db_one('SELECT id, username, email, role, bio, created_at FROM users WHERE id = ?', [$uid]);
$myGenres = array_map('intval', db_query('SELECT genre_id FROM user_genres WHERE user_id = ?', [$uid])->fetchAll(PDO::FETCH_COLUMN));
$myPlatforms = array_map('intval', db_query('SELECT platform_id FROM user_platforms WHERE user_id = ?', [$uid])->fetchAll(PDO::FETCH_COLUMN));
$quiz = user_quiz_prefs($uid);
$wishlist = array_map('hydrate_game', db_all(
    str_replace('FROM games g', 'FROM wishlists w JOIN games g ON g.id = w.game_id', game_select_sql())
    . ' WHERE w.user_id = ? ORDER BY w.created_at DESC LIMIT 5', [$uid]));
$wishCount = (int) db_value('SELECT COUNT(*) FROM wishlists WHERE user_id = ?', [$uid]);
$myReviews = db_all('SELECT r.id, r.game_id, r.rating, r.body, r.created_at, r.is_hidden, g.title AS game_title
                       FROM reviews r JOIN games g ON g.id = r.game_id WHERE r.user_id = ? ORDER BY r.created_at DESC', [$uid]);
$favourites = array_filter($myReviews, fn($r) => (int) $r['rating'] >= 4 && !$r['is_hidden']);
$helpfulReceived = (int) db_value('SELECT COUNT(*) FROM review_votes v JOIN reviews r ON r.id = v.review_id WHERE r.user_id = ?', [$uid]);

$quizLabels = [
    'mood' => ['adventure' => 'An adventure', 'story' => 'A good story', 'challenge' => 'A challenge',
               'relax' => 'Something relaxing', 'social' => 'Time with friends', 'quick' => 'A quick session'],
    'play_style' => ['solo' => 'Solo', 'coop' => 'Co-op', 'multiplayer' => 'Multiplayer'],
    'difficulty' => ['relaxed' => 'Relaxed', 'balanced' => 'Balanced', 'challenging' => 'Challenging'],
    'session' => ['short' => 'Under an hour', 'medium' => '1–2 hours', 'long' => '2+ hours'],
    'focus' => ['story' => 'Story', 'combat' => 'Combat', 'exploration' => 'Exploration'],
];
$genreNames = array_column(all_genres(), 'name', 'slug');
$platformNames = array_column(all_platforms(), 'name', 'slug');
$invalid = fn(string $name) => isset($errors[$name]) ? ' is-invalid' : '';
$error = fn(string $name) => isset($errors[$name]) ? '<div class="invalid-feedback">' . e($errors[$name]) . '</div>' : '';

$pageTitle = 'Your profile';
require __DIR__ . '/includes/header.php';
?>
<main>
  <header class="page-head">
    <div class="container d-flex flex-wrap align-items-center gap-3">
      <span class="avatar" style="width:64px;height:64px;font-size:1.6rem" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['username'], 0, 1))) ?></span>
      <div>
        <h1 class="mb-0"><?= e($user['username']) ?></h1>
        <p><?= e($user['email']) ?> · Member since <?= e(format_date($user['created_at'])) ?>
          <?php if ($user['role'] === 'admin'): ?> · <span class="tag tag--amber">Admin</span><?php endif; ?></p>
      </div>
    </div>
  </header>

  <div class="container">
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3"><div class="stat-tile"><strong><?= $wishCount ?></strong><span>Games on wishlist</span></div></div>
      <div class="col-6 col-md-3"><div class="stat-tile"><strong><?= count($myReviews) ?></strong><span>Reviews written</span></div></div>
      <div class="col-6 col-md-3"><div class="stat-tile"><strong><?= $helpfulReceived ?></strong><span>Helpful votes received</span></div></div>
      <div class="col-6 col-md-3"><div class="stat-tile"><strong><?= count($myGenres) ?></strong><span>Favourite genres</span></div></div>
    </div>

    <div class="row g-4">
      <div class="col-lg-7">
        <!-- Saved quiz answers -->
        <section class="panel mb-4" aria-labelledby="quiz-title">
          <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3">
            <h2 class="h5 mb-0" id="quiz-title">Saved gaming preferences</h2>
            <a class="btn btn-sm btn-amber" href="<?= e(url('recommend.php')) ?>"><?= $quiz ? 'Retake quiz' : 'Take the quiz' ?></a>
          </div>
          <?php if ($quiz): ?>
            <dl class="pref-list">
              <dt>Genre</dt><dd><?= e($genreNames[$quiz['genre']] ?? 'No preference') ?></dd>
              <dt>Mood</dt><dd><?= e($quizLabels['mood'][$quiz['mood']] ?? 'No preference') ?></dd>
              <dt>Play style</dt><dd><?= e($quizLabels['play_style'][$quiz['play_style']] ?? 'No preference') ?></dd>
              <dt>Difficulty</dt><dd><?= e($quizLabels['difficulty'][$quiz['difficulty']] ?? 'No preference') ?></dd>
              <dt>Time to play</dt><dd><?= e($quizLabels['session'][$quiz['session']] ?? 'No preference') ?></dd>
              <dt>Focus</dt><dd><?= e($quizLabels['focus'][$quiz['focus']] ?? 'No preference') ?></dd>
              <dt>Platform</dt><dd><?= e($platformNames[$quiz['platform']] ?? 'No preference') ?></dd>
            </dl>
            <p class="small text-soft mt-3">Saved <?= e(format_date($quiz['updated_at'])) ?>. Game pages use these answers to explain why a game may or may not suit you.</p>
            <form method="post" action="<?= e(url('actions/preferences.php')) ?>" data-confirm="Clear your saved quiz answers?">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="clear">
              <button class="btn btn-sm btn-ghost" type="submit">Clear saved answers</button>
            </form>
          <?php else: ?>
            <p class="text-soft mb-0">You haven't saved any quiz answers yet. Take the quiz and press “Save these preferences”.</p>
          <?php endif; ?>
        </section>

        <!-- Favourite genres and platforms -->
        <section class="panel mb-4" id="favourites" aria-labelledby="fav-title">
          <h2 class="h5" id="fav-title">Favourite genres &amp; platforms</h2>
          <form method="post" action="<?= e(url('profile.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="favourites">
            <fieldset class="mb-3">
              <legend class="form-label">Genres</legend>
              <div class="chip-options">
                <?php foreach (all_genres() as $g): ?>
                  <label class="chip-option"><input type="checkbox" name="genres[]" value="<?= (int) $g['id'] ?>"
                    <?= in_array((int) $g['id'], $myGenres, true) ? 'checked' : '' ?>><span><?= e($g['name']) ?></span></label>
                <?php endforeach; ?>
              </div>
            </fieldset>
            <fieldset class="mb-3">
              <legend class="form-label">Platforms you own</legend>
              <div class="chip-options">
                <?php foreach (all_platforms() as $p): ?>
                  <label class="chip-option"><input type="checkbox" name="platforms[]" value="<?= (int) $p['id'] ?>"
                    <?= in_array((int) $p['id'], $myPlatforms, true) ? 'checked' : '' ?>><span><?= e($p['name']) ?></span></label>
                <?php endforeach; ?>
              </div>
            </fieldset>
            <button class="btn btn-violet" type="submit">Save favourites</button>
            <?php if ($myGenres): ?>
              <a class="btn btn-ghost ms-1" href="<?= e(url('explore.php?genre=' . (array_column(all_genres(), 'slug', 'id')[$myGenres[0]] ?? ''))) ?>">Browse a favourite genre</a>
            <?php endif; ?>
          </form>
        </section>

        <!-- Reviews -->
        <section class="panel mb-4" aria-labelledby="my-reviews-title">
          <h2 class="h5" id="my-reviews-title">My reviews</h2>
          <?php if ($myReviews): ?>
            <div class="table-wrap">
              <table class="table table-hover">
                <thead><tr><th>Game</th><th>Rating</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($myReviews as $r): ?>
                    <tr>
                      <td><a href="<?= e(url('game.php?id=' . $r['game_id'])) ?>#reviews"><?= e($r['game_title']) ?></a>
                        <?php if ($r['is_hidden']): ?><span class="tag tag--amber ms-1">Hidden by moderator</span><?php endif; ?></td>
                      <td><?= render_stars((float) $r['rating']) ?></td>
                      <td class="text-soft small"><?= e(format_date($r['created_at'])) ?></td>
                      <td class="text-end">
                        <a class="btn btn-sm btn-ghost" href="<?= e(url('game.php?id=' . $r['game_id'])) ?>#review-form">Edit</a>
                        <form method="post" action="<?= e(url('actions/review.php')) ?>" class="d-inline" data-confirm="Delete this review?">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="review_id" value="<?= (int) $r['id'] ?>">
                          <input type="hidden" name="return_to" value="<?= e(url('profile.php')) ?>">
                          <button class="btn btn-sm btn-ghost text-danger-soft">Delete</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <p class="text-soft mb-0">You haven't reviewed any games yet. Open a game's page to write one.</p>
          <?php endif; ?>
        </section>
      </div>

      <div class="col-lg-5">
        <section class="panel mb-4" aria-labelledby="wish-title">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 mb-0" id="wish-title">Wishlist</h2>
            <a class="small" href="<?= e(url('wishlist.php')) ?>">View all (<?= $wishCount ?>)</a>
          </div>
          <?php foreach ($wishlist as $g): ?>
            <a class="mini-game" href="<?= e(url('game.php?id=' . $g['id'])) ?>">
              <?= render_cover($g, 'sm') ?>
              <span><strong><?= e($g['title']) ?></strong><small><?= e(implode(' · ', array_slice($g['genres'], 0, 2))) ?></small></span>
            </a>
          <?php endforeach; ?>
          <?php if (!$wishlist): ?><p class="text-soft mb-0">No saved games yet.</p><?php endif; ?>
        </section>

        <section class="panel mb-4" aria-labelledby="fav-games-title">
          <h2 class="h5" id="fav-games-title">Favourite games</h2>
          <p class="small text-soft">Games you rated 4 or 5 stars.</p>
          <?php foreach (array_slice($favourites, 0, 6) as $r): ?>
            <a class="mini-game" href="<?= e(url('game.php?id=' . $r['game_id'])) ?>">
              <span><strong><?= e($r['game_title']) ?></strong><small><?= render_stars((float) $r['rating']) ?></small></span>
            </a>
          <?php endforeach; ?>
          <?php if (!$favourites): ?><p class="text-soft mb-0">Rate games 4 stars or higher and they will appear here.</p><?php endif; ?>
        </section>

        <section class="panel mb-4" aria-labelledby="account-title">
          <h2 class="h5" id="account-title">Account details</h2>
          <form method="post" action="<?= e(url('profile.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="account">
            <div class="mb-3">
              <label class="form-label" for="username">Username</label>
              <input class="form-control<?= $invalid('username') ?>" id="username" name="username" value="<?= e(($_POST['form'] ?? '') === 'account' && $errors ? str_param($_POST, 'username', 30) : $user['username']) ?>" required>
              <?= $error('username') ?>
            </div>
            <div class="mb-3">
              <label class="form-label" for="email">Email</label>
              <input class="form-control" id="email" value="<?= e($user['email']) ?>" disabled>
              <div class="form-text">Email can't be changed in this version.</div>
            </div>
            <div class="mb-3">
              <label class="form-label" for="bio">About you</label>
              <textarea class="form-control" id="bio" name="bio" rows="2" maxlength="280"><?= e(($_POST['form'] ?? '') === 'account' && $errors ? str_param($_POST, 'bio', 280) : ($user['bio'] ?? '')) ?></textarea>
            </div>
            <button class="btn btn-violet" type="submit">Save details</button>
          </form>
        </section>

        <section class="panel" aria-labelledby="pw-title">
          <h2 class="h5" id="pw-title">Change password</h2>
          <form method="post" action="<?= e(url('profile.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="password">
            <div class="mb-3">
              <label class="form-label" for="current_password">Current password</label>
              <input class="form-control<?= $invalid('current_password') ?>" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
              <?= $error('current_password') ?>
            </div>
            <div class="mb-3">
              <label class="form-label" for="new_password">New password</label>
              <input class="form-control<?= $invalid('new_password') ?>" type="password" id="new_password" name="new_password" autocomplete="new-password" required>
              <?= $error('new_password') ?>
            </div>
            <div class="mb-3">
              <label class="form-label" for="confirm_password">Confirm new password</label>
              <input class="form-control" type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
            </div>
            <button class="btn btn-ghost" type="submit">Change password</button>
          </form>
        </section>
      </div>
    </div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
