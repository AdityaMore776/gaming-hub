<?php
/*
 * Shared helper functions: escaping, URLs, flash messages, game queries
 * and small HTML components (cards, covers, stars, Game DNA).
 */

// ===================================================================
//  Output, URLs and requests
// ===================================================================

/** Escape text for HTML output. Use for EVERY value that came from users or the database. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Web path of the project root, e.g. "" or "/gaming-hub". */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $configured = config('base_url');
        if (is_string($configured)) {
            $base = rtrim($configured, '/');
        } else {
            $root = str_replace('\\', '/', (string) realpath(APP_ROOT));
            $docRoot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
            $base = ($docRoot !== '' && stripos($root, $docRoot) === 0) ? substr($root, strlen($docRoot)) : '';
            $base = rtrim($base, '/');
        }
    }
    return $base;
}

/** Build a URL relative to the project root: url('game.php?id=3'). */
function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

/** URL for a CSS/JS file with a cache-busting version number. */
function asset(string $path): string
{
    $file = APP_ROOT . '/assets/' . $path;
    $v = is_file($file) ? filemtime($file) : 0;
    return url('assets/' . $path) . '?v=' . $v;
}

function redirect(string $path): never
{
    $target = preg_match('#^(https?:)?//#', $path) ? url('index.php') : $path;
    if (!str_starts_with($target, '/')) {
        $target = url($target);
    }
    header('Location: ' . $target);
    exit;
}

/** Only allow redirects to paths on this site (prevents open redirects). */
function safe_return_path(?string $path, string $fallback): string
{
    if (is_string($path) && $path !== '' && str_starts_with($path, '/') && !str_starts_with($path, '//')
        && !str_contains($path, '\\') && !preg_match('/[\r\n]/', $path)) {
        return $path;
    }
    return url($fallback);
}

/** The current URL path + query, used as a "come back here" target. */
function current_path(): string
{
    return $_SERVER['REQUEST_URI'] ?? url('index.php');
}

function wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Read a positive integer from an array such as $_GET, or null. */
function int_param(array $src, string $key): ?int
{
    $value = filter_var($src[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $value === false ? null : $value;
}

function str_param(array $src, string $key, int $maxLength = 200): string
{
    $value = $src[$key] ?? '';
    return is_string($value) ? mb_substr(trim($value), 0, $maxLength) : '';
}

/** Pick $value if it is one of $allowed, otherwise $default. */
function choice(mixed $value, array $allowed, string $default = ''): string
{
    return (is_string($value) && in_array($value, $allowed, true)) ? $value : $default;
}

function format_date(?string $datetime): string
{
    return $datetime ? date('j M Y', strtotime($datetime)) : '';
}

function plural(int $n, string $word, ?string $pluralWord = null): string
{
    return $n . ' ' . ($n === 1 ? $word : ($pluralWord ?? $word . 's'));
}

// ===================================================================
//  Labels for Game DNA values
// ===================================================================

const DNA_TRAITS = [
    'story'         => 'Story',
    'combat'        => 'Combat',
    'exploration'   => 'Exploration',
    'difficulty'    => 'Difficulty',
    'replayability' => 'Replayability',
];

function difficulty_label(int $score): string
{
    return match (true) {
        $score <= 3 => 'Relaxed',
        $score <= 6 => 'Moderate',
        $score <= 8 => 'Hard',
        default     => 'Brutal',
    };
}

function session_label(string $session, bool $long = false): string
{
    $labels = [
        'short'  => ['Short', 'Short sessions (under 1 hour)'],
        'medium' => ['Medium', 'Medium sessions (1–2 hours)'],
        'long'   => ['Long', 'Long sessions (2+ hours)'],
    ];
    return $labels[$session][$long ? 1 : 0] ?? $session;
}

function completion_label(?int $hours): string
{
    return $hours ? '≈ ' . $hours . ' hours' : 'Open-ended';
}

function modes_list(array $g): array
{
    $modes = [];
    if ($g['has_single_player']) $modes[] = 'Single-player';
    if ($g['has_coop'])          $modes[] = 'Co-op';
    if ($g['has_multiplayer'])   $modes[] = 'Multiplayer';
    return $modes;
}

function platform_short(string $slug, string $name): string
{
    return ['playstation' => 'PS', 'switch' => 'Switch'][$slug] ?? $name;
}

// ===================================================================
//  Game queries
// ===================================================================

/** SELECT used everywhere a full game row is needed (game + DNA + rating + genres + platforms). */
function game_select_sql(): string
{
    return "SELECT g.id, g.title, g.slug, g.developer, g.release_year, g.description, g.pros, g.cons,
               g.cover_image, g.has_single_player, g.has_coop, g.has_multiplayer, g.is_featured,
               a.story_score, a.combat_score, a.exploration_score, a.difficulty_score,
               a.replayability_score, a.session_length, a.completion_hours,
               COALESCE(r.avg_rating, 0) AS avg_rating, COALESCE(r.review_count, 0) AS review_count,
               (SELECT GROUP_CONCAT(ge.name ORDER BY gg.position, ge.name SEPARATOR '|')
                  FROM game_genres gg JOIN genres ge ON ge.id = gg.genre_id WHERE gg.game_id = g.id) AS genre_names,
               (SELECT GROUP_CONCAT(ge.slug ORDER BY gg.position, ge.name SEPARATOR '|')
                  FROM game_genres gg JOIN genres ge ON ge.id = gg.genre_id WHERE gg.game_id = g.id) AS genre_slugs,
               (SELECT GROUP_CONCAT(p.name ORDER BY p.id SEPARATOR '|')
                  FROM game_platforms gp JOIN platforms p ON p.id = gp.platform_id WHERE gp.game_id = g.id) AS platform_names,
               (SELECT GROUP_CONCAT(p.slug ORDER BY p.id SEPARATOR '|')
                  FROM game_platforms gp JOIN platforms p ON p.id = gp.platform_id WHERE gp.game_id = g.id) AS platform_slugs
            FROM games g
            JOIN game_attributes a ON a.game_id = g.id
            LEFT JOIN v_game_ratings r ON r.game_id = g.id";
}

/** Turn a raw row into a friendlier array (ints, lists, DNA). */
function hydrate_game(array $row): array
{
    $split = fn(?string $s) => ($s === null || $s === '') ? [] : explode('|', $s);
    $g = $row;
    foreach (['id', 'release_year', 'has_single_player', 'has_coop', 'has_multiplayer', 'is_featured',
              'story_score', 'combat_score', 'exploration_score', 'difficulty_score', 'replayability_score',
              'review_count'] as $key) {
        $g[$key] = (int) $row[$key];
    }
    $g['completion_hours'] = $row['completion_hours'] === null ? null : (int) $row['completion_hours'];
    $g['avg_rating'] = round((float) $row['avg_rating'], 1);
    $g['genres'] = array_combine($split($row['genre_slugs']), $split($row['genre_names'])) ?: [];
    $g['platforms'] = array_combine($split($row['platform_slugs']), $split($row['platform_names'])) ?: [];
    $g['primary_genre'] = array_key_first($g['genres']) ?? 'action';
    $g['dna'] = [
        'story'         => $g['story_score'],
        'combat'        => $g['combat_score'],
        'exploration'   => $g['exploration_score'],
        'difficulty'    => $g['difficulty_score'],
        'replayability' => $g['replayability_score'],
    ];
    $g['pros_list'] = array_values(array_filter(array_map('trim', explode("\n", (string) $row['pros']))));
    $g['cons_list'] = array_values(array_filter(array_map('trim', explode("\n", (string) $row['cons']))));
    return $g;
}

function find_game(int $id): ?array
{
    $row = db_one(game_select_sql() . ' WHERE g.id = ?', [$id]);
    return $row ? hydrate_game($row) : null;
}

/** Fetch several games by id, keeping the order of $ids. */
function find_games(array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
    if (!$ids) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $rows = db_all(game_select_sql() . " WHERE g.id IN ($placeholders)", $ids);
    $byId = [];
    foreach ($rows as $row) {
        $byId[(int) $row['id']] = hydrate_game($row);
    }
    return array_values(array_filter(array_map(fn($id) => $byId[$id] ?? null, $ids)));
}

function all_genres(): array
{
    static $genres = null;
    return $genres ??= db_all('SELECT id, name, slug FROM genres ORDER BY name');
}

function all_platforms(): array
{
    static $platforms = null;
    return $platforms ??= db_all('SELECT id, name, slug FROM platforms ORDER BY id');
}

/** Game IDs on the current user's wishlist (empty for guests). */
function wishlist_ids(): array
{
    static $ids = null;
    if ($ids === null) {
        $ids = [];
        if ($user = current_user()) {
            $ids = array_map('intval', db_query('SELECT game_id FROM wishlists WHERE user_id = ?', [$user['id']])
                ->fetchAll(PDO::FETCH_COLUMN));
        }
    }
    return $ids;
}

/** Saved quiz answers for a user, as slugs (same shape the JavaScript uses). */
function user_quiz_prefs(int $userId): ?array
{
    $row = db_one('SELECT up.mood, up.play_style, up.difficulty, up.session, up.focus,
                          ge.slug AS genre, p.slug AS platform, up.updated_at
                     FROM user_preferences up
                     LEFT JOIN genres ge ON ge.id = up.genre_id
                     LEFT JOIN platforms p ON p.id = up.platform_id
                    WHERE up.user_id = ?', [$userId]);
    return $row ?: null;
}

/** The compact game data the JavaScript scoring code needs. */
function game_for_js(array $g): array
{
    return [
        'id'        => $g['id'],
        'title'     => $g['title'],
        'year'      => $g['release_year'],
        'url'       => url('game.php?id=' . $g['id']),
        'cover'     => cover_url($g),
        'customCover' => $g['cover_image'] !== null,
        'genres'    => $g['genres'],
        'platforms' => $g['platforms'],
        'single'    => (bool) $g['has_single_player'],
        'coop'      => (bool) $g['has_coop'],
        'multi'     => (bool) $g['has_multiplayer'],
        'dna'       => $g['dna'],
        'session'   => $g['session_length'],
        'hours'     => $g['completion_hours'],
        'rating'    => $g['avg_rating'],
        'reviews'   => $g['review_count'],
    ];
}

function json_for_script(mixed $data): string
{
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
}

// ===================================================================
//  Explorer filters
// ===================================================================

const EXPLORE_SORTS = [
    'rating' => 'Highest rated',
    'title'  => 'Title (A–Z)',
    'newest' => 'Newest first',
    'oldest' => 'Oldest first',
];

/** Read and validate explorer filters from the query string. */
function explore_filters(array $src): array
{
    $slug = fn($v) => (is_string($v) && preg_match('/^[a-z0-9-]{1,40}$/', $v)) ? $v : '';
    return [
        'q'          => str_param($src, 'q', 80),
        'genre'      => $slug($src['genre'] ?? ''),
        'platform'   => $slug($src['platform'] ?? ''),
        'mode'       => choice($src['mode'] ?? '', ['single', 'coop', 'multi', 'together']),
        'difficulty' => choice($src['difficulty'] ?? '', ['easy', 'moderate', 'hard']),
        'focus'      => choice($src['focus'] ?? '', ['story', 'combat', 'exploration']),
        'session'    => choice($src['session'] ?? '', ['short', 'medium', 'long']),
        'min_rating' => choice($src['min_rating'] ?? '', ['3', '3.5', '4', '4.5']),
        'sort'       => choice($src['sort'] ?? '', array_keys(EXPLORE_SORTS), 'rating'),
        'page'       => int_param($src, 'page') ?? 1,
    ];
}

/** Build the WHERE clause + parameters for explorer filters. */
function explore_where(array $f): array
{
    $where = [];
    $params = [];

    if ($f['q'] !== '') {
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';
        $where[] = '(g.title LIKE ? OR g.developer LIKE ?)';
        array_push($params, $like, $like);
    }
    if ($f['genre'] !== '') {
        $where[] = 'EXISTS (SELECT 1 FROM game_genres gg JOIN genres ge ON ge.id = gg.genre_id
                            WHERE gg.game_id = g.id AND ge.slug = ?)';
        $params[] = $f['genre'];
    }
    if ($f['platform'] !== '') {
        $where[] = 'EXISTS (SELECT 1 FROM game_platforms gp JOIN platforms p ON p.id = gp.platform_id
                            WHERE gp.game_id = g.id AND p.slug = ?)';
        $params[] = $f['platform'];
    }
    $where[] = match ($f['mode']) {
        'single'   => 'g.has_single_player = 1',
        'coop'     => 'g.has_coop = 1',
        'multi'    => 'g.has_multiplayer = 1',
        'together' => '(g.has_coop = 1 OR g.has_multiplayer = 1)',
        default    => '1 = 1',
    };
    $where[] = match ($f['difficulty']) {
        'easy'     => 'a.difficulty_score <= 3',
        'moderate' => 'a.difficulty_score BETWEEN 4 AND 6',
        'hard'     => 'a.difficulty_score >= 7',
        default    => '1 = 1',
    };
    $where[] = match ($f['focus']) {
        'story'       => 'a.story_score >= 8',
        'combat'      => 'a.combat_score >= 8',
        'exploration' => 'a.exploration_score >= 8',
        default       => '1 = 1',
    };
    if ($f['session'] !== '') {
        $where[] = 'a.session_length = ?';
        $params[] = $f['session'];
    }
    if ($f['min_rating'] !== '') {
        $where[] = 'COALESCE(r.avg_rating, 0) >= ?';
        $params[] = (float) $f['min_rating'];
    }
    return [implode(' AND ', $where), $params];
}

function explore_order(string $sort): string
{
    return match ($sort) {
        'title'  => 'g.title ASC',
        'newest' => 'g.release_year DESC, g.title ASC',
        'oldest' => 'g.release_year ASC, g.title ASC',
        default  => 'avg_rating DESC, review_count DESC, g.title ASC',
    };
}

/** Human-readable descriptions of active filters, used for removable chips. */
function explore_active_filters(array $f): array
{
    $genreNames = array_column(all_genres(), 'name', 'slug');
    $platformNames = array_column(all_platforms(), 'name', 'slug');
    $labels = [
        'q'          => $f['q'] !== '' ? '“' . $f['q'] . '”' : null,
        'genre'      => $genreNames[$f['genre']] ?? null,
        'platform'   => $platformNames[$f['platform']] ?? null,
        'mode'       => ['single' => 'Single-player', 'coop' => 'Co-op', 'multi' => 'Multiplayer',
                         'together' => 'Play with friends'][$f['mode']] ?? null,
        'difficulty' => ['easy' => 'Relaxed difficulty', 'moderate' => 'Moderate difficulty',
                         'hard' => 'Hard difficulty'][$f['difficulty']] ?? null,
        'focus'      => ['story' => 'Story-focused', 'combat' => 'Combat-heavy',
                         'exploration' => 'Exploration-heavy'][$f['focus']] ?? null,
        'session'    => $f['session'] !== '' ? session_label($f['session']) . ' sessions' : null,
        'min_rating' => $f['min_rating'] !== '' ? $f['min_rating'] . '★ and up' : null,
    ];
    return array_filter($labels);
}

/** Query string for the explorer with one filter changed (null removes it). */
function explore_query(array $f, array $changes = []): string
{
    $params = array_merge($f, $changes);
    if (($params['sort'] ?? '') === 'rating') unset($params['sort']);
    if (($params['page'] ?? 1) == 1) unset($params['page']);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return $params ? '?' . http_build_query($params) : '';
}

// ===================================================================
//  Reviews
// ===================================================================

const REVIEW_SORTS = [
    'newest'  => 'Newest',
    'highest' => 'Highest rated',
    'helpful' => 'Most helpful',
];

/**
 * Visible reviews with author, helpful count and the viewer's vote/report state.
 * $where is extra SQL (using ? placeholders) appended with AND.
 */
function fetch_reviews(string $where, array $params, string $sort, int $limit, int $offset = 0): array
{
    $uid = current_user()['id'] ?? 0;
    $order = match ($sort) {
        'highest' => 'r.rating DESC, r.created_at DESC',
        'helpful' => 'helpful_count DESC, r.created_at DESC',
        default   => 'r.created_at DESC',
    };
    $sql = "SELECT r.id, r.game_id, r.user_id, r.rating, r.body, r.created_at, r.updated_at,
                   u.username, g.title AS game_title,
                   (SELECT COUNT(*) FROM review_votes v WHERE v.review_id = r.id) AS helpful_count,
                   EXISTS (SELECT 1 FROM review_votes v WHERE v.review_id = r.id AND v.user_id = ?) AS voted,
                   EXISTS (SELECT 1 FROM review_reports rp WHERE rp.review_id = r.id AND rp.reporter_id = ?) AS reported
              FROM reviews r
              JOIN users u ON u.id = r.user_id
              JOIN games g ON g.id = r.game_id
             WHERE r.is_hidden = 0" . ($where !== '' ? " AND $where" : '') . "
             ORDER BY $order
             LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset;
    return db_all($sql, array_merge([$uid, $uid], $params));
}

// ===================================================================
//  HTML components
// ===================================================================

/** Small inline SVG icons (no icon font or external library needed). */
function icon(string $name, string $class = ''): string
{
    $paths = [
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'heart'    => '<path d="M12 20s-7-4.4-9.2-8.6C1.3 8.4 3 5 6.3 5c2 0 3.2 1 3.7 2 .5-1 1.7-2 3.7-2C17 5 18.7 8.4 17.2 11.4 15 15.6 12 20 12 20z" transform="translate(2 0)"/>',
        'compare'  => '<rect x="3" y="4" width="7" height="16" rx="1.5"/><rect x="14" y="4" width="7" height="16" rx="1.5"/>',
        'gamepad'  => '<path d="M6 8h12a4 4 0 0 1 4 4v1.5a3.5 3.5 0 0 1-6.3 2.1L14.5 14h-5l-1.2 1.6A3.5 3.5 0 0 1 2 13.5V12a4 4 0 0 1 4-4z"/><path d="M7 11v3M5.5 12.5h3"/><circle cx="16" cy="11.5" r=".6"/><circle cx="18" cy="13" r=".6"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
        'x'        => '<path d="M6 6l12 12M18 6 6 18"/>',
        'check'    => '<path d="m5 12 5 5 9-10"/>',
        'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'filter'   => '<path d="M4 6h16M7 12h10M10 18h4"/>',
        'trash'    => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'edit'     => '<path d="M4 20h4L19 9l-4-4L4 16v4z"/>',
        'flag'     => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
        'thumb'    => '<path d="M7 11v9H4v-9h3zM7 11l4-8c1.5 0 2.5 1 2.5 2.5V9h5a2 2 0 0 1 2 2.3l-1.2 7A2 2 0 0 1 17.3 20H7"/>',
        'clock'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1-3.5 3.5-5 6.5-5s5.5 1.5 6.5 5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 15c2 .6 3.2 2.3 3.8 5"/>',
        'compass'  => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
        'book'     => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5zM4 20.5A2.5 2.5 0 0 0 6.5 23H20v-5"/>',
        'bolt'     => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'leaf'     => '<path d="M5 19c0-8 5-14 15-14 0 10-6 15-14 15M5 19l7-7"/>',
        'shield'   => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'logout'   => '<path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/>',
        'sparkle'  => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/>',
        'dice'     => '<rect x="3.5" y="3.5" width="17" height="17" rx="3"/><circle cx="8.5" cy="8.5" r="1"/><circle cx="15.5" cy="15.5" r="1"/><circle cx="12" cy="12" r="1"/>',
        'chevron'  => '<path d="m9 6 6 6-6 6"/>',
    ];
    $inner = $paths[$name] ?? '';
    $fill = $name === 'heart-fill' ? 'currentColor' : 'none';
    if ($name === 'heart-fill') {
        $inner = $paths['heart'];
    }
    return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" width="1em" height="1em" fill="' . $fill
        . '" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $inner . '</svg>';
}

/** URL of a game's cover: an uploaded image, or our generated SVG artwork. */
function cover_url(array $g): string
{
    if (!empty($g['cover_image'])) {
        return url('uploads/covers/' . rawurlencode($g['cover_image']));
    }
    return url('cover.php?id=' . $g['id'] . '&g=' . rawurlencode($g['primary_genre']));
}

/** Cover artwork block. Generated artwork gets the title printed on top. */
function render_cover(array $g, string $size = 'card'): string
{
    $html = '<div class="cover cover--' . e($size) . '">'
        . '<img src="' . e(cover_url($g)) . '" alt="' . e('Cover art for ' . $g['title']) . '" loading="lazy">';
    if (empty($g['cover_image'])) {
        $html .= '<span class="cover__title">' . e($g['title']) . '</span>';
    }
    return $html . '</div>';
}

/** Star rating display, e.g. 4.3 → four and a third stars filled. */
function render_stars(float $rating, string $extraClass = ''): string
{
    $pct = max(0, min(100, $rating / 5 * 100));
    return '<span class="stars ' . e($extraClass) . '" style="--pct:' . round($pct, 1) . '%" role="img" aria-label="'
        . e(number_format($rating, 1) . ' out of 5 stars') . '"></span>';
}

function render_rating_line(array $g): string
{
    if ($g['review_count'] === 0) {
        return '<span class="rating-line rating-line--none">No ratings yet</span>';
    }
    return '<span class="rating-line">' . render_stars($g['avg_rating'])
        . ' <strong>' . number_format($g['avg_rating'], 1) . '</strong>'
        . ' <span class="text-soft">(' . $g['review_count'] . ')</span></span>';
}

/** Horizontal Game DNA bars. */
function render_dna(array $g, bool $compact = false): string
{
    $html = '<ul class="dna' . ($compact ? ' dna--compact' : '') . '">';
    foreach (DNA_TRAITS as $key => $label) {
        $score = $g['dna'][$key];
        $html .= '<li class="dna__row dna--' . $key . '">'
            . '<span class="dna__label">' . $label . '</span>'
            . '<span class="dna__track" role="img" aria-label="' . e("$label $score out of 10") . '">'
            . '<span class="dna__fill" style="width:' . ($score * 10) . '%"></span></span>'
            . '<span class="dna__value">' . $score . '</span></li>';
    }
    return $html . '</ul>';
}

/** Pentagon "radar" chart of the five DNA scores, drawn with SVG. */
function render_dna_radar(array $g): string
{
    $cx = 130;
    $cy = 122;
    $r = 88;
    $keys = array_keys(DNA_TRAITS);
    $point = function (int $i, float $scale) use ($cx, $cy, $r): array {
        $angle = deg2rad(-90 + $i * 72);
        return [round($cx + cos($angle) * $r * $scale, 1), round($cy + sin($angle) * $r * $scale, 1)];
    };
    $svg = '<svg class="radar" viewBox="-50 0 360 250" role="img" aria-label="' . e('Game DNA chart for ' . $g['title']) . '">';
    foreach ([0.2, 0.4, 0.6, 0.8, 1.0] as $ring) {
        $pts = [];
        foreach ($keys as $i => $_) {
            $pts[] = implode(',', $point($i, $ring));
        }
        $svg .= '<polygon class="radar__ring" points="' . implode(' ', $pts) . '"/>';
    }
    $shape = [];
    foreach ($keys as $i => $key) {
        [$x, $y] = $point($i, 1);
        $svg .= '<line class="radar__axis" x1="' . $cx . '" y1="' . $cy . '" x2="' . $x . '" y2="' . $y . '"/>';
        $shape[] = implode(',', $point($i, $g['dna'][$key] / 10));
        [$lx, $ly] = $point($i, 1.2);
        $anchor = $lx < $cx - 5 ? 'end' : ($lx > $cx + 5 ? 'start' : 'middle');
        $svg .= '<text class="radar__label" x="' . $lx . '" y="' . ($ly + 4) . '" text-anchor="' . $anchor . '">'
            . DNA_TRAITS[$key] . ' ' . $g['dna'][$key] . '</text>';
    }
    $svg .= '<polygon class="radar__shape" points="' . implode(' ', $shape) . '"/>';
    foreach ($shape as $pt) {
        [$x, $y] = explode(',', $pt);
        $svg .= '<circle class="radar__dot" cx="' . $x . '" cy="' . $y . '" r="3.5"/>';
    }
    return $svg . '</svg>';
}

/** Up to two standout traits for a card, plus a difficulty tag. */
function dna_highlights(array $g): array
{
    $traits = ['story' => $g['story_score'], 'exploration' => $g['exploration_score'],
               'combat' => $g['combat_score'], 'replayability' => $g['replayability_score']];
    arsort($traits);
    $out = [];
    foreach (array_slice($traits, 0, 2, true) as $key => $score) {
        if ($score >= 7) {
            $out[] = ['key' => $key, 'text' => DNA_TRAITS[$key] . ' ' . $score];
        }
    }
    $out[] = ['key' => 'difficulty', 'text' => difficulty_label($g['difficulty_score'])];
    return $out;
}

function render_wishlist_button(array $g, bool $withLabel = false): string
{
    $saved = in_array($g['id'], wishlist_ids(), true);
    $label = $saved ? 'Saved' : 'Wishlist';
    return '<form method="post" action="' . e(url('actions/wishlist.php')) . '" class="d-inline" data-wishlist-form>'
        . csrf_field()
        . '<input type="hidden" name="game_id" value="' . $g['id'] . '">'
        . '<input type="hidden" name="action" value="' . ($saved ? 'remove' : 'add') . '">'
        . '<input type="hidden" name="return_to" value="' . e(current_path()) . '">'
        . '<button type="submit" class="btn btn-sm btn-icon wish-btn' . ($saved ? ' is-active' : '') . '"'
        . ' aria-pressed="' . ($saved ? 'true' : 'false') . '"'
        . ' title="' . ($saved ? 'Remove from wishlist' : 'Add to wishlist') . '"'
        . ' data-label="' . ($withLabel ? '1' : '0') . '">'
        . icon('heart', 'icon-outline') . icon('heart-fill', 'icon-filled')
        . '<span class="' . ($withLabel ? '' : 'visually-hidden') . ' wish-btn__label">' . $label . '</span>'
        . '</button></form>';
}

function render_compare_button(array $g, bool $withLabel = true): string
{
    return '<button type="button" class="btn btn-sm compare-btn" data-compare-toggle'
        . ' data-id="' . $g['id'] . '" data-title="' . e($g['title']) . '" aria-pressed="false">'
        . icon('compare') . '<span class="compare-btn__label' . ($withLabel ? '' : ' visually-hidden') . '">Compare</span></button>';
}

/** The standard game card used in grids. */
function render_game_card(array $g): string
{
    $link = e(url('game.php?id=' . $g['id']));
    $genre = e(implode(' · ', array_slice($g['genres'], 0, 2)));
    $platforms = '';
    foreach ($g['platforms'] as $slug => $name) {
        $platforms .= '<span class="plat" title="' . e($name) . '">' . e(platform_short($slug, $name)) . '</span>';
    }
    $chips = '';
    foreach (dna_highlights($g) as $chip) {
        $chips .= '<span class="dna-chip dna-chip--' . $chip['key'] . '">' . e($chip['text']) . '</span>';
    }
    $cover = render_cover($g);
    $title = e($g['title']);
    $rating = render_rating_line($g);
    $wish = render_wishlist_button($g);
    $compare = render_compare_button($g);
    return <<<HTML
<article class="game-card" data-game-id="{$g['id']}">
  <a class="game-card__cover" href="$link" tabindex="-1" aria-hidden="true">$cover</a>
  <div class="game-card__body">
    <p class="game-card__meta">$genre <span aria-hidden="true">·</span> {$g['release_year']}</p>
    <h3 class="game-card__title"><a href="$link">$title</a></h3>
    <div class="game-card__rating">$rating</div>
    <div class="game-card__chips">$chips</div>
    <div class="game-card__platforms">$platforms</div>
    <div class="game-card__actions">
      <a class="btn btn-sm btn-ghost" href="$link">Details</a>
      <span class="ms-auto d-inline-flex gap-1">$wish$compare</span>
    </div>
  </div>
</article>
HTML;
}

/** One review, with the right actions for the viewer. */
function render_review(array $r, bool $showGame = false): string
{
    $user = current_user();
    $own = $user && (int) $user['id'] === (int) $r['user_id'];
    $returnTo = e(current_path());
    $csrf = csrf_field();
    $action = e(url('actions/review.php'));
    $edited = $r['updated_at'] ? ' <span class="text-soft">(edited)</span>' : '';
    $gameLink = $showGame
        ? '<a class="review__game" href="' . e(url('game.php?id=' . $r['game_id'])) . '">' . e($r['game_title']) . '</a>'
        : '';
    $helpful = (int) $r['helpful_count'];
    $helpfulText = $helpful > 0 ? plural($helpful, 'person', 'people') . ' found this helpful' : '';

    $actions = '';
    if ($own) {
        $actions .= '<a class="btn btn-sm btn-ghost" href="' . e(url('game.php?id=' . $r['game_id'])) . '#review-form">'
            . icon('edit') . ' Edit</a>'
            . '<form method="post" action="' . $action . '" class="d-inline" data-confirm="Delete your review? This cannot be undone.">'
            . $csrf . '<input type="hidden" name="action" value="delete">'
            . '<input type="hidden" name="review_id" value="' . (int) $r['id'] . '">'
            . '<input type="hidden" name="return_to" value="' . $returnTo . '">'
            . '<button class="btn btn-sm btn-ghost text-danger-soft">' . icon('trash') . ' Delete</button></form>';
    } elseif ($user) {
        $voted = (bool) $r['voted'];
        $actions .= '<form method="post" action="' . $action . '" class="d-inline">'
            . $csrf . '<input type="hidden" name="action" value="helpful">'
            . '<input type="hidden" name="review_id" value="' . (int) $r['id'] . '">'
            . '<input type="hidden" name="return_to" value="' . $returnTo . '">'
            . '<button class="btn btn-sm btn-ghost' . ($voted ? ' is-active' : '') . '" aria-pressed="' . ($voted ? 'true' : 'false') . '">'
            . icon('thumb') . ($voted ? ' Helpful ✓' : ' Helpful') . '</button></form>';
        if ($r['reported']) {
            $actions .= '<span class="btn btn-sm btn-ghost disabled" aria-disabled="true">' . icon('flag') . ' Reported</span>';
        } else {
            $actions .= '<details class="report">'
                . '<summary class="btn btn-sm btn-ghost">' . icon('flag') . ' Report</summary>'
                . '<form method="post" action="' . $action . '" class="report__form">' . $csrf
                . '<input type="hidden" name="action" value="report">'
                . '<input type="hidden" name="review_id" value="' . (int) $r['id'] . '">'
                . '<input type="hidden" name="return_to" value="' . $returnTo . '">'
                . '<label class="form-label small mb-1" for="reason-' . (int) $r['id'] . '">Why are you reporting this review?</label>'
                . '<select class="form-select form-select-sm mb-2" name="reason" id="reason-' . (int) $r['id'] . '" required>'
                . '<option value="">Choose a reason…</option><option value="spam">Spam or advertising</option>'
                . '<option value="offensive">Offensive or abusive</option><option value="spoilers">Major spoilers</option>'
                . '<option value="off_topic">Not about the game</option><option value="other">Something else</option></select>'
                . '<input class="form-control form-control-sm mb-2" name="details" maxlength="300" placeholder="Details (optional)">'
                . '<button class="btn btn-sm btn-amber">Send report</button></form></details>';
        }
    }

    $body = nl2br(e($r['body']));
    $stars = render_stars((float) $r['rating']);
    $date = e(format_date($r['created_at']));
    $name = e($r['username']);
    $initial = e(mb_strtoupper(mb_substr($r['username'], 0, 1)));
    return <<<HTML
<article class="review" id="review-{$r['id']}">
  <header class="review__head">
    <span class="avatar" aria-hidden="true">$initial</span>
    <div>
      <strong>$name</strong>
      <div class="review__meta">$stars <span>$date$edited</span></div>
    </div>
    $gameLink
  </header>
  <p class="review__body">$body</p>
  <footer class="review__foot">
    <span class="text-soft small">$helpfulText</span>
    <div class="review__actions">$actions</div>
  </footer>
</article>
HTML;
}

/** Pagination links. $makeUrl receives a page number and returns a URL. */
function render_pagination(int $page, int $pages, callable $makeUrl): string
{
    if ($pages <= 1) {
        return '';
    }
    $html = '<nav aria-label="Pages"><ul class="pagination justify-content-center">';
    $html .= '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '"><a class="page-link" href="'
        . e($makeUrl(max(1, $page - 1))) . '" data-page-link>Previous</a></li>';
    for ($p = 1; $p <= $pages; $p++) {
        $html .= '<li class="page-item' . ($p === $page ? ' active' : '') . '"><a class="page-link" href="'
            . e($makeUrl($p)) . '" data-page-link' . ($p === $page ? ' aria-current="page"' : '') . '>' . $p . '</a></li>';
    }
    $html .= '<li class="page-item' . ($page >= $pages ? ' disabled' : '') . '"><a class="page-link" href="'
        . e($makeUrl(min($pages, $page + 1))) . '" data-page-link>Next</a></li>';
    return $html . '</ul></nav>';
}
