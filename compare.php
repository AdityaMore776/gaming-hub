<?php
require __DIR__ . '/includes/init.php';

const COMPARE_MAX = 3;

/** Parse "3,7,12" into up to three unique positive ids. */
function parse_ids(mixed $raw): array
{
    if (!is_string($raw)) {
        return [];
    }
    $ids = array_filter(array_map('intval', explode(',', $raw)), fn($id) => $id > 0);
    return array_slice(array_values(array_unique($ids)), 0, COMPARE_MAX);
}

$ids = parse_ids($_GET['ids'] ?? '');

// Add / remove / replace work through simple links and forms, so they also work without JavaScript.
$changed = false;
if ($add = int_param($_GET, 'add')) {
    if (!in_array($add, $ids, true) && count($ids) < COMPARE_MAX) {
        $ids[] = $add;
    }
    $changed = true;
}
if ($remove = int_param($_GET, 'remove')) {
    $ids = array_values(array_diff($ids, [$remove]));
    $changed = true;
}
if (($old = int_param($_GET, 'replace')) && ($new = int_param($_GET, 'with'))) {
    if (!in_array($new, $ids, true)) {
        $ids = array_map(fn($id) => $id === $old ? $new : $id, $ids);
    }
    $changed = true;
}
if ($changed) {
    redirect('compare.php' . ($ids ? '?ids=' . implode(',', $ids) : '?cleared=1'));
}

$games = find_games($ids);                 // skips ids that do not exist
$ids = array_column($games, 'id');
$count = count($games);
$allGames = db_all('SELECT id, title FROM games ORDER BY title');
$choices = array_filter($allGames, fn($g) => !in_array((int) $g['id'], $ids, true));
$compareUrl = fn(array $params) => url('compare.php?' . http_build_query($params));

// ------------------------------------------------------------ which cells to highlight
/** Index of the clearly highest value, or null when values are tied or too close. */
function top_index(array $values, float $minGap): ?int
{
    if (count($values) < 2) {
        return null;
    }
    $sorted = $values;
    rsort($sorted);
    if ($sorted[0] - $sorted[1] < $minGap) {
        return null;
    }
    return array_search($sorted[0], $values, true);
}

$tops = [];
if ($count >= 2) {
    foreach (['story', 'combat', 'exploration', 'replayability'] as $key) {
        // Same thresholds as the written verdict in compare.js: 2 points on DNA, 3 on difficulty, 0.3 stars.
        $tops[$key] = top_index(array_map(fn($g) => $g['dna'][$key], $games), 2);
    }
    $tops['difficulty'] = top_index(array_map(fn($g) => $g['dna']['difficulty'], $games), 3);
    // Ratings are only comparable when every game has at least two reviews.
    $ratingsComparable = min(array_column($games, 'review_count')) >= 2;
    $tops['rating'] = $ratingsComparable ? top_index(array_map(fn($g) => $g['avg_rating'], $games), 0.3) : null;
}
$cellClass = fn(string $key, int $i) => (isset($tops[$key]) && $tops[$key] === $i)
    ? ($key === 'difficulty' ? 'cmp-top cmp-top--neutral' : 'cmp-top') : '';

$pageTitle = $count ? 'Compare: ' . implode(' vs ', array_column($games, 'title')) : 'Compare games';
$activeNav = 'compare';
$pageScripts = ['gamelogic.js', 'compare.js'];
require __DIR__ . '/includes/header.php';

