<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

csrf_check();

$lines = cart_lines();
if (!$lines) {
    redirect(url('cart.php'), 303);
}
$totals = cart_totals($lines);

try {
    $order = order_create_pending($lines, $totals);
} catch (OutOfStock $soldOut) {
    flash('Sorry, ' . $soldOut->getMessage() . ' just sold out. Your cart has been updated.');
    redirect(url('cart.php'), 303);
}

try {
    $session = stripe_create_checkout($order, $lines, $totals);
} catch (Throwable $error) {
    order_release((int) $order['id'], 'canceled');
    error_log('Checkout failed: ' . $error->getMessage());
    flash('Checkout is unavailable right now. Please try again in a minute.');
    redirect(url('cart.php'), 303);
}

order_attach_session((int) $order['id'], (string) $session['id']);
redirect((string) $session['url'], 303);
