<?php
/*
 * Save or clear the logged-in user's quiz answers ("What Should I Play?").
 * POST action = save | clear, plus genre, mood, play_style, difficulty, session, focus, platform.
 * An empty value means "no preference" and is stored as NULL.
 */
require __DIR__ . '/../includes/init.php';

require_post();
$user = require_login();
$back = safe_return_path($_POST['return_to'] ?? null, 'profile.php');

$finish = function (bool $ok, string $message) use ($back): never {
    if (wants_json()) {
        json_response(['ok' => $ok, 'message' => $message], $ok ? 200 : 422);
    }
    flash($ok ? 'success' : 'danger', $message);
    redirect($back);
};

if (($_POST['action'] ?? '') === 'clear') {
    db_query('DELETE FROM user_preferences WHERE user_id = ?', [$user['id']]);
    $finish(true, 'Your saved quiz answers were cleared.');
}

$nullable = fn(string $value) => $value === '' ? null : $value;
$values = [
    'mood'       => $nullable(choice($_POST['mood'] ?? '', ['adventure', 'story', 'challenge', 'relax', 'social', 'quick'])),
    'play_style' => $nullable(choice($_POST['play_style'] ?? '', ['solo', 'coop', 'multiplayer'])),
    'difficulty' => $nullable(choice($_POST['difficulty'] ?? '', ['relaxed', 'balanced', 'challenging'])),
    'session'    => $nullable(choice($_POST['session'] ?? '', ['short', 'medium', 'long'])),
    'focus'      => $nullable(choice($_POST['focus'] ?? '', ['story', 'combat', 'exploration'])),
];

// Genre and platform arrive as slugs; look up their ids (unknown slugs become NULL).
$genreId = null;
if (is_string($_POST['genre'] ?? null) && $_POST['genre'] !== '') {
    $genreId = db_value('SELECT id FROM genres WHERE slug = ?', [$_POST['genre']]);
}
$platformId = null;
if (is_string($_POST['platform'] ?? null) && $_POST['platform'] !== '') {
    $platformId = db_value('SELECT id FROM platforms WHERE slug = ?', [$_POST['platform']]);
}

db_query('INSERT INTO user_preferences (user_id, genre_id, platform_id, mood, play_style, difficulty, session, focus)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)
          ON DUPLICATE KEY UPDATE genre_id = VALUES(genre_id), platform_id = VALUES(platform_id), mood = VALUES(mood),
              play_style = VALUES(play_style), difficulty = VALUES(difficulty), session = VALUES(session), focus = VALUES(focus)',
    [$user['id'], $genreId, $platformId, $values['mood'], $values['play_style'], $values['difficulty'], $values['session'], $values['focus']]);

$finish(true, 'Your preferences were saved. Game pages will now explain matches using them.');
