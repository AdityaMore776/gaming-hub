<?php
require __DIR__ . '/../includes/init.php';
require APP_ROOT . '/includes/admin.php';
$admin = require_admin();

// ------------------------------------------------------------ moderation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $reviewId = int_param($_POST, 'review_id');
    $action = choice($_POST['action'] ?? '', ['hide', 'unhide', 'delete', 'dismiss']);
    $back = safe_return_path($_POST['return_to'] ?? null, 'admin/reviews.php');
    $review = $reviewId ? db_one('SELECT r.id, u.username FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.id = ?', [$reviewId]) : null;

    if ($review === null || $action === '') {
        flash('danger', 'That review no longer exists.');
        redirect($back);
    }
    $closeReports = fn(string $status) => db_query(
        "UPDATE review_reports SET status = ?, resolved_at = NOW() WHERE review_id = ? AND status = 'open'", [$status, $reviewId]);

    switch ($action) {
        case 'hide':
            db_query('UPDATE reviews SET is_hidden = 1 WHERE id = ?', [$reviewId]);
            $closeReports('resolved');
            flash('success', 'Review by ' . $review['username'] . ' is now hidden and no longer counts towards ratings.');
            break;
        case 'unhide':
            db_query('UPDATE reviews SET is_hidden = 0 WHERE id = ?', [$reviewId]);
            flash('success', 'Review by ' . $review['username'] . ' is visible again.');
            break;
        case 'delete':
            db_query('DELETE FROM reviews WHERE id = ?', [$reviewId]); // votes and reports cascade
            flash('success', 'Review by ' . $review['username'] . ' was deleted.');
            break;
        case 'dismiss':
            $closeReports('dismissed');
            flash('info', 'Reports dismissed. The review stays visible.');
            break;
    }
    redirect($back);
}

// ------------------------------------------------------------ lists
$tab = choice($_GET['tab'] ?? '', ['reported', 'hidden', 'all'], 'reported');
$userFilter = int_param($_GET, 'user');

$select = "SELECT r.id, r.rating, r.body, r.created_at, r.is_hidden, u.username, g.title, g.id AS game_id,
                  (SELECT COUNT(*) FROM review_reports rp WHERE rp.review_id = r.id AND rp.status = 'open') AS open_reports,
                  (SELECT GROUP_CONCAT(DISTINCT rp.reason ORDER BY rp.reason SEPARATOR ', ') FROM review_reports rp
                    WHERE rp.review_id = r.id AND rp.status = 'open') AS reasons,
                  (SELECT GROUP_CONCAT(rp.details SEPARATOR ' | ') FROM review_reports rp
                    WHERE rp.review_id = r.id AND rp.status = 'open' AND rp.details IS NOT NULL) AS details
             FROM reviews r JOIN users u ON u.id = r.user_id JOIN games g ON g.id = r.game_id";
$params = [];
$where = match ($tab) {
    'reported' => "EXISTS (SELECT 1 FROM review_reports rp WHERE rp.review_id = r.id AND rp.status = 'open')",
    'hidden'   => 'r.is_hidden = 1',
    default    => '1 = 1',
};
if ($userFilter) {
    $where .= ' AND r.user_id = ?';
    $params[] = $userFilter;
}
$order = $tab === 'reported' ? 'open_reports DESC, r.created_at DESC' : 'r.created_at DESC';
$reviews = db_all("$select WHERE $where ORDER BY $order LIMIT 200", $params);

