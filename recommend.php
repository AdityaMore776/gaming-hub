<?php
require __DIR__ . '/includes/init.php';

$user = current_user();
$games = array_map(fn($row) => game_for_js(hydrate_game($row)), db_all(game_select_sql() . ' ORDER BY g.title'));

$genreOptions = [];
foreach (all_genres() as $g) {
    $genreOptions[$g['slug']] = [$g['name'], ''];
}
$platformOptions = [];
foreach (all_platforms() as $p) {
    $platformOptions[$p['slug']] = [$p['name'], ''];
}

// Each step: [key, question, help text, options => [value => [label, description]], "any" label]
$steps = [
    ['genre', 'Which genre do you enjoy most?', 'Pick the one you would choose first.', $genreOptions, 'No preference'],
    ['mood', 'What are you in the mood for right now?', 'Your mood changes which Game DNA traits count most.', [
        'adventure' => ['An adventure', 'Somewhere new to explore'],
        'story'     => ['A good story', 'Characters and writing'],
        'challenge' => ['A challenge', 'Something that pushes back'],
        'relax'     => ['Something relaxing', 'Low pressure, calm'],
        'social'    => ['Time with friends', 'Play together'],
        'quick'     => ['A quick session', 'Jump in and out'],
    ], 'Not sure'],
    ['play_style', 'How do you want to play?', 'Co-op means playing together on the same side.', [
        'solo'        => ['Solo', 'Just me'],
        'coop'        => ['Co-op', 'Team up with friends'],
        'multiplayer' => ['Multiplayer', 'Compete with other players'],
    ], 'Any of these'],
    ['difficulty', 'How difficult should it be?', 'Based on each game\'s difficulty score from 1 to 10.', [
        'relaxed'     => ['Relaxed', 'I want to enjoy it, not struggle'],
        'balanced'    => ['Balanced', 'Some challenge is good'],
        'challenging' => ['Challenging', 'I like hard games'],
    ], 'Doesn\'t matter'],
    ['session', 'How much time do you usually have to play?', 'Some games need long sittings to make progress.', [
        'short'  => ['Under an hour', 'Short sessions'],
        'medium' => ['1–2 hours', 'An evening session'],
        'long'   => ['2+ hours', 'Long sessions'],
    ], 'It varies'],
    ['focus', 'What matters most to you in a game?', 'This compares against the story, combat and exploration scores.', [
        'story'       => ['Story', 'Plot and characters'],
        'combat'      => ['Combat', 'Fighting and action'],
        'exploration' => ['Exploration', 'Discovering the world'],
    ], 'A bit of everything'],
    ['platform', 'Which platform will you play on?', 'Games that are not on your platform score lower.', $platformOptions, 'Any platform'],
];

$pageData = [
    'games'     => $games,
    'genres'    => array_column(all_genres(), 'name', 'slug'),
    'platforms' => array_column(all_platforms(), 'name', 'slug'),
    'saved'     => $user ? user_quiz_prefs((int) $user['id']) : null,
    'wishlist'  => wishlist_ids(),
    'steps'     => array_map(fn($s) => ['key' => $s[0], 'question' => $s[1],
                                        'options' => array_map(fn($o) => $o[0], $s[3])], $steps),
];

