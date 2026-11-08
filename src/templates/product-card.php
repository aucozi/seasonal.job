<?php $soldOut = $product['remaining'] <= 0; $images = product_images($product); ?>
<a class="card<?= $soldOut ? ' is-sold-out' : '' ?>" href="<?= e(url('product.php?p=' . $product['slug'])) ?>">
  <span class="card-media">
    <img src="<?= e(asset($images[0])) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
    <?php if (isset($images[1])): ?><img class="alt" src="<?= e(asset($images[1])) ?>" alt="" loading="lazy"><?php endif; ?>
  </span>
  <span class="card-name"><?= e($product['name']) ?></span>
  <span class="card-meta"><?= money((int) $product['price_cents']) ?> · <?= $soldOut ? 'sold out' : (int) $product['remaining'] . ' of ' . (int) $product['edition_size'] . ' left' ?></span>
</a>
