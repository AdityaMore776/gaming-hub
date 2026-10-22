<?php
require __DIR__ . '/../includes/init.php';
require APP_ROOT . '/includes/admin.php';
$admin = require_admin();

$stats = db_one("SELECT
    (SELECT COUNT(*) FROM games) AS games,
    (SELECT COUNT(*) FROM users) AS users,
    (SELECT COUNT(*) FROM reviews WHERE is_hidden = 0) AS reviews,
    (SELECT COUNT(*) FROM reviews WHERE is_hidden = 1) AS hidden_reviews,
    (SELECT COUNT(DISTINCT review_id) FROM review_reports WHERE status = 'open') AS open_reports,
    (SELECT COUNT(*) FROM wishlists) AS wishlist_entries,
    (SELECT AVG(rating) FROM reviews WHERE is_hidden = 0) AS avg_rating,
    (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS new_users");

// Warn while any admin still uses the seeded development password.
$devPasswordAdmins = array_filter(db_all("SELECT username, password_hash FROM users WHERE role = 'admin'"),
    fn($u) => password_verify('Admin@Dev2026', $u['password_hash']));

$recentReviews = db_all('SELECT r.id, r.rating, r.body, r.created_at, r.is_hidden, u.username, g.title, g.id AS game_id
                           FROM reviews r JOIN users u ON u.id = r.user_id JOIN games g ON g.id = r.game_id
                          ORDER BY r.created_at DESC LIMIT 6');
$mostWished = db_all('SELECT g.id, g.title, COUNT(*) AS saves FROM wishlists w JOIN games g ON g.id = w.game_id
                       GROUP BY g.id, g.title ORDER BY saves DESC, g.title LIMIT 5');
$noReviews = db_all('SELECT g.id, g.title FROM games g WHERE NOT EXISTS (SELECT 1 FROM reviews r WHERE r.game_id = g.id AND r.is_hidden = 0) ORDER BY g.title LIMIT 8');

$pageTitle = 'Admin dashboard';
require APP_ROOT . '/includes/header.php';
?>
<main>
  <header class="page-head">
    <div class="container">
      <span class="eyebrow">Admin</span>
      <h1>Dashboard</h1>
      <p>Signed in as <?= e($admin['username']) ?>. All figures are counted live from the database.</p>
    </div>
  </header>
  <div class="container">
    <?= render_admin_nav('dashboard') ?>

    <?php if ($devPasswordAdmins): ?>
      <div class="warn-box mb-4" role="alert">
        <strong>Development password in use.</strong>
        <?= e(implode(', ', array_column($devPasswordAdmins, 'username'))) ?> still
        <?= count($devPasswordAdmins) > 1 ? 'use' : 'uses' ?> the seeded password from the README.
        Change it in <a href="<?= e(url('profile.php')) ?>#pw-title">Profile → Change password</a> before deploying anywhere public.
      </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
      <?php foreach ([
          [$stats['games'], 'Games', 'admin/games.php'],
          [$stats['users'], 'Registered users', 'admin/users.php'],
          [$stats['reviews'], 'Visible reviews', 'admin/reviews.php?tab=all'],
          [$stats['open_reports'], 'Reviews awaiting moderation', 'admin/reviews.php'],
          [$stats['wishlist_entries'], 'Wishlist saves', null],
          [$stats['avg_rating'] ? number_format((float) $stats['avg_rating'], 2) : '–', 'Average rating', null],
          [$stats['hidden_reviews'], 'Hidden reviews', 'admin/reviews.php?tab=hidden'],
          [$stats['new_users'], 'New users (30 days)', 'admin/users.php'],
      ] as [$value, $label, $link]): ?>
        <div class="col-6 col-md-3">
          <?php if ($link): ?><a class="text-decoration-none" href="<?= e(url($link)) ?>"><?php endif; ?>
          <div class="stat-tile h-100"><strong><?= e((string) $value) ?></strong><span><?= e($label) ?></span></div>
          <?php if ($link): ?></a><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
      <a class="btn btn-amber" href="<?= e(url('admin/game_edit.php')) ?>"><?= icon('plus') ?> Add a game</a>
      <a class="btn btn-ghost" href="<?= e(url('admin/reviews.php')) ?>">Moderate reports</a>
      <a class="btn btn-ghost" href="<?= e(url('admin/taxonomy.php')) ?>">Manage genres &amp; platforms</a>
    </div>

    <div class="row g-4">
      <div class="col-lg-8">
        <section class="panel">
          <h2 class="h5">Latest reviews</h2>
          <div class="table-wrap">
            <table class="table table-hover">
              <thead><tr><th>Game</th><th>User</th><th>Rating</th><th>Review</th><th>Date</th></tr></thead>
              <tbody>
                <?php foreach ($recentReviews as $r): ?>
                  <tr>
                    <td><a href="<?= e(url('game.php?id=' . $r['game_id'])) ?>#reviews"><?= e($r['title']) ?></a></td>
                    <td><?= e($r['username']) ?></td>
                    <td><?= (int) $r['rating'] ?>★</td>
                    <td class="small"><?= e(mb_strimwidth($r['body'], 0, 70, '…')) ?>
                      <?php if ($r['is_hidden']): ?><span class="tag tag--amber">Hidden</span><?php endif; ?></td>
                    <td class="small text-soft text-nowrap"><?= e(format_date($r['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
      <div class="col-lg-4">
        <section class="panel mb-4">
          <h2 class="h5">Most wishlisted</h2>
          <?php if ($mostWished): ?>
            <ol class="mb-0">
              <?php foreach ($mostWished as $w): ?>
                <li><a href="<?= e(url('game.php?id=' . $w['id'])) ?>"><?= e($w['title']) ?></a> <span class="text-soft">· <?= plural((int) $w['saves'], 'save') ?></span></li>
              <?php endforeach; ?>
            </ol>
          <?php else: ?><p class="text-soft mb-0">No wishlist saves yet.</p><?php endif; ?>
        </section>
        <section class="panel">
          <h2 class="h5">Games without reviews</h2>
          <?php if ($noReviews): ?>
            <ul class="mb-0">
              <?php foreach ($noReviews as $g): ?>
                <li><a href="<?= e(url('game.php?id=' . $g['id'])) ?>"><?= e($g['title']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?><p class="text-soft mb-0">Every game has at least one review.</p><?php endif; ?>
        </section>
      </div>
    </div>
  </div>
</main>
<?php require APP_ROOT . '/includes/footer.php'; ?>
