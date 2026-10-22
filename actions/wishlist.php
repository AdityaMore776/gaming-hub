<?php
/*
 * Add or remove a game from the logged-in user's wishlist.
 * POST: csrf_token, game_id, action = add | remove
 * Replies with JSON for fetch() requests, otherwise redirects back.
 */
require __DIR__ . '/../includes/init.php';

require_post();
$user = require_login();

$gameId = int_param($_POST, 'game_id');
$action = choice($_POST['action'] ?? '', ['add', 'remove']);
$back = safe_return_path($_POST['return_to'] ?? null, 'wishlist.php');

$game = $gameId ? db_one('SELECT id, title FROM games WHERE id = ?', [$gameId]) : null;
if ($game === null || $action === '') {
    if (wants_json()) {
        json_response(['ok' => false, 'message' => 'That game could not be found.'], 404);
    }
    flash('danger', 'That game could not be found.');
    redirect($back);
}

if ($action === 'add') {
    // The primary key (user_id, game_id) prevents duplicates; INSERT IGNORE makes a repeat click harmless.
    db_query('INSERT IGNORE INTO wishlists (user_id, game_id) VALUES (?, ?)', [$user['id'], $game['id']]);
    $message = $game['title'] . ' was added to your wishlist.';
} else {
    db_query('DELETE FROM wishlists WHERE user_id = ? AND game_id = ?', [$user['id'], $game['id']]);
    $message = $game['title'] . ' was removed from your wishlist.';
}
$saved = (bool) db_value('SELECT 1 FROM wishlists WHERE user_id = ? AND game_id = ?', [$user['id'], $game['id']]);

if (wants_json()) {
    json_response(['ok' => true, 'saved' => $saved, 'message' => $message]);
}
flash('success', $message);
redirect($back);
