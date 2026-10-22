<?php
require __DIR__ . '/includes/init.php';

$featured = array_map('hydrate_game', db_all(game_select_sql() . ' WHERE g.is_featured = 1 ORDER BY avg_rating DESC, g.title LIMIT 8'));

// Hero composition and the Game DNA spotlight both use featured games.
$heroGames = array_slice($featured, 0, 3);
$spotlight = $featured[(int) date('z') % max(1, count($featured))] ?? null; // changes daily

// Recent, helpful reviews that nobody has reported.
$latestReviews = fetch_reviews(
    "CHAR_LENGTH(r.body) BETWEEN 40 AND 220 AND r.created_at >= DATE_SUB(NOW(), INTERVAL 45 DAY)
     AND NOT EXISTS (SELECT 1 FROM review_reports rp WHERE rp.review_id = r.id AND rp.status = 'open')",
    [], 'helpful', 3);
$topRated = array_map('hydrate_game', db_all(
    game_select_sql() . ' WHERE r.review_count >= 3 ORDER BY r.avg_rating DESC, r.review_count DESC LIMIT 5'));
$totalGames = (int) db_value('SELECT COUNT(*) FROM games');

$moods = [
    ['I want an adventure', 'Big worlds made for exploring', 'compass', '#35C6B0', 'explore.php?focus=exploration&mood=adventure'],
    ['I want a good story', 'Characters and writing first', 'book', '#A08BEE', 'explore.php?focus=story&mood=story'],
    ['I want a challenge', 'Hard games that reward practice', 'bolt', '#FF8A70', 'explore.php?difficulty=hard&mood=challenge'],
    ['I want something relaxing', 'Low pressure, easy to enjoy', 'leaf', '#7FD88A', 'explore.php?difficulty=easy&mood=relax'],
    ['I want to play with friends', 'Co-op and multiplayer picks', 'users', '#FFB454', 'explore.php?mode=together&mood=friends'],
    ['I want a short session', 'Fits into under an hour', 'clock', '#6FA8FF', 'explore.php?session=short&mood=short'],
];

