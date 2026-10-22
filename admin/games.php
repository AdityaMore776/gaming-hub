<?php
require __DIR__ . '/../includes/init.php';
require APP_ROOT . '/includes/admin.php';
$admin = require_admin();

// ------------------------------------------------------------ actions (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = int_param($_POST, 'id');
    $game = $id ? db_one('SELECT id, title, cover_image, is_featured FROM games WHERE id = ?', [$id]) : null;
    $back = safe_return_path($_POST['return_to'] ?? null, 'admin/games.php');

    if ($game === null) {
        flash('danger', 'That game no longer exists.');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        // Reviews, wishlist entries, genres, platforms and DNA are removed by ON DELETE CASCADE.
        db_query('DELETE FROM games WHERE id = ?', [$id]);
        delete_cover_file($game['cover_image']);
        flash('success', '“' . $game['title'] . '” and its reviews were deleted.');
    } elseif (($_POST['action'] ?? '') === 'feature') {
        db_query('UPDATE games SET is_featured = 1 - is_featured WHERE id = ?', [$id]);
        flash('success', '“' . $game['title'] . '” is ' . ($game['is_featured'] ? 'no longer featured.' : 'now featured on the homepage.'));
    }
    redirect($back);
}

// ------------------------------------------------------------ list
$q = str_param($_GET, 'q', 80);
$where = '1 = 1';
$params = [];
if ($q !== '') {
    $where = '(g.title LIKE ? OR g.developer LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $params = [$like, $like];
}
$games = array_map('hydrate_game', db_all(game_select_sql() . " WHERE $where ORDER BY g.title", $params));

$pageTitle = 'Manage games';
require APP_ROOT . '/includes/header.php';
?>
<main>
  <header class="page-head">
    <div class="container">
      <span class="eyebrow">Admin</span>
      <h1>Games</h1>
      <p>Add, edit, feature or delete games. Deleting a game also deletes its reviews and wishlist entries.</p>
    </div>
  </header>
  <div class="container">
    <?= render_admin_nav('games') ?>
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
      <form class="d-flex gap-2" method="get" role="search">
        <label class="visually-hidden" for="admin-q">Search games</label>
        <input class="form-control" id="admin-q" type="search" name="q" value="<?= e($q) ?>" placeholder="Search title or studio">
        <button class="btn btn-ghost" type="submit"><?= icon('search') ?></button>
      </form>
      <a class="btn btn-amber" href="<?= e(url('admin/game_edit.php')) ?>"><?= icon('plus') ?> Add a game</a>
    </div>

    <p class="small text-soft"><?= plural(count($games), 'game') ?></p>
    <div class="table-wrap">
      <table class="table table-hover">
        <thead><tr><th></th><th>Title</th><th>Genres</th><th>Year</th><th>Rating</th><th>Featured</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
          <?php foreach ($games as $g): ?>
            <tr>
              <td class="admin-cover"><?= render_cover($g, 'sm') ?></td>
              <td><a href="<?= e(url('game.php?id=' . $g['id'])) ?>"><?= e($g['title']) ?></a><div class="small text-soft"><?= e($g['developer']) ?></div></td>
              <td class="small"><?= e(implode(', ', $g['genres'])) ?></td>
              <td><?= $g['release_year'] ?></td>
              <td class="text-nowrap"><?= $g['review_count'] ? number_format($g['avg_rating'], 1) . '★ <span class="text-soft small">(' . $g['review_count'] . ')</span>' : '<span class="text-soft">–</span>' ?></td>
              <td>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="feature">
                  <input type="hidden" name="id" value="<?= $g['id'] ?>">
                  <input type="hidden" name="return_to" value="<?= e(current_path()) ?>">
                  <button class="btn btn-sm <?= $g['is_featured'] ? 'btn-teal' : 'btn-ghost' ?>" type="submit"
                          title="<?= $g['is_featured'] ? 'Remove from homepage' : 'Show on homepage' ?>"><?= $g['is_featured'] ? 'Featured' : 'Feature' ?></button>
                </form>
              </td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-ghost" href="<?= e(url('admin/game_edit.php?id=' . $g['id'])) ?>"><?= icon('edit') ?> Edit</a>
                <form method="post" class="d-inline"
                      data-confirm="Delete “<?= e($g['title']) ?>”? This also deletes its <?= $g['review_count'] ?> review(s) and wishlist entries. This cannot be undone.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $g['id'] ?>">
                  <button class="btn btn-sm btn-ghost text-danger-soft" type="submit"><?= icon('trash') ?> Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$games): ?>
            <tr><td colspan="7" class="text-center text-soft py-4">No games match “<?= e($q) ?>”.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php require APP_ROOT . '/includes/footer.php'; ?>
