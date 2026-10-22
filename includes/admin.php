<?php
/*
 * Helpers for the admin pages. Every admin page starts with:
 *     require __DIR__ . '/../includes/init.php';
 *     require APP_ROOT . '/includes/admin.php';
 *     $admin = require_admin();
 */

function render_admin_nav(string $active): string
{
    $openReports = (int) db_value("SELECT COUNT(DISTINCT review_id) FROM review_reports WHERE status = 'open'");
    $items = [
        'dashboard' => ['admin/index.php', 'Dashboard'],
        'games'     => ['admin/games.php', 'Games'],
        'taxonomy'  => ['admin/taxonomy.php', 'Genres & platforms'],
        'users'     => ['admin/users.php', 'Users'],
        'reviews'   => ['admin/reviews.php', 'Reviews & reports'],
    ];
    $html = '<nav class="admin-nav" aria-label="Admin">';
    foreach ($items as $key => [$href, $label]) {
        $badge = ($key === 'reviews' && $openReports) ? ' <span class="badge rounded-pill">' . $openReports . '</span>' : '';
        $html .= '<a class="' . ($key === $active ? 'active' : '') . '" href="' . e(url($href)) . '"'
            . ($key === $active ? ' aria-current="page"' : '') . '>' . e($label) . $badge . '</a>';
    }
    return $html . '</nav>';
}

function slugify(string $text): string
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    return $slug !== '' ? $slug : 'item';
}

/** A slug that is not used by another row in $table. */
function unique_slug(string $table, string $text, ?int $exceptId = null): string
{
    $base = mb_substr(slugify($text), 0, 120);
    $slug = $base;
    $n = 2;
    while (db_value("SELECT 1 FROM $table WHERE slug = ? AND id <> ?", [$slug, $exceptId ?? 0])) {
        $slug = $base . '-' . $n++;
    }
    return $slug;
}

const COVER_MAX_BYTES = 2 * 1024 * 1024;
const COVER_DIR = APP_ROOT . '/uploads/covers/';

/**
 * Validate an uploaded cover image and save a clean copy.
 * Returns the new file name. Throws InvalidArgumentException with a friendly message.
 *
 * Safety steps: size limit, real MIME type from file contents (not the browser's claim),
 * image dimensions check, then re-encoding with GD so only pixel data is kept.
 * The saved name is random and always ends in .jpg, so it can never be run as a script.
 */
function save_cover_upload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $message = match ($file['error'] ?? null) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The image is larger than the upload limit (2 MB).',
            UPLOAD_ERR_PARTIAL => 'The upload did not finish. Please try again.',
            default => 'The image could not be uploaded.',
        };
        throw new InvalidArgumentException($message);
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('Invalid upload.');
    }
    if ($file['size'] > COVER_MAX_BYTES) {
        throw new InvalidArgumentException('The image must be 2 MB or smaller.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $loaders = ['image/jpeg' => 'imagecreatefromjpeg', 'image/png' => 'imagecreatefrompng', 'image/webp' => 'imagecreatefromwebp'];
    if (!isset($loaders[$mime])) {
        throw new InvalidArgumentException('Please upload a JPG, PNG or WebP image.');
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || $info[0] < 150 || $info[1] < 150 || $info[0] > 5000 || $info[1] > 5000) {
        throw new InvalidArgumentException('The image must be between 150 and 5000 pixels on each side.');
    }
    if (!function_exists($loaders[$mime])) {
        throw new InvalidArgumentException('This server cannot process that image type. Enable the GD extension in php.ini.');
    }

    $source = @$loaders[$mime]($file['tmp_name']);
    if ($source === false) {
        throw new InvalidArgumentException('The file is not a valid image.');
    }

    // Re-draw onto a fresh canvas, at most 600 × 800, keeping the aspect ratio.
    [$w, $h] = [imagesx($source), imagesy($source)];
    $scale = min(1, 600 / $w, 800 / $h);
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $canvas = imagecreatetruecolor($nw, $nh);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 21, 26, 45)); // background for transparent PNGs
    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $nw, $nh, $w, $h);

    $name = bin2hex(random_bytes(16)) . '.jpg';
    if (!is_dir(COVER_DIR) && !mkdir(COVER_DIR, 0755, true)) {
        throw new InvalidArgumentException('The uploads/covers folder is missing and could not be created.');
    }
    if (!imagejpeg($canvas, COVER_DIR . $name, 85)) {
        throw new InvalidArgumentException('Could not save the image. Check that uploads/covers is writable.');
    }
    return $name;
}

function delete_cover_file(?string $name): void
{
    // Only delete files that look like names we generated.
    if ($name && preg_match('/^[a-f0-9]{32}\.jpg$/', $name) && is_file(COVER_DIR . $name)) {
        unlink(COVER_DIR . $name);
    }
}
