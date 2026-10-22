<?php
/* Log out (POST only, with CSRF check, so other sites cannot log you out with a link). */
require __DIR__ . '/../includes/init.php';

require_post();
logout_user();
start_session();
flash('success', 'You have been logged out.');
redirect('index.php');
