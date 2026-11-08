<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

start_session();
header('X-Robots-Tag: noindex');

$adminUrl = url('admin/');
$action = (string) ($_POST['action'] ?? '');

if ($action === 'login') {
    csrf_check();
    $password = (string) config('admin_password', '');
    if (strlen($password) >= 12 && hash_equals($password, (string) ($_POST['password'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
    } else {
        sleep(2);
        flash('Wrong password.');
    }
    redirect($adminUrl, 303);
}

if (empty($_SESSION['admin'])) {
    admin_start('Log in');
    ?>
    <form method="post" class="admin-login">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="login">
      <label for="password">Admin password</label>
      <input id="password" type="password" name="password" required autofocus>
      <button class="btn" type="submit">Log in</button>
      <?php if (strlen((string) config('admin_password', '')) < 12): ?>
        <p class="meta">Set an admin_password of at least 12 characters in config.php first.</p>
      <?php endif; ?>
    </form>
    <?php
    admin_end();
    exit;
}

if ($action !== '') {
    csrf_check();
    switch ($action) {
        case 'logout':
            $_SESSION = [];
            session_destroy();
            redirect($adminUrl, 303);
        case 'install':
            if (!db_installed()) {
                db_install();
                flash('Database installed with the First Edition stock.');
            }
            redirect($adminUrl, 303);
        case 'ship':
            $orderId = (int) ($_POST['order_id'] ?? 0);
            $tracking = preg_replace('/\s+/', '', (string) ($_POST['tracking'] ?? ''));
            if ($tracking === '') {
                flash('Add the tracking number first.');
            } elseif (order_mark_shipped($orderId, $tracking)) {
                mail_order_shipped($orderId);
                flash("Order #$orderId marked shipped. The customer got the tracking email.");
            }
            redirect($adminUrl . '?view=order&id=' . $orderId, 303);
        case 'stock':
            foreach ((array) ($_POST['stock'] ?? []) as $variantId => $stock) {
                q('UPDATE variants SET stock = ? WHERE id = ?', [max(0, (int) $stock), (int) $variantId]);
            }
            flash('Stock saved.');
            redirect($adminUrl . '?view=stock', 303);
    }
}

if (!db_installed()) {
    admin_start('Set up');
    ?>
    <p>The database is empty. Installing creates the tables and loads the First Edition stock
      (33 tees, 9 trucker hats, 22 caps).</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="install">
      <button class="btn" type="submit">Install database</button></form>
    <?php
    admin_end();
    exit;
}

$view = (string) ($_GET['view'] ?? 'orders');

if (($_GET['export'] ?? '') === 'shipping') {
    // Paid, unshipped orders in a layout Pirate Ship's spreadsheet import understands.
    $orders = q("SELECT * FROM orders WHERE status = 'paid' ORDER BY id")->fetchAll();
    csv_download('to-ship-' . date('Y-m-d') . '.csv',
        ['Name', 'Address', 'Address Line 2', 'City', 'State', 'Zipcode', 'Country', 'Email', 'Order ID', 'Items'],
        array_map(fn ($o) => [$o['ship_name'], $o['ship_line1'], $o['ship_line2'], $o['ship_city'], $o['ship_state'],
            $o['ship_postal'], $o['ship_country'], $o['email'], $o['id'], items_line((int) $o['id'])], $orders));
}
if (($_GET['export'] ?? '') === 'signups') {
    $rows = q('SELECT email, created_at FROM signups ORDER BY id')->fetchAll();
    csv_download('drop-list-' . date('Y-m-d') . '.csv', ['Email', 'Joined'], array_map('array_values', $rows));
}

admin_start(match ($view) { 'stock' => 'Stock', 'signups' => 'Drop list', 'order' => 'Order', default => 'Orders' });

if ($view === 'order') {
    $order = order_find((int) ($_GET['id'] ?? 0));
    if (!$order) {
        echo '<p>Order not found.</p>';
    } else {
        $sizes = edition_sizes();
        ?>
        <p><a href="<?= e($adminUrl) ?>">← All orders</a></p>
        <h2>Order #<?= (int) $order['id'] ?> <span class="status status-<?= e($order['status']) ?>"><?= e($order['status']) ?></span></h2>
        <table class="admin-table">
          <thead><tr><th>Item</th><th>Qty</th><th>Card number(s)</th></tr></thead>
          <tbody>
          <?php foreach (order_items((int) $order['id']) as $item): ?>
            <tr><td><?= e($item['product_name']) ?> (<?= e($item['variant_label']) ?>)</td><td><?= (int) $item['qty'] ?></td>
                <td><strong><?= e(edition_label($item, (int) ($sizes[$item['product_id']] ?? 0))) ?: '—' ?></strong></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <div class="admin-columns">
          <div><h3>Ship to</h3><pre class="address"><?= e(shipping_address_text($order)) ?></pre>
            <p><?= e($order['email']) ?></p></div>
          <div><h3>Payment</h3>
            <p>Total <?= money((int) $order['total_cents']) ?> (shipping <?= money((int) $order['shipping_cents']) ?>)</p>
            <p class="meta">Placed <?= e($order['created_at']) ?><?= $order['paid_at'] ? ' · paid ' . e($order['paid_at']) : '' ?></p>
            <?php if ($order['stripe_payment_intent']): ?>
              <p><a href="https://dashboard.stripe.com/payments/<?= e($order['stripe_payment_intent']) ?>" rel="noopener">Open in Stripe</a></p>
            <?php endif; ?></div>
        </div>
        <?php if ($order['status'] === 'paid'): ?>
          <form method="post" class="ship-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="ship"><input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
            <label for="tracking">USPS tracking number</label>
            <input id="tracking" name="tracking" required>
            <button class="btn" type="submit">Mark shipped and email customer</button>
          </form>
        <?php elseif ($order['status'] === 'shipped'): ?>
          <p>Shipped <?= e($order['shipped_at']) ?> · tracking <?= e($order['tracking_number']) ?></p>
        <?php endif; ?>
        <?php
    }
} elseif ($view === 'stock') {
    ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="stock">
      <table class="admin-table">
        <thead><tr><th>Product</th><th>Size</th><th>In stock</th></tr></thead>
        <tbody>
        <?php foreach (products_all() as $product): foreach ($product['variants'] as $variant): ?>
          <tr><td><?= e($product['name']) ?></td><td><?= e($variant['label']) ?></td>
              <td><input class="qty" type="number" min="0" name="stock[<?= (int) $variant['id'] ?>]" value="<?= (int) $variant['stock'] ?>"></td></tr>
        <?php endforeach; endforeach; ?>
        </tbody>
      </table>
      <p class="meta">Sold at an event or in a DM? Lower the count here so the site never oversells.
        Pieces in an unfinished checkout are already taken out and come back within 30 minutes.</p>
      <button class="btn" type="submit">Save stock</button>
    </form>
    <?php
} elseif ($view === 'signups') {
    $signups = q('SELECT email, created_at FROM signups ORDER BY id DESC')->fetchAll();
    ?>
    <p><?= count($signups) ?> people on the drop list. <a href="?view=signups&amp;export=signups">Download CSV</a></p>
    <table class="admin-table"><tbody>
      <?php foreach ($signups as $signup): ?><tr><td><?= e($signup['email']) ?></td><td class="meta"><?= e($signup['created_at']) ?></td></tr><?php endforeach; ?>
    </tbody></table>
    <?php
} else {
    $orders = q("SELECT * FROM orders WHERE status IN ('paid', 'shipped')
                 ORDER BY CASE status WHEN 'paid' THEN 0 ELSE 1 END, id DESC")->fetchAll();
    $toShip = count(array_filter($orders, fn ($o) => $o['status'] === 'paid'));
    $revenue = array_sum(array_column($orders, 'total_cents'));
    ?>
    <p><strong><?= $toShip ?></strong> to ship · <?= count($orders) ?> orders · <?= money($revenue) ?> taken
      <?php if ($toShip): ?> · <a href="?export=shipping">Download addresses for Pirate Ship</a><?php endif; ?></p>
    <table class="admin-table">
      <thead><tr><th>#</th><th>Status</th><th>Customer</th><th>Items</th><th>Total</th><th>Paid</th></tr></thead>
      <tbody>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td><a href="?view=order&amp;id=<?= (int) $order['id'] ?>"><?= (int) $order['id'] ?></a></td>
          <td><span class="status status-<?= e($order['status']) ?>"><?= e($order['status']) ?></span></td>
          <td><?= e($order['ship_name'] ?: $order['email']) ?></td>
          <td><?= e(items_line((int) $order['id'])) ?></td>
          <td><?= money((int) $order['total_cents']) ?></td>
          <td class="meta"><?= e($order['paid_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$orders): ?><tr><td colspan="6">No orders yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
    <?php
}
admin_end();

function admin_start(string $title): void
{
    $adminUrl = url('admin/');
    ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex"><title><?= e($title) ?> — seasonal.job admin</title>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>"></head>
<body class="admin">
<header class="site-header"><div class="wrap bar">
  <a class="wordmark" href="<?= e($adminUrl) ?>">seasonal.job <span class="meta">admin</span></a>
  <?php if (!empty($_SESSION['admin'])): ?>
  <nav class="nav">
    <a href="<?= e($adminUrl) ?>">Orders</a><a href="?view=stock">Stock</a><a href="?view=signups">Drop list</a>
    <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button class="link-button" type="submit">Log out</button></form>
  </nav>
  <?php endif; ?>
</div></header>
<main class="wrap">
<?php if ($message = flash()): ?><p class="flash" role="status"><?= e($message) ?></p><?php endif; ?>
<h1><?= e($title) ?></h1>
<?php
}

function admin_end(): void
{
    echo "</main></body></html>\n";
}

function items_line(int $orderId): string
{
    return implode(', ', array_map(fn ($i) => $i['qty'] . '× ' . $i['product_name'] . ' ' . $i['variant_label'], order_items($orderId)));
}

function csv_download(string $filename, array $header, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $header);
    foreach ($rows as $row) {
        // Neutralise spreadsheet formulas in customer-entered text.
        fputcsv($out, array_map(fn ($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : $v, $row));
    }
    exit;
}
