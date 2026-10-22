<?php
/* Page footer, the comparison tray, the login prompt and shared scripts. */
$pageScripts = $pageScripts ?? [];
?>
<footer class="site-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <a class="logo logo--footer" href="<?= e(url('index.php')) ?>">
          <span class="logo__mark" aria-hidden="true"><?= icon('gamepad') ?></span>
          <span class="logo__word">Gaming<span>Hub</span></span>
        </a>
        <p class="mt-3 text-soft">Find Your Next Game. Discover, compare and choose games that suit how you like to play.</p>
      </div>
      <div class="col-6 col-lg-2">
        <h2 class="footer-heading">Discover</h2>
        <ul class="footer-links">
          <li><a href="<?= e(url('explore.php')) ?>">Explore games</a></li>
          <li><a href="<?= e(url('recommend.php')) ?>">What should I play?</a></li>
          <li><a href="<?= e(url('compare.php')) ?>">Compare games</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h2 class="footer-heading">Community</h2>
        <ul class="footer-links">
          <li><a href="<?= e(url('reviews.php')) ?>">Reviews</a></li>
          <li><a href="<?= e(url('wishlist.php')) ?>">Wishlist</a></li>
          <li><a href="<?= e(url('profile.php')) ?>">Your profile</a></li>
        </ul>
      </div>
      <div class="col-lg-4">
        <h2 class="footer-heading">About this project</h2>
        <p class="small text-soft mb-2">
          Gaming Hub is an independent student project built for a Web Technology course. It is not affiliated
          with any game publisher, developer or platform. Game names belong to their respective owners.
        </p>
        <p class="small text-soft mb-0">
          Game DNA scores are our team's editorial estimates. Demo reviews are sample data.
          <a href="<?= e(url('about.php')) ?>">Read more</a>
        </p>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> Gaming Hub student project</span>
      <span>HTML · CSS · Bootstrap · JavaScript · PHP · MySQL</span>
    </div>
  </div>
</footer>

<!-- Comparison tray: filled in by main.js from the saved selection -->
<aside class="compare-tray" data-compare-tray hidden aria-label="Games selected for comparison">
  <div class="container compare-tray__inner">
    <div class="compare-tray__label">
      <strong>Compare</strong>
      <span class="text-soft small" data-compare-hint>Pick up to 3 games</span>
    </div>
    <ul class="compare-tray__items" data-compare-items></ul>
    <div class="compare-tray__actions">
      <button type="button" class="btn btn-sm btn-ghost" data-compare-clear>Clear</button>
      <a class="btn btn-sm btn-amber" href="<?= e(url('compare.php')) ?>" data-compare-go>Compare now <?= icon('arrow') ?></a>
    </div>
  </div>
</aside>

<!-- Shown when a guest tries to save a game -->
<div class="modal fade" id="loginPrompt" tabindex="-1" aria-labelledby="loginPromptTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="loginPromptTitle">Log in to save games</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Your wishlist, reviews and saved preferences are stored in your Gaming Hub account.
           Comparing games and taking the quiz work without an account.</p>
      </div>
      <div class="modal-footer">
        <a class="btn btn-ghost" data-login-link href="<?= e(url('register.php')) ?>">Create account</a>
        <a class="btn btn-amber" data-login-link href="<?= e(url('login.php')) ?>">Log in</a>
      </div>
    </div>
  </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" data-toasts></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="<?= e(asset('js/main.js')) ?>"></script>
<?php foreach ($pageScripts as $script): ?>
  <script src="<?= e(asset('js/' . $script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
