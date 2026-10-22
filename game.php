<?php
require __DIR__ . '/includes/init.php';

$id = int_param($_GET, 'id');
$game = $id ? find_game($id) : null;

if ($game === null) {
    http_response_code(404);
    $pageTitle = 'Game not found';
    require __DIR__ . '/includes/header.php';
    ?>
    <main class="container py-5">
      <div class="empty-state">
        <span class="empty-state__icon"><?= icon('search') ?></span>
        <h1 class="h3">We couldn't find that game</h1>
        <p>The link may be broken, or the game may have been removed from Gaming Hub.</p>
        <a class="btn btn-amber" href="<?= e(url('explore.php')) ?>">Explore games</a>
      </div>
    </main>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$user = current_user();

// ------------------------------------------------------------ reviews
$reviewSort = choice($_GET['rsort'] ?? '', array_keys(REVIEW_SORTS), 'newest');
$perPage = 8;
$reviewPages = max(1, (int) ceil($game['review_count'] / $perPage));
$reviewPage = min(int_param($_GET, 'rpage') ?? 1, $reviewPages);
$reviews = fetch_reviews('r.game_id = ?', [$game['id']], $reviewSort, $perPage, ($reviewPage - 1) * $perPage);

$distribution = array_fill(1, 5, 0);
foreach (db_all('SELECT rating, COUNT(*) AS n FROM reviews WHERE game_id = ? AND is_hidden = 0 GROUP BY rating', [$game['id']]) as $row) {
    $distribution[(int) $row['rating']] = (int) $row['n'];
}

$myReview = $user ? db_one('SELECT id, rating, body, is_hidden FROM reviews WHERE game_id = ? AND user_id = ?', [$game['id'], $user['id']]) : null;
$old = $_SESSION['old_review'] ?? null; // values from a form that failed validation
unset($_SESSION['old_review']);
$formRating = (int) ($old['rating'] ?? $myReview['rating'] ?? 0);
$formBody = (string) ($old['body'] ?? $myReview['body'] ?? '');

// ------------------------------------------------------------ similar games (same primary genre)
$similar = array_map('hydrate_game', db_all(
    game_select_sql() . ' WHERE g.id <> ? AND EXISTS (
        SELECT 1 FROM game_genres gg JOIN genres ge ON ge.id = gg.genre_id
         WHERE gg.game_id = g.id AND ge.slug = ?)
     ORDER BY avg_rating DESC, g.title LIMIT 4', [$game['id'], $game['primary_genre']]));

// ------------------------------------------------------------ data for the "why" explanations (assets/js/game.js)
$prefs = $user ? user_quiz_prefs((int) $user['id']) : null;
$pageData = [
    'game'      => game_for_js($game),
    'prefs'     => $prefs,                       // null for guests: game.js then checks sessionStorage
    'genres'    => array_column(all_genres(), 'name', 'slug'),
    'platforms' => array_column(all_platforms(), 'name', 'slug'),
];

$reviewUrl = fn(array $changes) => url('game.php?' . http_build_query(array_merge(
    ['id' => $game['id'], 'rsort' => $reviewSort, 'rpage' => $reviewPage], $changes))) . '#reviews';

$pageTitle = $game['title'];
$activeNav = 'explore';
$pageScripts = ['gamelogic.js', 'game.js'];
require __DIR__ . '/includes/header.php';
?>
<main>
  <section class="game-hero">
    <div class="game-hero__backdrop" style="background-image: url('<?= e(cover_url($game)) ?>')" aria-hidden="true"></div>
    <div class="container">
      <nav aria-label="Breadcrumb" class="small mb-3">
        <a href="<?= e(url('explore.php')) ?>">Explore</a>
        <span class="text-soft"> / </span>
        <a href="<?= e(url('explore.php?genre=' . $game['primary_genre'])) ?>"><?= e(reset($game['genres']) ?: 'Games') ?></a>
        <span class="text-soft"> / <?= e($game['title']) ?></span>
      </nav>
      <div class="row g-4 align-items-center">
        <div class="col-sm-5 col-md-4">
          <div class="game-hero__cover"><?= render_cover($game, 'lg') ?></div>
        </div>
        <div class="col-sm-7 col-md-8">
          <div class="d-flex flex-wrap gap-2 mb-2">
            <?php foreach ($game['genres'] as $slug => $name): ?>
              <a class="tag tag--purple" href="<?= e(url('explore.php?genre=' . $slug)) ?>"><?= e($name) ?></a>
            <?php endforeach; ?>
            <?php if ($game['is_featured']): ?><span class="tag tag--amber">Featured</span><?php endif; ?>
          </div>
          <h1 class="mb-1"><?= e($game['title']) ?></h1>
          <p class="text-soft mb-2"><?= e($game['developer']) ?> · <?= $game['release_year'] ?></p>
          <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
            <?= render_rating_line($game) ?>
            <a class="small" href="#reviews">Read reviews</a>
            <span data-match-badge hidden class="match-badge"></span>
          </div>
          <p class="lead mb-3" style="max-width: 680px; font-size: 1.08rem;"><?= e($game['description']) ?></p>
          <div class="d-flex flex-wrap gap-2 game-actions">
            <?= render_wishlist_button($game, true) ?>
            <button type="button" class="btn compare-btn" data-compare-toggle data-id="<?= $game['id'] ?>"
                    data-title="<?= e($game['title']) ?>" aria-pressed="false"><?= icon('compare') ?><span class="compare-btn__label">Compare</span></button>
            <a class="btn btn-amber" href="#review-form"><?= icon('edit') ?> <?= $myReview ? 'Edit your review' : 'Write a review' ?></a>
          </div>
        </div>
      </div>

      <dl class="facts">
        <div class="fact"><dt>Released</dt><dd><?= $game['release_year'] ?></dd></div>
        <div class="fact"><dt>Platforms</dt><dd><?= e(implode(', ', $game['platforms'])) ?></dd></div>
        <div class="fact"><dt>Game modes</dt><dd><?= e(implode(', ', modes_list($game))) ?></dd></div>
        <div class="fact"><dt>Session length</dt><dd><?= e(session_label($game['session_length'], true)) ?></dd></div>
        <div class="fact"><dt>To finish</dt><dd><?= e(completion_label($game['completion_hours'])) ?></dd></div>
        <div class="fact"><dt>Difficulty</dt><dd><?= e(difficulty_label($game['difficulty_score'])) ?> (<?= $game['difficulty_score'] ?>/10)</dd></div>
      </dl>
    </div>
  </section>

  <div class="container mt-4">
    <div class="row g-4">
      <div class="col-lg-8">
        <!-- Why should I play this? -->
        <section class="panel mb-4" aria-labelledby="why-title">
          <div class="row g-4">
            <div class="col-md-6">
              <h2 class="h5" id="why-title">You might like this game because</h2>
              <ul class="why-list" data-why-list><li class="text-soft">Loading…</li></ul>
            </div>
            <div class="col-md-6">
              <h2 class="h5">Consider this before playing</h2>
              <ul class="why-list why-list--consider" data-consider-list><li class="text-soft">Loading…</li></ul>
            </div>
          </div>
          <p class="small text-soft mt-3 mb-0" data-why-source></p>
          <noscript><p class="small text-soft mt-3 mb-0">Turn on JavaScript to see explanations based on this game's Game DNA.</p></noscript>
        </section>

        <?php if ($game['pros_list'] || $game['cons_list']): ?>
          <section class="panel mb-4 pros-cons" aria-label="Pros and cons">
            <div class="row g-4">
              <div class="col-md-6">
                <h2 class="h6 text-teal text-uppercase" style="letter-spacing:.1em">Pros</h2>
                <ul><?php foreach ($game['pros_list'] as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
              </div>
              <div class="col-md-6">
                <h2 class="h6 text-amber text-uppercase" style="letter-spacing:.1em">Cons</h2>
                <ul><?php foreach ($game['cons_list'] as $c): ?><li><?= e($c) ?></li><?php endforeach; ?></ul>
              </div>
            </div>
          </section>
        <?php endif; ?>

        <!-- Reviews -->
        <section id="reviews" class="mb-4" aria-labelledby="reviews-title">
          <div class="section-head mb-3">
            <h2 id="reviews-title">Community reviews</h2>
          </div>

          <div class="panel mb-4">
            <div class="rating-summary">
              <div class="text-center">
                <div class="rating-summary__big"><?= $game['review_count'] ? number_format($game['avg_rating'], 1) : '–' ?></div>
                <?= render_stars($game['avg_rating'], 'stars--lg') ?>
                <div class="small text-soft mt-1"><?= plural($game['review_count'], 'review') ?></div>
              </div>
              <div class="rating-bars" aria-label="Rating distribution">
                <?php for ($s = 5; $s >= 1; $s--):
                    $pct = $game['review_count'] ? round($distribution[$s] / $game['review_count'] * 100) : 0; ?>
                  <div><span><?= $s ?>★</span><span class="bar"><i style="width: <?= $pct ?>%"></i></span><span><?= $distribution[$s] ?></span></div>
                <?php endfor; ?>
              </div>
            </div>
          </div>

          <div class="panel mb-4" id="review-form">
            <?php if (!$user): ?>
              <p class="mb-0"><a href="<?= e(url('login.php?next=' . urlencode(url('game.php?id=' . $game['id']) . '#review-form'))) ?>">Log in</a>
                or <a href="<?= e(url('register.php')) ?>">create an account</a> to rate and review <?= e($game['title']) ?>.</p>
            <?php else: ?>
              <h3 class="h5"><?= $myReview ? 'Your review' : 'Write a review' ?></h3>
              <?php if ($myReview && $myReview['is_hidden']): ?>
                <p class="small text-amber">A moderator has hidden your review. It is not shown publicly or counted in the rating.</p>
              <?php endif; ?>
              <form method="post" action="<?= e(url('actions/review.php')) ?>" novalidate data-review-form>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="game_id" value="<?= $game['id'] ?>">
                <input type="hidden" name="return_to" value="<?= e(url('game.php?id=' . $game['id']) . '#reviews') ?>">
                <fieldset class="mb-2">
                  <legend class="form-label mb-1">Your rating</legend>
                  <div class="star-input">
                    <?php for ($s = 5; $s >= 1; $s--): ?>
                      <input type="radio" id="star<?= $s ?>" name="rating" value="<?= $s ?>" <?= $formRating === $s ? 'checked' : '' ?> required>
                      <label for="star<?= $s ?>" title="<?= $s ?> star<?= $s > 1 ? 's' : '' ?>">★<span class="visually-hidden"><?= $s ?> stars</span></label>
                    <?php endfor; ?>
                  </div>
                </fieldset>
                <label class="form-label" for="review-body">Your review</label>
                <textarea class="form-control" id="review-body" name="body" rows="4" minlength="10" maxlength="2000" required
                          placeholder="What did you enjoy? What should other players know?"><?= e($formBody) ?></textarea>
                <div class="form-text mb-3">10–2000 characters. Please avoid major spoilers.</div>
                <div class="d-flex gap-2 flex-wrap">
                  <button class="btn btn-amber" type="submit"><?= $myReview ? 'Update review' : 'Post review' ?></button>
                </div>
              </form>
              <?php if ($myReview): ?>
                <form method="post" action="<?= e(url('actions/review.php')) ?>" class="mt-2" data-confirm="Delete your review? This cannot be undone.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="review_id" value="<?= (int) $myReview['id'] ?>">
                  <input type="hidden" name="return_to" value="<?= e(url('game.php?id=' . $game['id']) . '#reviews') ?>">
                  <button class="btn btn-sm btn-ghost text-danger-soft" type="submit"><?= icon('trash') ?> Delete my review</button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
          </div>

          <?php if ($reviews): ?>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
              <span class="small text-soft me-1">Sort:</span>
              <?php foreach (REVIEW_SORTS as $key => $label): ?>
                <a class="tag<?= $key === $reviewSort ? ' tag--teal' : '' ?>" href="<?= e($reviewUrl(['rsort' => $key, 'rpage' => 1])) ?>"
                   <?= $key === $reviewSort ? 'aria-current="true"' : '' ?>><?= e($label) ?></a>
              <?php endforeach; ?>
            </div>
            <?php foreach ($reviews as $r) { echo render_review($r); } ?>
            <div class="mt-3"><?= render_pagination($reviewPage, $reviewPages, fn($p) => $reviewUrl(['rpage' => $p])) ?></div>
          <?php else: ?>
            <div class="empty-state">
              <span class="empty-state__icon"><?= icon('edit') ?></span>
              <h3>No reviews yet</h3>
              <p>Played <?= e($game['title']) ?>? Your review will help others decide.</p>
            </div>
          <?php endif; ?>
        </section>
      </div>

      <aside class="col-lg-4">
        <section class="panel mb-4" aria-labelledby="dna-title">
          <h2 class="h5" id="dna-title">Game DNA</h2>
          <?= render_dna_radar($game) ?>
          <div class="mt-3"><?= render_dna($game) ?></div>
          <p class="dna-note"><?= icon('sparkle') ?> Editorial estimates by the Gaming Hub team on a 1–10 scale. Not official developer statistics.</p>
        </section>

        <?php if ($similar): ?>
          <section class="panel" aria-labelledby="similar-title">
            <h2 class="h6 text-uppercase text-soft" id="similar-title" style="letter-spacing:.1em">More <?= e(reset($game['genres'])) ?> games</h2>
            <?php foreach ($similar as $s): ?>
              <a class="mini-game" href="<?= e(url('game.php?id=' . $s['id'])) ?>">
                <?= render_cover($s, 'sm') ?>
                <span><strong><?= e($s['title']) ?></strong>
                  <small><?= $s['review_count'] ? render_stars($s['avg_rating']) . ' ' . number_format($s['avg_rating'], 1) : 'No ratings yet' ?> · <?= $s['release_year'] ?></small></span>
              </a>
            <?php endforeach; ?>
          </section>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</main>
<script type="application/json" id="game-data"><?= json_for_script($pageData) ?></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
