<?php
$pageTitle = isset($title) && $title !== '' ? $title . ' — seasonal job shop' : 'seasonal job shop — LITHUSA First Edition';
$pageDescription = $description ?? 'LITHUSA First Edition by Ben Zilinskas / seasonal job. The original fair run: 33 tees and 31 hats, numbered.';
$cartCount = cart_count();
$mainSite = (string) config('main_site_url', 'https://benzilinskas.com');
$current = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$currentPage = (string) ($_GET['p'] ?? '');
$navItems = [
    ['First Edition', url(), $current === 'index.php' || $current === 'product.php'],
    ['About', url('page.php?p=about'), $current === 'page.php' && $currentPage === 'about'],
    ['Size guide', url('page.php?p=size-guide'), $current === 'page.php' && $currentPage === 'size-guide'],
    ['Shipping', url('page.php?p=shipping'), $current === 'page.php' && $currentPage === 'shipping'],
    ['Cart' . ($cartCount ? " ($cartCount)" : ''), url('cart.php'), $current === 'cart.php'],
];
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
<meta name="theme-color" content="#f5f3f1">
<link rel="preload" href="<?= e(url('assets/fonts/albura_normal_400.otf')) ?>" as="font" type="font/otf" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip-link" href="#content">Skip to content</a>
<div class="layout">
  <header class="sidebar">
    <p class="site-name"><a href="<?= e(url()) ?>">seasonal job</a></p>
    <p class="site-title"><a href="<?= e($mainSite) ?>">benzilinskas.com</a> <span>/ shop</span></p>
    <nav class="site-nav" aria-label="Shop">
      <?php foreach ($navItems as [$label, $href, $active]): ?>
        <a href="<?= e($href) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
  </header>
  <main id="content" class="content">
    <?php if ($message = flash()): ?>
      <p class="flash" role="status"><?= e($message) ?></p>
    <?php endif; ?>