$activeNav = 'home';
require __DIR__ . '/includes/header.php';
?>
<main>
  <!-- Hero -->
  <section class="hero">
    <div class="container">
      <div class="row align-items-center g-4">
        <div class="col-lg-6">
          <span class="eyebrow">Game discovery &amp; comparison</span>
          <h1>Your Next Favourite Game <em>Is Out There.</em></h1>
          <p class="hero__lead">Discover games that match your taste, compare your options, and find your next adventure.</p>
          <form class="hero-search" action="<?= e(url('explore.php')) ?>" method="get" role="search">
            <label class="visually-hidden" for="hero-q">Search games</label>
            <input class="form-control" type="search" id="hero-q" name="q" placeholder="Search <?= $totalGames ?> games by title or studio…">
            <button class="btn btn-violet" type="submit"><?= icon('search') ?><span class="d-none d-sm-inline">Search</span></button>
          </form>
          <div class="hero__actions">
            <a class="btn btn-amber btn-lg" href="<?= e(url('recommend.php')) ?>"><?= icon('sparkle') ?> What Should I Play?</a>
            <a class="btn btn-ghost btn-lg" href="<?= e(url('explore.php')) ?>">Explore Games</a>
          </div>
          <p class="hero__note">Compare up to three games side by side, or answer seven quick questions to get matches with reasons.</p>
        </div>
        <div class="col-lg-6">
          <?php if ($heroGames): ?>
            <div class="hero-stack" aria-label="Featured games">
              <?php foreach ($heroGames as $g): ?>
                <a class="hero-stack__card" href="<?= e(url('game.php?id=' . $g['id'])) ?>" title="<?= e($g['title']) ?>">
                  <?= render_cover($g, 'hero') ?>
                </a>
              <?php endforeach; ?>
              <?php $hg = $heroGames[1] ?? $heroGames[0]; ?>
              <div class="hero-dna">
                <div class="hero-dna__title">Game DNA · <?= e($hg['title']) ?></div>
                <?= render_dna($hg, true) ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Featured games -->
  <section class="section section--tight">
    <div class="container">
      <div class="section-head">
        <div>
          <span class="eyebrow">Featured</span>
          <h2>Worth your time right now</h2>
          <p>Picked by our team for variety: different genres, lengths and difficulty levels.</p>
        </div>
        <a class="btn btn-ghost" href="<?= e(url('explore.php')) ?>">Browse all games <?= icon('arrow') ?></a>
      </div>
      <?php if ($featured): ?>
        <div class="game-grid">
          <?php foreach ($featured as $g) { echo render_game_card($g); } ?>
        </div>
      <?php else: ?>
        <div class="empty-state"><h3>No featured games yet</h3><p>An admin can mark games as featured in the admin panel.</p></div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Discover by mood -->
  <section class="section">
    <div class="container">
      <div class="section-head">
        <div>
          <span class="eyebrow">Discover by mood</span>
          <h2>What are you in the mood for?</h2>
          <p>Each option opens the explorer with matching filters already applied.</p>
        </div>
      </div>
      <div class="mood-grid">
        <?php foreach ($moods as [$title, $sub, $ico, $color, $href]): ?>
          <a class="mood-card" href="<?= e(url($href)) ?>" style="--mood: <?= e($color) ?>">
            <span class="mood-card__icon"><?= icon($ico) ?></span>
            <span><strong><?= e($title) ?></strong><small><?= e($sub) ?></small></span>
            <?= icon('chevron', 'icon-arrow') ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Game DNA preview -->
  <?php if ($spotlight): ?>
  <section class="section">
    <div class="container">
      <div class="panel panel--raised p-4 p-lg-5">
        <div class="row g-4 align-items-center">
          <div class="col-lg-5">
            <span class="eyebrow">Game DNA</span>
            <h2>More than a star rating</h2>
            <p class="text-soft">Two games can both be rated 4.5 and feel completely different to play. Game DNA describes
              what a game is <em>like</em> with five scores from 1 to 10, so you can tell before you start.</p>
            <div class="dna-explainer">
              <div style="--c: var(--dna-story)"><strong>Story</strong><span>How much plot and character drive the game</span></div>
              <div style="--c: var(--dna-combat)"><strong>Combat</strong><span>How central fighting is</span></div>
              <div style="--c: var(--dna-exploration)"><strong>Exploration</strong><span>Freedom to roam and discover</span></div>
              <div style="--c: var(--dna-difficulty)"><strong>Difficulty</strong><span>How demanding it is to progress</span></div>
              <div style="--c: var(--dna-replayability)"><strong>Replayability</strong><span>Reasons to keep coming back</span></div>
            </div>
          </div>
          <div class="col-lg-7">
            <div class="row g-4 align-items-center">
              <div class="col-sm-5">
                <a href="<?= e(url('game.php?id=' . $spotlight['id'])) ?>"><?= render_cover($spotlight) ?></a>
              </div>
              <div class="col-sm-7">
                <p class="small text-soft mb-1">Today's spotlight</p>
                <h3 class="h4"><a class="text-white text-decoration-none" href="<?= e(url('game.php?id=' . $spotlight['id'])) ?>"><?= e($spotlight['title']) ?></a></h3>
                <p class="small text-soft"><?= e(implode(' · ', $spotlight['genres'])) ?> · <?= e(session_label($spotlight['session_length'], true)) ?></p>
                <?= render_dna($spotlight) ?>
                <p class="dna-note"><?= icon('sparkle') ?> Scores are curated estimates by the Gaming Hub team, not official developer statistics.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Community -->
  <section class="section">
    <div class="container">
      <div class="section-head">
        <div>
          <span class="eyebrow">Community</span>
          <h2>What players are saying</h2>
          <p>Recent reviews and the best-rated games, calculated from reviews stored on Gaming Hub.</p>
        </div>
        <a class="btn btn-ghost" href="<?= e(url('reviews.php')) ?>">All reviews <?= icon('arrow') ?></a>
      </div>
      <div class="row g-4">
        <div class="col-lg-8">
          <?php if ($latestReviews): ?>
            <div class="row g-3">
              <?php foreach ($latestReviews as $r): ?>
                <div class="col-md-4">
                  <figure class="review-snippet">
                    <?= render_stars((float) $r['rating']) ?>
                    <blockquote>“<?= e($r['body']) ?>”</blockquote>
                    <footer>
                      <span><?= e($r['username']) ?></span>
                      <a href="<?= e(url('game.php?id=' . $r['game_id'])) ?>#reviews"><?= e($r['game_title']) ?></a>
                    </footer>
                  </figure>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="empty-state"><h3>No reviews yet</h3><p>Be the first to review a game.</p></div>
          <?php endif; ?>
        </div>
        <div class="col-lg-4">
          <div class="panel h-100">
            <h3 class="h6 text-uppercase text-soft mb-3" style="letter-spacing:.1em">Top rated (3+ reviews)</h3>
            <?php foreach ($topRated as $i => $g): ?>
              <a class="mini-game" href="<?= e(url('game.php?id=' . $g['id'])) ?>">
                <?= render_cover($g, 'sm') ?>
                <span>
                  <strong><?= $i + 1 ?>. <?= e($g['title']) ?></strong>
                  <small><?= render_stars($g['avg_rating']) ?> <?= number_format($g['avg_rating'], 1) ?> · <?= plural($g['review_count'], 'review') ?></small>
                </span>
              </a>
            <?php endforeach; ?>
            <?php if (!$topRated): ?><p class="text-soft mb-0">Not enough reviews yet.</p><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