$picker = function (string $label) use ($choices, $ids, $count): void {
    if ($count >= COMPARE_MAX) {
        return;
    }
    ?>
    <form class="compare-picker" method="get" action="<?= e(url('compare.php')) ?>">
      <input type="hidden" name="ids" value="<?= e(implode(',', $ids)) ?>">
      <label class="visually-hidden" for="add-game"><?= e($label) ?></label>
      <select class="form-select" id="add-game" name="add" required>
        <option value=""><?= e($label) ?></option>
        <?php foreach ($choices as $c): ?>
          <option value="<?= (int) $c['id'] ?>"><?= e($c['title']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-violet" type="submit"><?= icon('plus') ?> Add</button>
    </form>
    <?php
};

$dnaCell = function (array $g, string $key, int $i) use ($cellClass): string {
    $score = $g['dna'][$key];
    return '<td class="' . $cellClass($key, $i) . '"><div class="cmp-bar dna--' . $key . '">'
        . '<span class="dna__track"><span class="dna__fill" style="width:' . ($score * 10) . '%"></span></span>'
        . '<strong>' . $score . '</strong>'
        . ($key === 'difficulty' ? '<span class="small text-soft">' . e(difficulty_label($score)) . '</span>' : '')
        . '</div></td>';
};
$yesNo = fn(bool $v) => $v ? '<span class="cmp-yes">' . icon('check') . ' Yes</span>' : '<span class="cmp-no">No</span>';
?>
<main>
  <header class="page-head">
    <div class="container">
      <span class="eyebrow">Side-by-side comparison</span>
      <h1>Compare games</h1>
      <p>Line up two or three games and see where they differ. Highlights only appear when a difference is clear,
         and no game is declared the overall winner, because the right choice depends on what you enjoy.</p>
    </div>
  </header>

  <div class="container" data-compare-page data-ids="<?= e(implode(',', $ids)) ?>">
    <?php if ($count < 2): ?>
      <div class="empty-state mb-4">
        <span class="empty-state__icon"><?= icon('compare') ?></span>
        <h2><?= $count === 0 ? 'Pick two or three games to compare' : 'Add one more game to compare' ?></h2>
        <p><?= $count === 0
            ? 'Use the Compare button on any game card, or choose games here.'
            : 'You have selected ' . e($games[0]['title']) . '. Choose at least one more game.' ?></p>
        <div class="d-flex justify-content-center"><?php $picker('Choose a game…'); ?></div>
        <p class="small text-soft mt-3 mb-0">Not sure where to start? <a href="<?= e(url('explore.php')) ?>">Explore games</a> or try
          <a href="<?= e(url('compare.php?ids=4,1,36')) ?>">Hades vs Hollow Knight vs Dead Cells</a>.</p>
      </div>
      <?php if ($count === 1): ?>
        <div class="game-grid" style="max-width: 260px"><?= render_game_card($games[0]) ?></div>
      <?php endif; ?>
    <?php else: ?>
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <p class="mb-0 text-soft"><?= $count ?> of <?= COMPARE_MAX ?> games selected.
          <?= $count < COMPARE_MAX ? 'You can add one more.' : 'Remove or replace a game to compare others.' ?></p>
        <?php $picker('Add a third game…'); ?>
      </div>

      <div class="compare-scroll">
        <table class="compare-table">
          <caption class="visually-hidden">Comparison of <?= e(implode(', ', array_column($games, 'title'))) ?></caption>
          <thead>
            <tr>
              <th scope="row"><span class="visually-hidden">Game</span></th>
              <?php foreach ($games as $g): ?>
                <td class="compare-col-head">
                  <a href="<?= e(url('game.php?id=' . $g['id'])) ?>"><?= render_cover($g) ?></a>
                  <h2><a class="text-white text-decoration-none" href="<?= e(url('game.php?id=' . $g['id'])) ?>"><?= e($g['title']) ?></a></h2>
                  <div class="d-flex flex-wrap gap-1 mb-2">
                    <?= render_wishlist_button($g) ?>
                    <a class="btn btn-sm btn-ghost" href="<?= e($compareUrl(['ids' => implode(',', $ids), 'remove' => $g['id']])) ?>"
                       data-compare-remove-link="<?= $g['id'] ?>"><?= icon('x') ?> Remove</a>
                  </div>
                  <form method="get" action="<?= e(url('compare.php')) ?>" class="d-flex gap-1" data-replace-form>
                    <input type="hidden" name="ids" value="<?= e(implode(',', $ids)) ?>">
                    <input type="hidden" name="replace" value="<?= $g['id'] ?>">
                    <label class="visually-hidden" for="replace-<?= $g['id'] ?>">Replace <?= e($g['title']) ?> with</label>
                    <select class="form-select form-select-sm" id="replace-<?= $g['id'] ?>" name="with" required>
                      <option value="">Replace with…</option>
                      <?php foreach ($choices as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= e($c['title']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-ghost" type="submit">Go</button>
                  </form>
                </td>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <tr class="group-row"><th colspan="<?= $count + 1 ?>">The basics</th></tr>
            <tr><th scope="row">Genre</th><?php foreach ($games as $g): ?><td><?= e(implode(', ', $g['genres'])) ?></td><?php endforeach; ?></tr>
            <tr><th scope="row">Release year</th><?php foreach ($games as $g): ?><td><?= $g['release_year'] ?></td><?php endforeach; ?></tr>
            <tr><th scope="row">Platforms</th><?php foreach ($games as $g): ?><td><?= e(implode(', ', $g['platforms'])) ?></td><?php endforeach; ?></tr>
            <tr><th scope="row">Single-player</th><?php foreach ($games as $g): ?><td><?= $yesNo((bool) $g['has_single_player']) ?></td><?php endforeach; ?></tr>
            <tr><th scope="row">Co-op</th><?php foreach ($games as $g): ?><td><?= $yesNo((bool) $g['has_coop']) ?></td><?php endforeach; ?></tr>
            <tr><th scope="row">Multiplayer</th><?php foreach ($games as $g): ?><td><?= $yesNo((bool) $g['has_multiplayer']) ?></td><?php endforeach; ?></tr>
            <tr><th scope="row">Session length</th><?php foreach ($games as $g): ?><td><?= e(session_label($g['session_length'], true)) ?></td><?php endforeach; ?></tr>
            <tr><th scope="row">Time to finish</th><?php foreach ($games as $g): ?><td><?= e(completion_label($g['completion_hours'])) ?></td><?php endforeach; ?></tr>

            <tr class="group-row"><th colspan="<?= $count + 1 ?>">Game DNA (1–10, editorial estimates)</th></tr>
            <?php foreach (DNA_TRAITS as $key => $label): ?>
              <tr><th scope="row"><?= e($label) ?></th><?php foreach ($games as $i => $g) { echo $dnaCell($g, $key, $i); } ?></tr>
            <?php endforeach; ?>

            <tr class="group-row"><th colspan="<?= $count + 1 ?>">Community</th></tr>
            <tr>
              <th scope="row">Rating</th>
              <?php foreach ($games as $i => $g): ?>
                <td class="<?= $cellClass('rating', $i) ?>"><?= render_rating_line($g) ?></td>
              <?php endforeach; ?>
            </tr>
            <tr><th scope="row">Reviews</th><?php foreach ($games as $g): ?><td><?= plural($g['review_count'], 'review') ?></td><?php endforeach; ?></tr>
          </tbody>
        </table>
      </div>
      <?php if (!$ratingsComparable): ?>
        <p class="small text-soft mt-2">Ratings are not highlighted because at least one game has fewer than two reviews.</p>
      <?php endif; ?>

      <section class="panel verdict mt-4" aria-labelledby="verdict-title">
        <h2 class="h5" id="verdict-title">Quick verdict</h2>
        <div data-verdict><p class="text-soft mb-0">Working out the differences…</p></div>
        <p class="small text-soft mt-3 mb-0">Generated with simple rules from the values in the table above, not by AI.</p>
        <noscript><p class="small text-soft mb-0">Turn on JavaScript to see the written verdict.</p></noscript>
      </section>

      <div class="d-flex flex-wrap gap-2 mt-4">
        <a class="btn btn-ghost" href="<?= e(url('explore.php')) ?>">Find more games</a>
        <a class="btn btn-ghost" href="<?= e(url('compare.php?cleared=1')) ?>" data-compare-reset><?= icon('trash') ?> Clear comparison</a>
      </div>
    <?php endif; ?>
  </div>
</main>
<script type="application/json" id="compare-data"><?= json_for_script(array_map('game_for_js', $games)) ?></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
