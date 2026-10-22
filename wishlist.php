<?php
require __DIR__ . '/includes/init.php';

$user = require_login();

$sort = choice($_GET['sort'] ?? '', ['added', 'title', 'rating'], 'added');
$order = match ($sort) {
    'title'  => 'g.title ASC',
    'rating' => 'avg_rating DESC, g.title ASC',
    default  => 'w.created_at DESC',
};
$games = array_map('hydrate_game', db_all(
    str_replace('FROM games g', 'FROM wishlists w JOIN games g ON g.id = w.game_id', game_select_sql())
    . " WHERE w.user_id = ? ORDER BY $order", [$user['id']]));

$pageTitle = 'My wishlist';
$activeNav = 'wishlist';
require __DIR__ . '/includes/header.php';
?>
<main>
  <header class="page-head">
    <div class="container">
      <span class="eyebrow">Saved games</span>
      <h1>My wishlist</h1>
      <p>Games you want to remember. Use <strong>Compare</strong> on two or three of them to decide which to play first.</p>
    </div>
  </header>

  <div class="container" data-wishlist-page>
    <?php if ($games): ?>
      <div class="results-bar">
        <span class="results-count" data-wishlist-count><?= plural(count($games), 'game') ?></span>
        <div class="d-flex flex-wrap gap-2 align-items-center">
          <span class="small text-soft">Sort:</span>
          <?php foreach (['added' => 'Recently added', 'title' => 'Title', 'rating' => 'Rating'] as $key => $label): ?>
            <a class="tag<?= $key === $sort ? ' tag--teal' : '' ?>" href="<?= e(url('wishlist.php?sort=' . $key)) ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
          <?php if (count($games) >= 2): ?>
            <a class="btn btn-sm btn-violet ms-2" href="<?= e(url('compare.php?ids=' . implode(',', array_slice(array_column($games, 'id'), 0, 3)))) ?>">
              <?= icon('compare') ?> Compare <?= count($games) >= 3 ? 'first three' : 'both' ?></a>
          <?php endif; ?>
        </div>
      </div>
      <div class="game-grid">
        <?php foreach ($games as $g): ?>
          <div data-wishlist-item><?= render_game_card($g) ?></div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <span class="empty-state__icon"><?= icon('heart') ?></span>
        <h2>Your wishlist is empty</h2>
        <p>Tap the heart on any game to save it here. Not sure what to save? The quiz can suggest a few.</p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
          <a class="btn btn-amber" href="<?= e(url('recommend.php')) ?>">Take the quiz</a>
          <a class="btn btn-ghost" href="<?= e(url('explore.php')) ?>">Explore games</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
