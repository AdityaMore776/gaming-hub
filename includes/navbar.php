<?php
/* Main navigation. Uses $activeNav and $user from header.php. */
$navItems = [
    'home'      => ['index.php', 'Home'],
    'explore'   => ['explore.php', 'Explore Games'],
    'compare'   => ['compare.php', 'Compare'],
    'recommend' => ['recommend.php', 'What Should I Play?'],
    'community' => ['reviews.php', 'Community'],
    'wishlist'  => ['wishlist.php', 'Wishlist'],
];
?>
<nav class="navbar navbar-expand-lg site-nav" aria-label="Main">
  <div class="container">
    <a class="navbar-brand logo" href="<?= e(url('index.php')) ?>" aria-label="Gaming Hub home">
      <span class="logo__mark" aria-hidden="true"><?= icon('gamepad') ?></span>
      <span class="logo__word">Gaming<span>Hub</span></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav mx-lg-auto">
        <?php foreach ($navItems as $key => [$href, $label]): ?>
          <li class="nav-item">
            <a class="nav-link<?= $activeNav === $key ? ' active' : '' ?>" href="<?= e(url($href)) ?>"
               <?= $activeNav === $key ? 'aria-current="page"' : '' ?>>
              <?= e($label) ?>
              <?php if ($key === 'compare'): ?>
                <span class="nav-count" data-compare-count hidden>0</span>
              <?php endif; ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-account">
        <?php if ($user): ?>
          <div class="dropdown">
            <button class="btn btn-account dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="avatar avatar--sm" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['username'], 0, 1))) ?></span>
              <?= e($user['username']) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= e(url('profile.php')) ?>"><?= icon('user') ?> Profile &amp; preferences</a></li>
              <li><a class="dropdown-item" href="<?= e(url('wishlist.php')) ?>"><?= icon('heart') ?> My wishlist</a></li>
              <?php if ($user['role'] === 'admin'): ?>
                <li><a class="dropdown-item" href="<?= e(url('admin/index.php')) ?>"><?= icon('shield') ?> Admin dashboard</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li>
                <form method="post" action="<?= e(url('actions/logout.php')) ?>">
                  <?= csrf_field() ?>
                  <button class="dropdown-item" type="submit"><?= icon('logout') ?> Log out</button>
                </form>
              </li>
            </ul>
          </div>
        <?php else: ?>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('login.php')) ?>">Log in</a>
          <a class="btn btn-amber btn-sm" href="<?= e(url('register.php')) ?>">Sign up</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
