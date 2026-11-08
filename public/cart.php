<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

// Back from Stripe without paying: end that checkout so its reserved stock is freed straight away.
if (isset($_GET['cancel'])) {
    $order = order_find_by_token((string) $_GET['cancel']);
    if ($order && $order['status'] === 'pending' && $order['stripe_session_id'] !== '') {
        try {
            stripe_expire_session($order['stripe_session_id']);
            order_release((int) $order['id'], 'canceled');
        } catch (Throwable $error) {
            // Already completed or expired on Stripe's side; the webhook settles it.
            error_log('Cancel checkout: ' . $error->getMessage());
        }
    }
    flash('Checkout canceled. Your cart is still here.');
    redirect(url('cart.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $cart = cart_get();

    if ($action === 'add') {
        $variantId = (int) ($_POST['variant_id'] ?? 0);
        $variant = q('SELECT stock FROM variants WHERE id = ?', [$variantId])->fetch();
        if (!$variant || $variant['stock'] <= 0) {
            flash('Sorry, that one just sold out.');
            redirect($_SERVER['HTTP_REFERER'] ?? url(), 303);
        }
        $wanted = ($cart[$variantId] ?? 0) + max(1, (int) ($_POST['qty'] ?? 1));
        cart_set($variantId, min($wanted, (int) $variant['stock']));
        if ($wanted > (int) $variant['stock']) {
            flash('Only ' . (int) $variant['stock'] . ' left in that size, so your cart has all of them.');
        }
    } elseif ($action === 'update') {
        foreach ((array) ($_POST['qty'] ?? []) as $variantId => $qty) {
            cart_set((int) $variantId, max(0, (int) $qty));
        }
    } elseif ($action === 'remove') {
        cart_set((int) ($_POST['variant_id'] ?? 0), 0);
    }
    redirect(url('cart.php'), 303);
}

$lines = cart_lines();
$totals = cart_totals($lines);
$freeOver = (int) config('shipping.free_over_cents', 6500);

render('header', ['title' => 'Cart']);
?>
<main class="wrap narrow cart">
  <h1>Cart</h1>
  <?php if (!$lines): ?>
    <p>Your cart is empty.</p>
    <p><a class="btn" href="<?= e(url('#shop')) ?>">Shop the First Edition</a></p>
  <?php else: ?>
    <form method="post" action="<?= e(url('cart.php')) ?>" class="cart-lines">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update">
      <?php foreach ($lines as $line): ?>
        <div class="line">
          <img src="<?= e(asset($line['image'])) ?>" alt="">
          <div class="line-info">
            <a href="<?= e(url('product.php?p=' . $line['slug'])) ?>"><?= e($line['name']) ?></a>
            <p class="meta"><?= e($line['label']) ?> · <?= money((int) $line['unit_cents']) ?></p>
          </div>
          <label class="sr-only" for="qty-<?= (int) $line['variant_id'] ?>">Quantity</label>
          <input class="qty" id="qty-<?= (int) $line['variant_id'] ?>" type="number" name="qty[<?= (int) $line['variant_id'] ?>]"
                 value="<?= (int) $line['qty'] ?>" min="0" max="<?= (int) $line['stock'] ?>">
          <p class="line-total"><?= money((int) $line['line_cents']) ?></p>
        </div>
      <?php endforeach; ?>
      <button class="link-button" type="submit">Update quantities</button>
    </form>

    <dl class="totals">
      <dt>Subtotal</dt><dd><?= money($totals['subtotal']) ?></dd>
      <dt>US shipping</dt><dd><?= $totals['shipping'] ? money($totals['shipping']) : 'Free' ?></dd>
      <dt class="grand">Total</dt><dd class="grand"><?= money($totals['total']) ?></dd>
    </dl>
    <?php if ($totals['shipping'] > 0): ?>
      <p class="meta">Add <?= money($freeOver - $totals['subtotal']) ?> more for free shipping.</p>
    <?php endif; ?>

    <form method="post" action="<?= e(url('checkout.php')) ?>">
      <?= csrf_field() ?>
      <button class="btn wide" type="submit">Check out</button>
    </form>
    <p class="meta">Secure payment by Stripe. Ships to US addresses only. Your pieces are held for 30 minutes while you pay.</p>
  <?php endif; ?>
</main>
<?php render('footer');
