<?php
// Expects $pageTitle (optional) and $db from config.php
$mode = setting($db, 'theme', 'normal');
$announcement = setting($db, 'announcement');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h(($pageTitle ?? 'Home') . " · Neil and Avi's Coffee Shop") ?></title>
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="mode-<?= h($mode) ?>">
<?php if ($announcement !== ''): ?>
  <div class="announce"><?= h($announcement) ?></div>
<?php endif; ?>
<header class="site-header">
  <a class="brand" href="/">
    <svg class="brand-cup" viewBox="0 0 48 48" aria-hidden="true">
      <path d="M17 6c-2 3 2 5 0 8M24 4c-2 3 2 5 0 8M31 6c-2 3 2 5 0 8" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
      <path d="M8 18h28v10a12 12 0 0 1-12 12h-4A12 12 0 0 1 8 28z" fill="currentColor"/>
      <path d="M36 21h3a5 5 0 0 1 0 10h-4" fill="none" stroke="currentColor" stroke-width="3"/>
    </svg>
    <span>Neil <em>&amp;</em> Avi's<small>Coffee Shop</small></span>
  </a>
  <nav>
    <a href="/">Home</a>
    <a href="/menu.php">Menu</a>
    <a href="/order.php">Order</a>
    <?php if (is_admin()): ?>
      <a href="/admin/">Back office</a>
    <?php endif; ?>
  </nav>
</header>
<main>