$counts = db_one("SELECT (SELECT COUNT(DISTINCT review_id) FROM review_reports WHERE status = 'open') AS reported,
                         (SELECT COUNT(*) FROM reviews WHERE is_hidden = 1) AS hidden,
                         (SELECT COUNT(*) FROM reviews) AS total");
$reasonLabels = ['spam' => 'Spam', 'offensive' => 'Offensive', 'spoilers' => 'Spoilers', 'off_topic' => 'Off-topic', 'other' => 'Other'];

$actionButton = function (array $r, string $action, string $label, string $class, string $confirm = ''): string {
    return '<form method="post" class="d-inline"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>'
        . csrf_field()
        . '<input type="hidden" name="review_id" value="' . (int) $r['id'] . '">'
        . '<input type="hidden" name="action" value="' . e($action) . '">'
        . '<input type="hidden" name="return_to" value="' . e(current_path()) . '">'
        . '<button class="btn btn-sm ' . $class . '" type="submit">' . e($label) . '</button></form>';
};

$pageTitle = 'Moderate reviews';
require APP_ROOT . '/includes/header.php';
?>
<main>
  <header class="page-head">
    <div class="container">
      <span class="eyebrow">Admin</span>
      <h1>Reviews &amp; reports</h1>
      <p>Hidden reviews are not shown publicly and are left out of average ratings. Deleting is permanent.</p>
    </div>
  </header>
  <div class="container">
    <?= render_admin_nav('reviews') ?>
    <ul class="nav nav-pills gap-2 mb-3">
      <?php foreach (['reported' => 'Reported (' . $counts['reported'] . ')', 'hidden' => 'Hidden (' . $counts['hidden'] . ')',
                      'all' => 'All reviews (' . $counts['total'] . ')'] as $key => $label): ?>
        <li class="nav-item"><a class="tag<?= $tab === $key ? ' tag--teal' : '' ?>" href="<?= e(url('admin/reviews.php?tab=' . $key)) ?>"><?= e($label) ?></a></li>
      <?php endforeach; ?>
    </ul>
    <?php if ($userFilter): ?>
      <p class="small">Showing reviews by one user. <a href="<?= e(url('admin/reviews.php?tab=' . $tab)) ?>">Show everyone</a></p>
    <?php endif; ?>

    <?php if ($reviews): ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Review</th><th>Game</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
            <?php foreach ($reviews as $r): ?>
              <tr>
                <td style="min-width: 280px">
                  <div class="small text-soft mb-1"><?= e($r['username']) ?> · <?= (int) $r['rating'] ?>★ · <?= e(format_date($r['created_at'])) ?></div>
                  <?= nl2br(e(mb_strimwidth($r['body'], 0, 300, '…'))) ?>
                  <?php if ($r['open_reports']): ?>
                    <div class="small text-amber mt-1">
                      <?= plural((int) $r['open_reports'], 'report') ?>:
                      <?= e(implode(', ', array_map(fn($x) => $reasonLabels[$x] ?? $x, explode(', ', (string) $r['reasons'])))) ?>
                      <?php if ($r['details']): ?><br>“<?= e($r['details']) ?>”<?php endif; ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td><a href="<?= e(url('game.php?id=' . $r['game_id'])) ?>#reviews"><?= e($r['title']) ?></a></td>
                <td><?= $r['is_hidden'] ? '<span class="tag tag--amber">Hidden</span>' : '<span class="tag">Visible</span>' ?></td>
                <td class="text-end text-nowrap">
                  <?php
                  echo $r['is_hidden']
                      ? $actionButton($r, 'unhide', 'Unhide', 'btn-ghost')
                      : $actionButton($r, 'hide', 'Hide', 'btn-amber');
                  if ($r['open_reports']) {
                      echo ' ' . $actionButton($r, 'dismiss', 'Dismiss reports', 'btn-ghost');
                  }
                  echo ' ' . $actionButton($r, 'delete', 'Delete', 'btn-ghost text-danger-soft', 'Delete this review permanently?');
                  ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <span class="empty-state__icon"><?= icon('check') ?></span>
        <h2><?= $tab === 'reported' ? 'No open reports' : ($tab === 'hidden' ? 'No hidden reviews' : 'No reviews yet') ?></h2>
        <p><?= $tab === 'reported' ? 'Nothing needs moderating right now.' : 'There is nothing to show here.' ?></p>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php require APP_ROOT . '/includes/footer.php'; ?>
