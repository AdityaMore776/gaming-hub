<?php
/*
 * Review actions for logged-in users.
 * POST action = save | delete | helpful | report
 */
require __DIR__ . '/../includes/init.php';

require_post();
$user = require_login();
$uid = (int) $user['id'];
$action = choice($_POST['action'] ?? '', ['save', 'delete', 'helpful', 'report']);
$back = safe_return_path($_POST['return_to'] ?? null, 'reviews.php');

switch ($action) {
    // -------------------------------------------------------- create or update your own review
    case 'save':
        $gameId = int_param($_POST, 'game_id');
        $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
        $body = trim((string) ($_POST['body'] ?? ''));
        $bodyLength = mb_strlen($body);

        if (!$gameId || !db_value('SELECT 1 FROM games WHERE id = ?', [$gameId])) {
            flash('danger', 'That game could not be found.');
            redirect('explore.php');
        }
        $errors = [];
        if ($rating === false || $rating === null) {
            $errors[] = 'choose a rating from 1 to 5 stars';
        }
        if ($bodyLength < 10 || $bodyLength > 2000) {
            $errors[] = 'write between 10 and 2000 characters';
        }
        if ($errors) {
            $_SESSION['old_review'] = ['rating' => $rating ?: 0, 'body' => mb_substr($body, 0, 2000)];
            flash('danger', 'Please ' . implode(' and ', $errors) . '.');
            redirect(url('game.php?id=' . $gameId) . '#review-form');
        }

        $existing = db_value('SELECT id FROM reviews WHERE user_id = ? AND game_id = ?', [$uid, $gameId]);
        if ($existing) {
            db_query('UPDATE reviews SET rating = ?, body = ?, updated_at = NOW() WHERE id = ? AND user_id = ?',
                [$rating, $body, $existing, $uid]);
            flash('success', 'Your review was updated.');
        } else {
            // The UNIQUE (user_id, game_id) key also blocks duplicates if two requests race.
            db_query('INSERT IGNORE INTO reviews (game_id, user_id, rating, body) VALUES (?, ?, ?, ?)', [$gameId, $uid, $rating, $body]);
            flash('success', 'Thanks! Your review was posted.');
        }
        redirect(url('game.php?id=' . $gameId) . '#reviews');

    // -------------------------------------------------------- delete your own review
    case 'delete':
        $reviewId = int_param($_POST, 'review_id');
        // "AND user_id = ?" means nobody can delete someone else's review.
        $deleted = $reviewId ? db_query('DELETE FROM reviews WHERE id = ? AND user_id = ?', [$reviewId, $uid])->rowCount() : 0;
        flash($deleted ? 'success' : 'danger', $deleted ? 'Your review was deleted.' : 'You can only delete your own reviews.');
        redirect($back);

    // -------------------------------------------------------- mark helpful (click again to undo)
    case 'helpful':
        $reviewId = int_param($_POST, 'review_id');
        $review = $reviewId ? db_one('SELECT id, user_id FROM reviews WHERE id = ? AND is_hidden = 0', [$reviewId]) : null;
        if ($review === null) {
            flash('danger', 'That review is no longer available.');
        } elseif ((int) $review['user_id'] === $uid) {
            flash('warning', 'You cannot vote on your own review.');
        } elseif (db_value('SELECT 1 FROM review_votes WHERE review_id = ? AND user_id = ?', [$reviewId, $uid])) {
            db_query('DELETE FROM review_votes WHERE review_id = ? AND user_id = ?', [$reviewId, $uid]);
            flash('info', 'Your helpful vote was removed.');
        } else {
            // Primary key (review_id, user_id) = one vote per user per review.
            db_query('INSERT IGNORE INTO review_votes (review_id, user_id) VALUES (?, ?)', [$reviewId, $uid]);
            flash('success', 'Thanks for your feedback.');
        }
        redirect($back);

    // -------------------------------------------------------- report to moderators
    case 'report':
        $reviewId = int_param($_POST, 'review_id');
        $reason = choice($_POST['reason'] ?? '', ['spam', 'offensive', 'spoilers', 'off_topic', 'other']);
        $details = mb_substr(trim((string) ($_POST['details'] ?? '')), 0, 300);
        $review = $reviewId ? db_one('SELECT id, user_id FROM reviews WHERE id = ?', [$reviewId]) : null;
        if ($review === null || $reason === '') {
            flash('danger', 'Please choose a reason for your report.');
        } elseif ((int) $review['user_id'] === $uid) {
            flash('warning', 'You cannot report your own review.');
        } else {
            $added = db_query('INSERT IGNORE INTO review_reports (review_id, reporter_id, reason, details) VALUES (?, ?, ?, ?)',
                [$reviewId, $uid, $reason, $details === '' ? null : $details])->rowCount();
            flash($added ? 'success' : 'info', $added
                ? 'Thanks. A moderator will look at this review.'
                : 'You have already reported this review.');
        }
        redirect($back);

    default:
        flash('danger', 'Unknown action.');
        redirect($back);
}
