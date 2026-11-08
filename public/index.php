<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

if (!db_installed()) {
    render('header', ['title' => 'Coming soon']);
    echo '<main class="wrap narrow"><h1>Coming soon</h1><p>The First Edition opens shortly. Follow <a href="'
        . e(config('instagram_url')) . '">@seasonal.job</a> for the drop.</p></main>';
    render('footer');
    exit;
}

$products = products_all();
$totalMade = array_sum(array_column($products, 'edition_size'));

render('header', ['bodyClass' => 'home']);
?>
<main>
  <section class="hero">
    <div class="hero-media" style="background-image:url('<?= e(asset('img/hero.svg')) ?>')" role="img" aria-label="LITHUSA at the fair"></div>
    <div class="hero-copy wrap">
      <p class="meta rec"><span class="dot" aria-hidden="true"></span> Drop 01 · First Edition</p>
      <h1>LITHUSA</h1>
      <p class="lede">The original fair run. Numbered. Once.</p>
      <a class="btn" href="#shop">Shop the First Edition</a>
    </div>
  </section>

  <section id="shop" class="wrap products">
    <div class="section-head">
      <h2>First Edition</h2>
      <p class="meta"><?= $totalMade ?> pieces made · US shipping · free over <?= money((int) config('shipping.free_over_cents', 6500)) ?></p>
    </div>
    <div class="grid">
      <?php foreach ($products as $product) { render('product-card', ['product' => $product]); } ?>
    </div>
  </section>

  <section class="wrap story">
    <p class="meta">Behind the camera</p>
    <div class="story-grid">
      <h2>Made by the eye behind seasonal.job.</h2>
      <div>
        <p>seasonal.job films gigs, events and the people in them. LITHUSA started as a shirt for one fair, for everyone who lives between Lithuania and the USA.</p>
        <p>These are the pieces left from that first run, with their original tags and packaging. Each one ships with a card carrying its number. When they're gone, LITHUSA returns as a printed-to-order Edition 02.</p>
        <p><a class="link" href="<?= e(config('instagram_url')) ?>" rel="noopener">See the work on Instagram →</a></p>
      </div>
    </div>
  </section>
</main>
<?php render('footer');
