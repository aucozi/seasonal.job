<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

if (!db_installed()) {
    render('header', ['title' => 'Coming soon']);
    echo '<h1 class="page-title">Coming soon</h1><p>The First Edition opens shortly. Follow <a href="'
        . e(config('instagram_url')) . '">@seasonal.job</a> for the drop.</p>';
    render('footer');
    exit;
}

$products = products_all();
$totalMade = array_sum(array_column($products, 'edition_size'));

render('header', ['bodyClass' => 'home']);
?>
<h1 class="page-title">First Edition</h1>
<p class="lede">LITHUSA, the original fair run. <?= $totalMade ?> pieces with their original hang tags, each shipped with a numbered card. When they're gone, they're gone.</p>
<p class="small">Ships from the US · <?= money((int) config('shipping.flat_cents', 600)) ?> shipping, free over <?= money((int) config('shipping.free_over_cents', 6500)) ?></p>

<div class="grid">
  <?php foreach ($products as $product) { render('product-card', ['product' => $product]); } ?>
</div>
<?php render('footer');
