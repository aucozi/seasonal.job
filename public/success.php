<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

$order = order_find_by_token((string) ($_GET['o'] ?? '')) ?? not_found();
$items = order_items((int) $order['id']);
$sizes = edition_sizes();
$paid = in_array($order['status'], ['paid', 'shipped'], true);
cart_clear();

render('header', ['title' => 'Thank you']);
?>
<main class="wrap narrow">
  <p class="meta">Order #<?= (int) $order['id'] ?></p>
  <h1>Thank you.</h1>
  <?php if ($paid): ?>
    <p>Your payment went through. A confirmation is on its way to <?= e($order['email']) ?>.</p>
  <?php else: ?>
    <p>We're confirming your payment with Stripe. A confirmation email will arrive in a minute or two.</p>
  <?php endif; ?>

  <ul class="receipt">
    <?php foreach ($items as $item): $label = edition_label($item, (int) ($sizes[$item['product_id']] ?? 0)); ?>
      <li>
        <span><?= (int) $item['qty'] ?> × <?= e($item['product_name']) ?> (<?= e($item['variant_label']) ?>)</span>
        <?php if ($label): ?><span class="meta">First Edition <?= e($label) ?></span><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>

  <p>We pack every order by hand and ship within 2 business days. When it lands, tag
    <a href="<?= e(config('instagram_url')) ?>" rel="noopener">@seasonal.job</a> with your number.</p>
  <p><a class="link" href="<?= e(url()) ?>">← Back to the shop</a></p>
</main>
<?php render('footer');
