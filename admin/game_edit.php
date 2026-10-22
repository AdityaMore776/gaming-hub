<?php
require __DIR__ . '/../includes/init.php';
require APP_ROOT . '/includes/admin.php';
$admin = require_admin();

$id = int_param($_GET, 'id') ?? int_param($_POST, 'id');
$existing = $id ? find_game($id) : null;
if ($id && $existing === null) {
    flash('danger', 'That game no longer exists.');
    redirect('admin/games.php');
}

$genres = all_genres();
$platforms = all_platforms();
$genreIdBySlug = array_column($genres, 'id', 'slug');
$platformIdBySlug = array_column($platforms, 'id', 'slug');
$maxYear = (int) date('Y') + 1;

// Form values: start from the existing game (edit) or sensible defaults (add).
$v = [
    'title'          => $existing['title'] ?? '',
    'developer'      => $existing['developer'] ?? '',
    'release_year'   => $existing['release_year'] ?? (int) date('Y'),
    'description'    => $existing['description'] ?? '',
    'pros'           => $existing['pros'] ?? '',
    'cons'           => $existing['cons'] ?? '',
    'primary_genre'  => $existing ? (int) $genreIdBySlug[$existing['primary_genre']] : 0,
    'genres'         => $existing ? array_map(fn($slug) => (int) $genreIdBySlug[$slug], array_keys($existing['genres'])) : [],
    'platforms'      => $existing ? array_map(fn($slug) => (int) $platformIdBySlug[$slug], array_keys($existing['platforms'])) : [],
    'single'         => $existing['has_single_player'] ?? 1,
    'coop'           => $existing['has_coop'] ?? 0,
    'multi'          => $existing['has_multiplayer'] ?? 0,
    'featured'       => $existing['is_featured'] ?? 0,
    'dna'            => $existing['dna'] ?? array_fill_keys(array_keys(DNA_TRAITS), 5),
    'session_length' => $existing['session_length'] ?? 'medium',
    'completion_hours' => $existing['completion_hours'] ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $ids = fn(string $key) => array_values(array_unique(array_map('intval', array_filter((array) ($_POST[$key] ?? []), 'is_scalar'))));

    $v['title'] = str_param($_POST, 'title', 120);
    $v['developer'] = str_param($_POST, 'developer', 120);
    $v['release_year'] = filter_var($_POST['release_year'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1970, 'max_range' => $maxYear]]);
    $v['description'] = str_param($_POST, 'description', 2000);
    $v['pros'] = str_param($_POST, 'pros', 1000);
    $v['cons'] = str_param($_POST, 'cons', 1000);
    $v['primary_genre'] = (int) ($_POST['primary_genre'] ?? 0);
    $v['genres'] = array_values(array_intersect($ids('genres'), array_map('intval', array_column($genres, 'id'))));
    $v['platforms'] = array_values(array_intersect($ids('platforms'), array_map('intval', array_column($platforms, 'id'))));
    $v['single'] = isset($_POST['single']) ? 1 : 0;
    $v['coop'] = isset($_POST['coop']) ? 1 : 0;
    $v['multi'] = isset($_POST['multi']) ? 1 : 0;
    $v['featured'] = isset($_POST['featured']) ? 1 : 0;
    foreach (array_keys(DNA_TRAITS) as $key) {
        $v['dna'][$key] = filter_var($_POST['dna'][$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10]]);
    }
    $v['session_length'] = choice($_POST['session_length'] ?? '', ['short', 'medium', 'long']);
    $hoursRaw = trim((string) ($_POST['completion_hours'] ?? ''));
    $v['completion_hours'] = $hoursRaw === '' ? null
        : filter_var($hoursRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);

    if (mb_strlen($v['title']) < 2) $errors['title'] = 'Enter a title (at least 2 characters).';
    if ($v['title'] !== '' && db_value('SELECT 1 FROM games WHERE title = ? AND id <> ?', [$v['title'], $id ?? 0])) {
        $errors['title'] = 'Another game already has this title.';
    }
    if (mb_strlen($v['developer']) < 2) $errors['developer'] = 'Enter the developer or studio.';
    if ($v['release_year'] === false || $v['release_year'] === null) $errors['release_year'] = "Enter a year between 1970 and $maxYear.";
    if (mb_strlen($v['description']) < 20) $errors['description'] = 'Write a description of at least 20 characters.';
    if (!in_array($v['primary_genre'], array_map('intval', array_column($genres, 'id')), true)) $errors['primary_genre'] = 'Choose a primary genre.';
    if (!$v['platforms']) $errors['platforms'] = 'Choose at least one platform.';
    if (!$v['single'] && !$v['coop'] && !$v['multi']) $errors['modes'] = 'Choose at least one game mode.';
    foreach ($v['dna'] as $key => $score) {
        if ($score === false || $score === null) $errors['dna'] = 'Every Game DNA score must be a whole number from 1 to 10.';
    }
    if ($v['session_length'] === '') $errors['session_length'] = 'Choose a session length.';
    if ($v['completion_hours'] === false) $errors['completion_hours'] = 'Enter hours between 1 and 1000, or leave it empty.';

    // Cover image (optional)
    $newCover = null;
    if (!$errors && ($_FILES['cover']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $newCover = save_cover_upload($_FILES['cover']);
        } catch (InvalidArgumentException $ex) {
            $errors['cover'] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $removeCover = isset($_POST['remove_cover']);
        $oldCover = $existing['cover_image'] ?? null;
        $cover = $newCover ?? ($removeCover ? null : $oldCover);
        $pros = str_replace("\r", '', $v['pros']);
        $cons = str_replace("\r", '', $v['cons']);
        $genreList = array_values(array_unique(array_merge([$v['primary_genre']], $v['genres'])));

        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($existing) {
                db_query('UPDATE games SET title = ?, slug = ?, developer = ?, release_year = ?, description = ?, pros = ?, cons = ?,
                                 cover_image = ?, has_single_player = ?, has_coop = ?, has_multiplayer = ?, is_featured = ?
                           WHERE id = ?',
                    [$v['title'], unique_slug('games', $v['title'], $id), $v['developer'], $v['release_year'], $v['description'],
                     $pros ?: null, $cons ?: null, $cover, $v['single'], $v['coop'], $v['multi'], $v['featured'], $id]);
            } else {
                db_query('INSERT INTO games (title, slug, developer, release_year, description, pros, cons, cover_image,
                                             has_single_player, has_coop, has_multiplayer, is_featured)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$v['title'], unique_slug('games', $v['title']), $v['developer'], $v['release_year'], $v['description'],
                     $pros ?: null, $cons ?: null, $cover, $v['single'], $v['coop'], $v['multi'], $v['featured']]);
                $id = (int) $pdo->lastInsertId();
            }

            db_query('REPLACE INTO game_attributes (game_id, story_score, combat_score, exploration_score, difficulty_score,
                                                    replayability_score, session_length, completion_hours)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$id, $v['dna']['story'], $v['dna']['combat'], $v['dna']['exploration'], $v['dna']['difficulty'],
                 $v['dna']['replayability'], $v['session_length'], $v['completion_hours']]);

            db_query('DELETE FROM game_genres WHERE game_id = ?', [$id]);
            foreach ($genreList as $position => $genreId) {
                db_query('INSERT INTO game_genres (game_id, genre_id, position) VALUES (?, ?, ?)', [$id, $genreId, $position]);
            }
            db_query('DELETE FROM game_platforms WHERE game_id = ?', [$id]);
            foreach ($v['platforms'] as $platformId) {
                db_query('INSERT INTO game_platforms (game_id, platform_id) VALUES (?, ?)', [$id, $platformId]);
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack();
            delete_cover_file($newCover);
            throw $ex;
        }

        if ($cover !== $oldCover) {
            delete_cover_file($oldCover);
        }
        flash('success', '“' . $v['title'] . '” was ' . ($existing ? 'updated.' : 'added.'));
        redirect('admin/games.php');
    }
    delete_cover_file($newCover ?? null);
}

