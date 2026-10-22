<?php
require __DIR__ . '/includes/init.php';

$f = explore_filters($_GET);
[$where, $params] = explore_where($f);

$perPage = 12;
$from = " FROM games g JOIN game_attributes a ON a.game_id = g.id LEFT JOIN v_game_ratings r ON r.game_id = g.id WHERE $where";
$total = (int) db_value("SELECT COUNT(*) $from", $params);
$pages = max(1, (int) ceil($total / $perPage));
$page = min($f['page'], $pages);
$games = array_map('hydrate_game', db_all(
    game_select_sql() . " WHERE $where ORDER BY " . explore_order($f['sort'])
    . " LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params));

$moods = [
    'adventure' => 'I want an adventure — showing games where exploration scores 8 or more.',
    'story'     => 'I want a good story — showing games where story scores 8 or more.',
    'challenge' => 'I want a challenge — showing games with difficulty 7 or more.',
    'relax'     => 'I want something relaxing — showing games with difficulty 3 or less.',
    'friends'   => 'I want to play with friends — showing games with co-op or multiplayer.',
    'short'     => 'I want a short session — showing games that suit sessions under an hour.',
];
$mood = choice($_GET['mood'] ?? '', array_keys($moods));
$active = explore_active_filters($f);

/** The results area. Also returned on its own for live filtering (?partial=1). */
function explore_results(array $games, array $f, array $active, int $total, int $page, int $pages, string $mood, array $moods): void
{
    ?>
    <?php if ($mood !== ''): ?>
      <div class="mood-banner"><?= icon('sparkle') ?> <span><?= e($moods[$mood]) ?></span></div>
    <?php endif; ?>
    <div class="results-bar">
      <span class="results-count" role="status">
        <?= $total === 0 ? 'No games found' : plural($total, 'game') . ' found' ?>
        <?php if ($pages > 1): ?><span class="text-soft fw-normal"> · page <?= $page ?> of <?= $pages ?></span><?php endif; ?>
      </span>
    </div>
    <?php if ($active): ?>
      <div class="active-filters" aria-label="Active filters">
        <?php foreach ($active as $key => $label): ?>
          <a class="tag tag--purple" data-filter-link href="<?= e(url('explore.php' . explore_query($f, [$key => null, 'page' => null]))) ?>"
             title="Remove this filter"><?= e($label) ?> <?= icon('x') ?></a>
        <?php endforeach; ?>
        <a class="tag" data-filter-link href="<?= e(url('explore.php' . explore_query(['sort' => $f['sort']]))) ?>">Clear all</a>
      </div>
    <?php endif; ?>

    <?php if ($games): ?>
      <div class="game-grid">
        <?php foreach ($games as $g) { echo render_game_card($g); } ?>
      </div>
      <div class="mt-4">
        <?= render_pagination($page, $pages, fn($p) => url('explore.php' . explore_query($f, ['page' => $p]))) ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <span class="empty-state__icon"><?= icon('search') ?></span>
        <h2>No games match all of these filters</h2>
        <p>Try removing one of the filters above. Combining strict filters, such as “Hard difficulty” with
           “Relaxing”, can leave no matches.</p>
        <a class="btn btn-amber" data-filter-link href="<?= e(url('explore.php')) ?>">Show all games</a>
      </div>
    <?php endif;
}

if (isset($_GET['partial'])) {
    header('Content-Type: text/html; charset=utf-8');
    explore_results($games, $f, $active, $total, $page, $pages, $mood, $moods);
    exit;
}

$pageTitle = 'Explore Games';
$activeNav = 'explore';
require __DIR__ . '/includes/header.php';

$select = function (string $name, string $label, array $options, string $current, string $anyLabel = 'Any'): void {
    ?>
    <div class="filters__group">
      <label class="form-label" for="f-<?= e($name) ?>"><?= e($label) ?></label>
      <select class="form-select" id="f-<?= e($name) ?>" name="<?= e($name) ?>">
        <option value=""><?= e($anyLabel) ?></option>
        <?php foreach ($options as $value => $text): ?>
          <option value="<?= e($value) ?>" <?= (string) $value === $current ? 'selected' : '' ?>><?= e($text) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php
};
?>
<main>
  <header class="page-head">
    <div class="container">
      <span class="eyebrow">Game Explorer</span>
      <h1>Explore games</h1>
      <p>Search and filter our library by genre, platform, how you play and what you enjoy most. Results update as you change filters.</p>
    </div>
  </header>

  <div class="container">
    <div class="row g-4">
      <div class="col-lg-3">
        <button class="btn btn-ghost w-100 d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel"
                aria-expanded="false" aria-controls="filterPanel"><?= icon('filter') ?> Show filters</button>
        <div class="collapse d-lg-block" id="filterPanel">
          <form class="panel filters" id="explore-form" action="<?= e(url('explore.php')) ?>" method="get" data-explore-form>
            <div class="filters__group">
              <label class="form-label" for="f-q">Search</label>
              <input class="form-control" type="search" id="f-q" name="q" value="<?= e($f['q']) ?>" placeholder="Title or studio" autocomplete="off">
            </div>
            <?php
            $select('genre', 'Genre', array_column(all_genres(), 'name', 'slug'), $f['genre']);
            $select('platform', 'Platform', array_column(all_platforms(), 'name', 'slug'), $f['platform']);
            $select('mode', 'Players', ['single' => 'Single-player', 'coop' => 'Co-op', 'multi' => 'Multiplayer',
                                        'together' => 'Any way with friends'], $f['mode']);
            $select('difficulty', 'Difficulty', ['easy' => 'Relaxed (1–3)', 'moderate' => 'Moderate (4–6)', 'hard' => 'Hard (7–10)'], $f['difficulty']);
            $select('focus', 'Focus', ['story' => 'Story-focused (story 8+)', 'combat' => 'Combat-heavy (combat 8+)',
                                       'exploration' => 'Exploration-heavy (exploration 8+)'], $f['focus']);
            $select('session', 'Session length', ['short' => 'Short (under 1 hour)', 'medium' => 'Medium (1–2 hours)',
                                                  'long' => 'Long (2+ hours)'], $f['session']);
            $select('min_rating', 'Community rating', ['4.5' => '4.5★ and up', '4' => '4★ and up', '3.5' => '3.5★ and up', '3' => '3★ and up'], $f['min_rating']);
            ?>
            <div class="filters__group">
              <label class="form-label" for="f-sort">Sort by</label>
              <select class="form-select" id="f-sort" name="sort">
                <?php foreach (EXPLORE_SORTS as $value => $text): ?>
                  <option value="<?= e($value) ?>" <?= $value === $f['sort'] ? 'selected' : '' ?>><?= e($text) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="d-flex gap-2 mt-4">
              <button class="btn btn-violet flex-fill" type="submit">Apply</button>
              <a class="btn btn-ghost" href="<?= e(url('explore.php')) ?>" data-clear-filters>Clear filters</a>
            </div>
          </form>
        </div>
      </div>

      <div class="col-lg-9">
        <div class="results" data-results aria-live="polite">
          <?php explore_results($games, $f, $active, $total, $page, $pages, $mood, $moods); ?>
        </div>
      </div>
    </div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
