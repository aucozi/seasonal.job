<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

$pages = [
    'about' => 'About',
    'shipping' => 'Shipping',
    'returns' => 'Returns and exchanges',
    'size-guide' => 'Size guide',
    'privacy' => 'Privacy',
];
$slug = (string) ($_GET['p'] ?? '');
if (!isset($pages[$slug])) {
    not_found();
}

render('header', ['title' => $pages[$slug]]);
?>
<h1 class="page-title"><?= e($pages[$slug]) ?></h1>
<div class="prose">
  <?php render('pages/' . $slug); ?>
</div>
<?php render('footer');
