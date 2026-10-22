<?php
/*
 * Page header. Before including, a page may set:
 *   $pageTitle   — text for the <title>
 *   $activeNav   — which navbar item to highlight (home, explore, compare, recommend, community, wishlist)
 *   $pageScripts — extra JS files from assets/js, e.g. ['compare.js']
 */
$pageTitle = isset($pageTitle) ? $pageTitle . ' · Gaming Hub' : 'Gaming Hub · Find Your Next Game';
$activeNav = $activeNav ?? '';
$user = current_user();
?><!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="Gaming Hub helps you discover, compare and choose your next game.">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <meta name="base-path" content="<?= e(base_path()) ?>">
  <meta name="logged-in" content="<?= $user ? '1' : '0' ?>">
  <link rel="icon" href="<?= e(url('assets/images/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@100..125,500..900&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body data-page="<?= e($activeNav) ?>">
<a class="visually-hidden-focusable skip-link" href="#main">Skip to content</a>
<?php require __DIR__ . '/navbar.php'; ?>
<?php $flashes = take_flashes(); if ($flashes): ?>
  <div class="container flash-stack">
    <?php foreach ($flashes as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($f['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<div id="main" tabindex="-1"></div>
