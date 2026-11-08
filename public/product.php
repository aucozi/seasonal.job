<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

$product = product_by_slug((string) ($_GET['p'] ?? '')) ?? not_found();
$soldOut = $product['remaining'] <= 0;
$singleVariant = count($product['variants']) === 1 ? $product['variants'][0] : null;
$firstAvailable = null;
foreach ($product['variants'] as $variant) {
    if ($variant['stock'] > 0) {
        $firstAvailable = (int) $variant['id'];
        break;
    }
}

render('header', [
    'title' => $product['name'] . ' — First Edition',
    'description' => $product['subtitle'] . '. ' . strtok($product['description'], "\n"),
]);
?>
<main class="wrap product">
  <div class="product-media">
    <?php foreach (product_images($product) as $i => $image): ?>
      <img src="<?= e(asset($image)) ?>" alt="<?= $i === 0 ? e($product['name']) : '' ?>"<?= $i ? ' loading="lazy"' : '' ?>>
    <?php endforeach; ?>
  </div>
  <div class="product-info">
    <p class="meta"><a href="<?= e(url('#shop')) ?>">Shop</a> / <?= e($product['subtitle']) ?></p>
    <h1><?= e($product['name']) ?></h1>
    <p class="price big"><?= money((int) $product['price_cents']) ?></p>
    <p class="meta count"><?= (int) $product['remaining'] ?> of <?= (int) $product['edition_size'] ?> left</p>

    <?php if ($soldOut): ?>
      <p class="sold-out-note">All <?= (int) $product['edition_size'] ?> are gone. Join the list below to hear about Edition 02.</p>
    <?php else: ?>
      <form class="buy" method="post" action="<?= e(url('cart.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <?php if ($singleVariant): ?>
          <input type="hidden" name="variant_id" value="<?= (int) $singleVariant['id'] ?>">
          <p class="meta">Size: <?= e($singleVariant['label']) ?></p>
        <?php else: ?>
          <fieldset class="sizes">
            <legend class="meta">Size<?php if ($product['slug'] === 'lithusa-tee'): ?> · <a href="<?= e(url('page.php?p=size-guide')) ?>">size guide</a><?php endif; ?></legend>
            <?php foreach ($product['variants'] as $variant): $out = $variant['stock'] <= 0; ?>
              <label class="size<?= $out ? ' is-out' : '' ?>">
                <input type="radio" name="variant_id" value="<?= (int) $variant['id'] ?>" <?= $out ? 'disabled' : '' ?> <?= (int) $variant['id'] === $firstAvailable ? 'checked' : '' ?> required>
                <span><?= e($variant['label']) ?></span>
                <small><?= $out ? 'gone' : (int) $variant['stock'] . ' left' ?></small>
              </label>
            <?php endforeach; ?>
          </fieldset>
        <?php endif; ?>
        <input type="hidden" name="qty" value="1">
        <button class="btn wide" type="submit">Add to cart</button>
      </form>
    <?php endif; ?>

    <div class="description">
      <?php foreach (explode("\n\n", $product['description']) as $paragraph): ?>
        <p><?= e($paragraph) ?></p>
      <?php endforeach; ?>
    </div>
    <?php render('hang-tag'); ?>
    <ul class="facts meta">
      <li>Original hang tag from the fair run</li>
      <li>Numbered card out of <?= (int) $product['edition_size'] ?></li>
      <li>Ships from the US within 2 business days</li>
      <li>Shipping <?= money((int) config('shipping.flat_cents', 600)) ?> · free over <?= money((int) config('shipping.free_over_cents', 6500)) ?></li>
    </ul>
  </div>
</main>
<?php render('footer');