$pageTitle = 'What Should I Play?';
$activeNav = 'recommend';
$pageScripts = ['gamelogic.js', 'recommend.js'];
require __DIR__ . '/includes/header.php';
?>
<main>
  <header class="page-head">
    <div class="container">
      <span class="eyebrow">Recommendation quiz</span>
      <h1>What should I play?</h1>
      <p>Answer seven quick questions. We compare your answers with every game's Game DNA and show your three best
         matches, with the reasons and one possible drawback for each.</p>
    </div>
  </header>

  <div class="container">
    <noscript>
      <div class="empty-state mb-4">
        <h2>The quiz needs JavaScript</h2>
        <p>Turn on JavaScript to take the quiz, or use the explorer filters instead.</p>
        <a class="btn btn-amber" href="<?= e(url('explore.php')) ?>">Explore games</a>
      </div>
    </noscript>

    <div class="quiz" data-quiz>
      <!-- Start screen -->
      <section class="panel p-4 p-md-5 text-center" data-quiz-intro>
        <h2 class="h3">Find games that fit how you like to play</h2>
        <p class="text-soft mx-auto" style="max-width: 540px">Every question has a “no preference” option. Questions you
          skip are left out of the score instead of counting against a game.</p>
        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
          <button class="btn btn-amber btn-lg" type="button" data-quiz-start><?= icon('sparkle') ?> Start the quiz</button>
          <button class="btn btn-ghost btn-lg" type="button" data-quiz-surprise><?= icon('dice') ?> Surprise me</button>
          <button class="btn btn-teal btn-lg" type="button" data-quiz-use-saved hidden>Use my saved answers</button>
        </div>
        <p class="small text-soft mt-3 mb-0" data-quiz-saved-note hidden></p>
      </section>

      <!-- Questions -->
      <form data-quiz-form hidden novalidate>
        <div class="d-flex justify-content-between small text-soft mb-1">
          <span data-quiz-counter>Question 1 of <?= count($steps) ?></span>
          <span>Click an answer to continue</span>
        </div>
        <div class="quiz-progress" aria-hidden="true"><span data-quiz-bar style="width: 0%"></span></div>

        <?php foreach ($steps as $i => [$key, $question, $help, $options, $anyLabel]): ?>
          <fieldset class="quiz-step<?= $i === 0 ? ' is-active' : '' ?>" data-step="<?= $i ?>">
            <legend class="visually-hidden"><?= e($question) ?></legend>
            <h2 class="mt-4" aria-hidden="true"><?= e($question) ?></h2>
            <p class="text-soft mb-0"><?= e($help) ?></p>
            <div class="quiz-options">
              <?php foreach ($options as $value => [$label, $desc]): ?>
                <div class="quiz-option">
                  <input type="radio" name="<?= e($key) ?>" id="q-<?= e($key . '-' . $value) ?>" value="<?= e($value) ?>">
                  <label for="q-<?= e($key . '-' . $value) ?>"><strong><?= e($label) ?></strong><?php if ($desc): ?><small><?= e($desc) ?></small><?php endif; ?></label>
                </div>
              <?php endforeach; ?>
              <div class="quiz-option quiz-option--any">
                <input type="radio" name="<?= e($key) ?>" id="q-<?= e($key) ?>-any" value="">
                <label for="q-<?= e($key) ?>-any"><strong><?= e($anyLabel) ?></strong><small>Leave this out of the score</small></label>
              </div>
            </div>
          </fieldset>
        <?php endforeach; ?>

        <div class="quiz-nav">
          <button class="btn btn-ghost" type="button" data-quiz-back>Back</button>
          <button class="btn btn-violet" type="button" data-quiz-next>Next <?= icon('arrow') ?></button>
        </div>
      </form>

      <!-- Results (filled in by recommend.js) -->
      <section data-quiz-results hidden aria-live="polite">
        <div class="section-head">
          <div>
            <span class="eyebrow">Your matches</span>
            <h2>Your top three games</h2>
            <p data-results-summary></p>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-ghost" type="button" data-quiz-restart>Retake quiz</button>
            <?php if ($user): ?>
              <button class="btn btn-amber" type="button" data-quiz-save>Save these preferences</button>
            <?php else: ?>
              <button class="btn btn-amber" type="button" data-quiz-login>Log in to save preferences</button>
            <?php endif; ?>
          </div>
        </div>
        <div data-results-list></div>

        <details class="panel mt-4" data-more-results>
          <summary class="fw-semibold">See the next seven matches</summary>
          <ol class="mt-3 mb-0" start="4" data-more-list></ol>
        </details>

        <section class="panel mt-4" aria-labelledby="how-title">
          <h3 class="h5" id="how-title">How the match score works</h3>
          <div class="row g-4">
            <div class="col-md-6">
              <p class="small text-soft">Each answer is compared with the game's stored attributes and earns part of its
                weight (for example, a game with exploration 8/10 earns 80% of the mood points for “an adventure”).
                Questions you answered with “no preference” are left out, and the total is scaled back to 100.</p>
              <p class="small text-soft mb-0">The percentage is a <strong>compatibility estimate</strong> based on our
                editorial Game DNA. It is not a prediction of how much you will enjoy a game.</p>
            </div>
            <div class="col-md-6">
              <table class="weights-table">
                <tr><td>Preferred genre</td><td>20</td></tr>
                <tr><td>Current mood</td><td>15</td></tr>
                <tr><td>Solo / co-op / multiplayer</td><td>15</td></tr>
                <tr><td>Difficulty</td><td>15</td></tr>
                <tr><td>Story / combat / exploration focus</td><td>15</td></tr>
                <tr><td>Available time</td><td>10</td></tr>
                <tr><td>Platform</td><td>10</td></tr>
                <tr><td><strong>Total</strong></td><td>100</td></tr>
              </table>
            </div>
          </div>
        </section>
      </section>
    </div>
  </div>
</main>
<script type="application/json" id="quiz-data"><?= json_for_script($pageData) ?></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
