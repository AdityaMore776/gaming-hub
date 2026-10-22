<?php
/*
 * Serves generated SVG cover artwork: cover.php?id=12&g=rpg
 * Does not touch the database or the session, so it is fast and cacheable.
 */
declare(strict_types=1);

require __DIR__ . '/includes/cover_art.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$genre = (isset($_GET['g']) && is_string($_GET['g']) && preg_match('/^[a-z0-9-]{1,40}$/', $_GET['g'])) ? $_GET['g'] : 'action';

if ($id === false || $id === null) {
    http_response_code(404);
    exit;
}

$svg = cover_svg($id, $genre);
$etag = '"' . md5($svg) . '"';

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=604800');
header('ETag: ' . $etag);
header('X-Content-Type-Options: nosniff');

if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    exit;
}
echo $svg;