$invalid = fn(string $key) => isset($errors[$key]) ? ' is-invalid' : '';
$error = fn(string $key) => isset($errors[$key]) ? '<div class="invalid-feedback d-block">' . e($errors[$key]) . '</div>' : '';

$pageTitle = $existing ? 'Edit ' . $existing['title'] : 'Add a game';
$pageScripts = ['admin.js'];
require APP_ROOT . '/includes/header.php';
?>
<main>
  <header class="page-head">
    <div class="container">
      <span class="eyebrow">Admin · Games</span>
      <h1><?= $existing ? 'Edit “' . e($existing['title']) . '”' : 'Add a game' ?></h1>
      <p>Game DNA scores are editorial estimates on a 1–10 scale. Keep them consistent with similar games.</p>
    </div>
  </header>
  <div class="container">
    <?= render_admin_nav('games') ?>
    <?php if ($errors): ?>
      <div class="alert alert-danger" role="alert">Please fix the highlighted fields (<?= count($errors) ?>).</div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <?php if ($existing): ?><input type="hidden" name="id" value="<?= $existing['id'] ?>"><?php endif; ?>
      <input type="hidden" name="MAX_FILE_SIZE" value="<?= COVER_MAX_BYTES ?>">

      <div class="row g-4">
        <div class="col-lg-8">
          <section class="panel mb-4">
            <h2 class="h5">Basic information</h2>
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label" for="title">Title</label>
                <input class="form-control<?= $invalid('title') ?>" id="title" name="title" value="<?= e($v['title']) ?>" maxlength="120" required>
                <?= $error('title') ?>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="release_year">Release year</label>
                <input class="form-control<?= $invalid('release_year') ?>" type="number" id="release_year" name="release_year"
                       value="<?= e((string) $v['release_year']) ?>" min="1970" max="<?= $maxYear ?>" required>
                <?= $error('release_year') ?>
              </div>
              <div class="col-12">
                <label class="form-label" for="developer">Developer / studio</label>
                <input class="form-control<?= $invalid('developer') ?>" id="developer" name="developer" value="<?= e($v['developer']) ?>" maxlength="120" required>
                <?= $error('developer') ?>
              </div>
              <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control<?= $invalid('description') ?>" id="description" name="description" rows="4" maxlength="2000" required><?= e($v['description']) ?></textarea>
                <?= $error('description') ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="pros">Pros <span class="text-soft fw-normal">(one per line)</span></label>
                <textarea class="form-control" id="pros" name="pros" rows="3" maxlength="1000"><?= e($v['pros']) ?></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="cons">Cons <span class="text-soft fw-normal">(one per line)</span></label>
                <textarea class="form-control" id="cons" name="cons" rows="3" maxlength="1000"><?= e($v['cons']) ?></textarea>
              </div>
            </div>
          </section>

          <section class="panel mb-4">
            <h2 class="h5">Genres, platforms &amp; modes</h2>
            <div class="mb-3" style="max-width: 320px">
              <label class="form-label" for="primary_genre">Primary genre</label>
              <select class="form-select<?= $invalid('primary_genre') ?>" id="primary_genre" name="primary_genre" required>
                <option value="">Choose…</option>
                <?php foreach ($genres as $g): ?>
                  <option value="<?= (int) $g['id'] ?>" <?= (int) $g['id'] === $v['primary_genre'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Shown first, and decides the style of the generated cover art.</div>
              <?= $error('primary_genre') ?>
            </div>
            <fieldset class="mb-3">
              <legend class="form-label">Other genres</legend>
              <div class="chip-options">
                <?php foreach ($genres as $g): ?>
                  <label class="chip-option"><input type="checkbox" name="genres[]" value="<?= (int) $g['id'] ?>"
                    <?= in_array((int) $g['id'], $v['genres'], true) ? 'checked' : '' ?>><span><?= e($g['name']) ?></span></label>
                <?php endforeach; ?>
              </div>
            </fieldset>
            <fieldset class="mb-3">
              <legend class="form-label">Platforms</legend>
              <div class="chip-options">
                <?php foreach ($platforms as $p): ?>
                  <label class="chip-option"><input type="checkbox" name="platforms[]" value="<?= (int) $p['id'] ?>"
                    <?= in_array((int) $p['id'], $v['platforms'], true) ? 'checked' : '' ?>><span><?= e($p['name']) ?></span></label>
                <?php endforeach; ?>
              </div>
              <?= $error('platforms') ?>
            </fieldset>
            <fieldset>
              <legend class="form-label">Game modes</legend>
              <div class="chip-options">
                <label class="chip-option"><input type="checkbox" name="single" <?= $v['single'] ? 'checked' : '' ?>><span>Single-player</span></label>
                <label class="chip-option"><input type="checkbox" name="coop" <?= $v['coop'] ? 'checked' : '' ?>><span>Co-op</span></label>
                <label class="chip-option"><input type="checkbox" name="multi" <?= $v['multi'] ? 'checked' : '' ?>><span>Competitive / online multiplayer</span></label>
              </div>
              <?= $error('modes') ?>
            </fieldset>
          </section>

          <section class="panel mb-4">
            <h2 class="h5">Game DNA</h2>
            <p class="small text-soft">Drag each slider. 1 = very little, 10 = a defining part of the game. For difficulty, 10 = extremely demanding.</p>
            <div class="d-grid gap-3">
              <?php foreach (DNA_TRAITS as $key => $label): ?>
                <div class="dna-input dna--<?= $key ?>">
                  <label class="form-label mb-0" for="dna-<?= $key ?>"><?= e($label) ?></label>
                  <input class="form-range" type="range" id="dna-<?= $key ?>" name="dna[<?= $key ?>]" min="1" max="10" step="1"
                         value="<?= (int) ($v['dna'][$key] ?: 5) ?>" data-output="out-<?= $key ?>">
                  <output id="out-<?= $key ?>" for="dna-<?= $key ?>"><?= (int) ($v['dna'][$key] ?: 5) ?></output>
                </div>
              <?php endforeach; ?>
            </div>
            <?= $error('dna') ?>
            <div class="row g-3 mt-2">
              <div class="col-md-6">
                <label class="form-label" for="session_length">Typical session length</label>
                <select class="form-select<?= $invalid('session_length') ?>" id="session_length" name="session_length">
                  <?php foreach (['short', 'medium', 'long'] as $s): ?>
                    <option value="<?= $s ?>" <?= $v['session_length'] === $s ? 'selected' : '' ?>><?= e(session_label($s, true)) ?></option>
                  <?php endforeach; ?>
                </select>
                <?= $error('session_length') ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="completion_hours">Hours to finish <span class="text-soft fw-normal">(empty = open-ended)</span></label>
                <input class="form-control<?= $invalid('completion_hours') ?>" type="number" id="completion_hours" name="completion_hours"
                       value="<?= e((string) ($v['completion_hours'] ?? '')) ?>" min="1" max="1000">
                <?= $error('completion_hours') ?>
              </div>
            </div>
          </section>
        </div>

        <div class="col-lg-4">
          <section class="panel mb-4">
            <h2 class="h5">Cover image</h2>
            <?php if ($existing): ?>
              <div class="mb-3" style="max-width: 180px"><?= render_cover($existing) ?></div>
            <?php endif; ?>
            <label class="form-label" for="cover">Upload a new image</label>
            <input class="form-control<?= $invalid('cover') ?>" type="file" id="cover" name="cover" accept="image/jpeg,image/png,image/webp">
            <div class="form-text">JPG, PNG or WebP, up to 2 MB, at least 150 × 150 px. A 3:4 portrait works best.
              Only upload images you have the right to use. Without an image, cover art is generated automatically.</div>
            <?= $error('cover') ?>
            <?php if (!empty($existing['cover_image'])): ?>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="remove_cover" name="remove_cover">
                <label class="form-check-label" for="remove_cover">Remove uploaded image (use generated art)</label>
              </div>
            <?php endif; ?>
          </section>

          <section class="panel mb-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="featured" name="featured" <?= $v['featured'] ? 'checked' : '' ?>>
              <label class="form-check-label" for="featured">Feature on the homepage</label>
            </div>
          </section>

          <div class="d-grid gap-2">
            <button class="btn btn-amber btn-lg" type="submit"><?= $existing ? 'Save changes' : 'Add game' ?></button>
            <a class="btn btn-ghost" href="<?= e(url('admin/games.php')) ?>">Cancel</a>
          </div>
        </div>
      </div>
    </form>
  </div>
</main>
<?php require APP_ROOT . '/includes/footer.php'; ?>
