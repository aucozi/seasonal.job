<?php $soldOut = $product['remaining'] <= 0; $images = product_images($product); ?>
<a class="card<?= $soldOut ? ' is-sold-out' : '' ?>" href="<?= e(url('product.php?p=' . $product['slug'])) ?>">
  <div class="card-media">
    <img src="<?= e(asset($images[0])) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
    <?php if (isset($images[1])): ?><img class="alt" src="<?= e(asset($images[1])) ?>" alt="" loading="lazy"><?php endif; ?>
    <?php if ($soldOut): ?><span class="tag">Sold out</span><?php endif; ?>
  </div>
  <div class="card-body">
    <h3><?= e($product['name']) ?></h3>
    <p class="price"><?= money((int) $product['price_cents']) ?></p>
    <p class="meta"><?= e($product['subtitle']) ?></p>
    <p class="meta count"><?= (int) $product['remaining'] ?> of <?= (int) $product['edition_size'] ?> left</p>
  </div>
</a>
