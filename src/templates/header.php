<?php
$pageTitle = isset($title) && $title !== '' ? $title . ' — seasonal.job' : 'seasonal.job — LITHUSA First Edition';
$pageDescription = $description ?? 'LITHUSA First Edition by seasonal.job. The original fair run: 33 tees and 31 hats, numbered.';
$cartCount = cart_count();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDescription) ?>">
<?php if (is_file(APP_ROOT . '/public/assets/img/og.jpg')): ?>
<meta property="og:image" content="<?= e(url('assets/img/og.jpg')) ?>">
<?php endif; ?>
<meta name="theme-color" content="#0c0b0a">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<div class="grain" aria-hidden="true"></div>
<header class="site-header">
  <div class="wrap bar">
    <a class="wordmark" href="<?= e(url()) ?>">seasonal.job</a>
    <nav class="nav" aria-label="Main">
      <a href="<?= e(url('#shop')) ?>">Shop</a>
      <a href="<?= e(url('page.php?p=about')) ?>">About</a>
      <a class="cart-link" href="<?= e(url('cart.php')) ?>">Cart<?php if ($cartCount): ?> <span class="badge"><?= $cartCount ?></span><?php endif; ?></a>
    </nav>
  </div>
</header>
<?php if ($message = flash()): ?>
<div class="wrap"><p class="flash" role="status"><?= e($message) ?></p></div>
<?php endif; ?>
