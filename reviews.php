<?php
require __DIR__ . '/includes/init.php';

$sort = choice($_GET['sort'] ?? '', array_keys(REVIEW_SORTS), 'newest');
$gameId = int_param($_GET, 'game');
$stars = filter_var($_GET['stars'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]) ?: null;

$where = [];
$params = [];
if ($gameId) {
    $where[] = 'r.game_id = ?';
    $params[] = $gameId;
}
if ($stars) {
    $where[] = 'r.rating = ?';
    $params[] = $stars;
}
$whereSql = implode(' AND ', $where);

$perPage = 10;
$total = (int) db_value('SELECT COUNT(*) FROM reviews r WHERE r.is_hidden = 0' . ($whereSql ? " AND $whereSql" : ''), $params);
$pages = max(1, (int) ceil($total / $perPage));
$page = min(int_param($_GET, 'page') ?? 1, $pages);
$reviews = fetch_reviews($whereSql, $params, $sort, $perPage, ($page - 1) * $perPage);

$gamesWithReviews = db_all('SELECT g.id, g.title FROM games g WHERE EXISTS (SELECT 1 FROM reviews r WHERE r.game_id = g.id AND r.is_hidden = 0) ORDER BY g.title');
$mostReviewed = array_map('hydrate_game', db_all(game_select_sql() . ' WHERE r.review_count > 0 ORDER BY r.review_count DESC, r.avg_rating DESC LIMIT 5'));
$stats = db_one('SELECT COUNT(*) AS reviews, COUNT(DISTINCT user_id) AS reviewers, AVG(rating) AS avg FROM reviews WHERE is_hidden = 0');

$query = fn(array $changes) => url('reviews.php?' . http_build_query(array_filter(array_merge(
    ['sort' => $sort, 'game' => $gameId, 'stars' => $stars, 'page' => $page], $changes))));

$pageTitle = 'Community reviews';
$activeNav = 'community';
require __DIR__ . '/includes/header.php';
?>
<main>
  <header class="page-head">
    <div class="container">
      <span class="eyebrow">Community</span>
      <h1>Community reviews</h1>
      <p><?= plural((int) $stats['reviews'], 'review') ?> from <?= plural((int) $stats['reviewers'], 'player') ?>, with an average rating of
        <?= $stats['reviews'] ? number_format((float) $stats['avg'], 1) : '–' ?>/5. Every number here is calculated from reviews stored on Gaming Hub.</p>
    </div>
  </header>

  <div class="container">
    <div class="row g-4">
      <div class="col-lg-8">
        <form class="panel d-flex flex-wrap gap-2 align-items-end mb-3" method="get" action="<?= e(url('reviews.php')) ?>">
          <div>
            <label class="form-label small" for="r-game">Game</label>
            <select class="form-select" id="r-game" name="game" onchange="this.form.submit()">
              <option value="">All games</option>
              <?php foreach ($gamesWithReviews as $g): ?>
                <option value="<?= (int) $g['id'] ?>" <?= (int) $g['id'] === $gameId ? 'selected' : '' ?>><?= e($g['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label small" for="r-stars">Rating</label>
            <select class="form-select" id="r-stars" name="stars" onchange="this.form.submit()">
              <option value="">Any rating</option>
              <?php for ($s = 5; $s >= 1; $s--): ?>
                <option value="<?= $s ?>" <?= $s === $stars ? 'selected' : '' ?>><?= $s ?> star<?= $s > 1 ? 's' : '' ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div>
            <label class="form-label small" for="r-sort">Sort by</label>
            <select class="form-select" id="r-sort" name="sort" onchange="this.form.submit()">
              <?php foreach (REVIEW_SORTS as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $key === $sort ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <noscript><button class="btn btn-violet" type="submit">Apply</button></noscript>
          <?php if ($gameId || $stars): ?>
            <a class="btn btn-ghost" href="<?= e(url('reviews.php?sort=' . $sort)) ?>">Clear filters</a>
          <?php endif; ?>
        </form>

        <p class="text-soft small"><?= plural($total, 'review') ?><?= $pages > 1 ? " · page $page of $pages" : '' ?></p>
        <?php if ($reviews): ?>
          <?php foreach ($reviews as $r) { echo render_review($r, true); } ?>
          <div class="mt-4"><?= render_pagination($page, $pages, fn($p) => $query(['page' => $p])) ?></div>
        <?php else: ?>
          <div class="empty-state">
            <span class="empty-state__icon"><?= icon('edit') ?></span>
            <h2>No reviews match these filters</h2>
            <p>Try another game or rating, or be the first to review it.</p>
            <a class="btn btn-amber" href="<?= e(url('reviews.php')) ?>">Show all reviews</a>
          </div>
        <?php endif; ?>
      </div>

      <aside class="col-lg-4">
        <section class="panel mb-4">
          <h2 class="h6 text-uppercase text-soft" style="letter-spacing:.1em">Most reviewed</h2>
          <?php foreach ($mostReviewed as $g): ?>
            <a class="mini-game" href="<?= e(url('reviews.php?game=' . $g['id'])) ?>">
              <?= render_cover($g, 'sm') ?>
              <span><strong><?= e($g['title']) ?></strong>
                <small><?= render_stars($g['avg_rating']) ?> <?= number_format($g['avg_rating'], 1) ?> · <?= plural($g['review_count'], 'review') ?></small></span>
            </a>
          <?php endforeach; ?>
        </section>
        <section class="panel">
          <h2 class="h6 text-uppercase text-soft" style="letter-spacing:.1em">Community guidelines</h2>
          <ul class="small text-soft mb-0 ps-3">
            <li>One review per game. You can edit or delete it any time.</li>
            <li>Mark reviews as helpful to move them up the “Most helpful” list.</li>
            <li>Report spam, abuse or major spoilers. Moderators can hide reviews that break the rules.</li>
            <li>Hidden reviews don't count towards a game's rating.</li>
          </ul>
        </section>
      </aside>
    </div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
