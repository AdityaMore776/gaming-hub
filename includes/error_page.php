<?php
/* Minimal stand-alone error page (it must not depend on the database). */
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Something went wrong · Gaming Hub</title>
  <style>
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #151A2D; color: #E8E5F7;
           font: 16px/1.6 system-ui, sans-serif; padding: 16px; }
    .box { max-width: 560px; background: #1D2240; border: 1px solid #2E3460; border-radius: 12px; padding: 32px; }
    h1 { margin: 0 0 8px; font-size: 1.4rem; }
    code { display: block; margin-top: 16px; padding: 12px; background: #151A2D; border-radius: 8px; color: #FFB454;
           white-space: pre-wrap; word-break: break-word; font-size: .85rem; }
    a { color: #35C6B0; }
  </style>
</head>
<body>
  <div class="box">
    <h1>Something went wrong</h1>
    <p>The page could not be loaded. If you are setting up Gaming Hub, check that MySQL is running and that
       <strong>config/config.php</strong> has the right database details (see README.md).</p>
    <?php if (!empty($details)): ?>
      <code><?= htmlspecialchars($details, ENT_QUOTES, 'UTF-8') ?></code>
    <?php endif; ?>
    <p><a href="javascript:history.back()">Go back</a></p>
  </div>
</body>
</html>
