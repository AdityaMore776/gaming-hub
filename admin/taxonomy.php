<?php
require __DIR__ . '/../includes/init.php';
require APP_ROOT . '/includes/admin.php';
$admin = require_admin();

// genres and platforms share the same structure, so one page manages both.
$tables = ['genre' => 'genres', 'platform' => 'platforms'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $type = choice($_POST['type'] ?? '', array_keys($tables));
    $action = choice($_POST['action'] ?? '', ['add', 'rename', 'delete']);
    $name = str_param($_POST, 'name', 40);
    $id = int_param($_POST, 'id');

    if ($type === '' || $action === '') {
        flash('danger', 'Unknown request.');
        redirect('admin/taxonomy.php');
    }
    $table = $tables[$type];
    $validName = (bool) preg_match("/^[\\p{L}0-9][\\p{L}0-9 &'+.\\-]{1,39}$/u", $name);

    if (in_array($action, ['add', 'rename'], true) && !$validName) {
        flash('danger', 'Names must be 2–40 characters: letters, numbers, spaces and & \' + . -');
    } elseif ($action === 'add') {
        if (db_value("SELECT 1 FROM $table WHERE name = ?", [$name])) {
            flash('warning', "“{$name}” already exists.");
        } else {
            db_query("INSERT INTO $table (name, slug) VALUES (?, ?)", [$name, unique_slug($table, $name)]);
            flash('success', ucfirst($type) . " “{$name}” was added.");
        }
    } elseif ($action === 'rename' && $id) {
        if (db_value("SELECT 1 FROM $table WHERE name = ? AND id <> ?", [$name, $id])) {
            flash('warning', "Another {$type} is already called “{$name}”.");
        } else {
            // The slug stays the same so existing links and saved preferences keep working.
            db_query("UPDATE $table SET name = ? WHERE id = ?", [$name, $id]);
            flash('success', 'Renamed to “' . $name . '”.');
        }
    } elseif ($action === 'delete' && $id) {
        // Links to games are removed by ON DELETE CASCADE; saved preferences become "no preference".
        db_query("DELETE FROM $table WHERE id = ?", [$id]);
        flash('success', ucfirst($type) . ' deleted.');
    }
    redirect('admin/taxonomy.php#' . $type . 's');
}

$lists = [
    'genre' => db_all('SELECT ge.id, ge.name, ge.slug, COUNT(gg.game_id) AS games
                         FROM genres ge LEFT JOIN game_genres gg ON gg.genre_id = ge.id GROUP BY ge.id ORDER BY ge.name'),
    'platform' => db_all('SELECT p.id, p.name, p.slug, COUNT(gp.game_id) AS games
                            FROM platforms p LEFT JOIN game_platforms gp ON gp.platform_id = p.id GROUP BY p.id ORDER BY p.id'),
];

$pageTitle = 'Genres & platforms';
require APP_ROOT . '/includes/header.php';
?>
<main>
  <header class="page-head">
    <div class="container">
      <span class="eyebrow">Admin</span>
      <h1>Genres &amp; platforms</h1>
      <p>These lists power the explorer filters, the quiz and the game form.</p>
    </div>
  </header>
  <div class="container">
    <?= render_admin_nav('taxonomy') ?>
    <div class="row g-4">
      <?php foreach ($lists as $type => $rows): ?>
        <div class="col-lg-6">
          <section class="panel" id="<?= $type ?>s">
            <h2 class="h5"><?= $type === 'genre' ? 'Genres' : 'Platforms' ?></h2>
            <form class="d-flex gap-2 mb-3" method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="type" value="<?= $type ?>">
              <input type="hidden" name="action" value="add">
              <label class="visually-hidden" for="add-<?= $type ?>">New <?= $type ?> name</label>
              <input class="form-control" id="add-<?= $type ?>" name="name" maxlength="40" placeholder="New <?= $type ?> name" required>
              <button class="btn btn-violet" type="submit"><?= icon('plus') ?> Add</button>
            </form>
            <div class="table-wrap">
              <table class="table">
                <thead><tr><th>Name</th><th>Games</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($rows as $row): ?>
                    <tr>
                      <td>
                        <form class="d-flex gap-1" method="post">
                          <?= csrf_field() ?>
                          <input type="hidden" name="type" value="<?= $type ?>">
                          <input type="hidden" name="action" value="rename">
                          <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                          <label class="visually-hidden" for="name-<?= $type . $row['id'] ?>">Name</label>
                          <input class="form-control form-control-sm" id="name-<?= $type . $row['id'] ?>" name="name" value="<?= e($row['name']) ?>" maxlength="40" required>
                          <button class="btn btn-sm btn-ghost" type="submit">Rename</button>
                        </form>
                      </td>
                      <td><a href="<?= e(url('explore.php?' . $type . '=' . $row['slug'])) ?>"><?= (int) $row['games'] ?></a></td>
                      <td class="text-end">
                        <form method="post" data-confirm="Delete “<?= e($row['name']) ?>”? It will be removed from <?= (int) $row['games'] ?> game(s).">
                          <?= csrf_field() ?>
                          <input type="hidden" name="type" value="<?= $type ?>">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                          <button class="btn btn-sm btn-ghost text-danger-soft" type="submit"><?= icon('trash') ?><span class="visually-hidden">Delete <?= e($row['name']) ?></span></button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>
<?php require APP_ROOT . '/includes/footer.php'; ?>
