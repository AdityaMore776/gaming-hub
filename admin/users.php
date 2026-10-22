<?php
require __DIR__ . '/../includes/init.php';
require APP_ROOT . '/includes/admin.php';
$admin = require_admin();

$q = str_param($_GET, 'q', 80);
$where = '1 = 1';
$params = [];
if ($q !== '') {
    $where = '(u.username LIKE ? OR u.email LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $params = [$like, $like];
}
$users = db_all("SELECT u.id, u.username, u.email, u.role, u.created_at,
                        (SELECT COUNT(*) FROM reviews r WHERE r.user_id = u.id) AS reviews,
                        (SELECT COUNT(*) FROM reviews r WHERE r.user_id = u.id AND r.is_hidden = 1) AS hidden_reviews,
                        (SELECT COUNT(*) FROM wishlists w WHERE w.user_id = u.id) AS wishlist
                   FROM users u WHERE $where ORDER BY u.created_at DESC", $params);

$pageTitle = 'Users';
require APP_ROOT . '/includes/header.php';
?>
<main>
  <header class="page-head">
    <div class="container">
      <span class="eyebrow">Admin</span>
      <h1>Users</h1>
      <p>Everyone with a Gaming Hub account. Passwords are stored only as secure hashes and are never shown.
         New administrators are created from the command line with <code>php database/create_admin.php</code>.</p>
    </div>
  </header>
  <div class="container">
    <?= render_admin_nav('users') ?>
    <form class="d-flex gap-2 mb-3" method="get" role="search" style="max-width: 420px">
      <label class="visually-hidden" for="user-q">Search users</label>
      <input class="form-control" id="user-q" type="search" name="q" value="<?= e($q) ?>" placeholder="Search username or email">
      <button class="btn btn-ghost" type="submit"><?= icon('search') ?></button>
    </form>
    <p class="small text-soft"><?= plural(count($users), 'user') ?></p>
    <div class="table-wrap">
      <table class="table table-hover">
        <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Reviews</th><th>Wishlist</th><th>Joined</th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><span class="avatar avatar--sm me-1" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($u['username'], 0, 1))) ?></span> <?= e($u['username']) ?></td>
              <td class="small"><?= e($u['email']) ?></td>
              <td><?= $u['role'] === 'admin' ? '<span class="tag tag--amber">Admin</span>' : '<span class="tag">User</span>' ?></td>
              <td>
                <?php if ($u['reviews']): ?>
                  <a href="<?= e(url('admin/reviews.php?tab=all&user=' . $u['id'])) ?>"><?= (int) $u['reviews'] ?></a>
                <?php else: ?>0<?php endif; ?>
                <?php if ($u['hidden_reviews']): ?><span class="small text-amber">(<?= (int) $u['hidden_reviews'] ?> hidden)</span><?php endif; ?>
              </td>
              <td><?= (int) $u['wishlist'] ?></td>
              <td class="small text-soft text-nowrap"><?= e(format_date($u['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php require APP_ROOT . '/includes/footer.php'; ?>
