<?php
declare(strict_types=1);
// Run: php tests/run.php — uses a throwaway SQLite database, no Stripe account needed.

$tmp = sys_get_temp_dir() . '/sjshop-test-' . getmypid();
mkdir($tmp);
$configFile = "$tmp/config.php";
$port = 8765;
file_put_contents($configFile, '<?php return ' . var_export([
    'site_url' => "http://127.0.0.1:$port",
    'instagram_url' => 'https://www.instagram.com/seasonal.job/',
    'db' => ['dsn' => "sqlite:$tmp/shop.sqlite", 'user' => null, 'pass' => null],
    'stripe' => ['secret_key' => 'sk_test_invalid', 'webhook_secret' => 'whsec_test'],
    'shipping' => ['countries' => ['US'], 'flat_cents' => 600, 'free_over_cents' => 6500],
    'mail' => ['from' => 'shop@example.com', 'admin' => 'owner@example.com', 'log_only' => true],
    'admin_password' => 'correct horse battery',
], true) . ';');
putenv("SHOP_CONFIG=$configFile");
require dirname(__DIR__) . '/src/bootstrap.php';
@unlink(APP_ROOT . '/storage/mail.log');

$failures = 0;
function check(bool $ok, string $what): void
{
    global $failures;
    echo ($ok ? '  ok   ' : '  FAIL ') . $what . "\n";
    $failures += $ok ? 0 : 1;
}
function stock(string $slug, string $label): int
{
    return (int) q('SELECT v.stock FROM variants v JOIN products p ON p.id = v.product_id WHERE p.slug = ? AND v.label = ?',
        [$slug, $label])->fetchColumn();
}
function line(string $slug, string $label, int $qty): array
{
    $row = q('SELECT v.id AS variant_id, v.label, v.stock, p.id AS product_id, p.slug, p.name, p.price_cents, p.image
              FROM variants v JOIN products p ON p.id = v.product_id WHERE p.slug = ? AND v.label = ?', [$slug, $label])->fetch();
    return $row + ['qty' => $qty, 'unit_cents' => (int) $row['price_cents'], 'line_cents' => $qty * (int) $row['price_cents']];
}
function fake_session(array $order): array
{
    return [
        'id' => 'cs_test_' . $order['id'], 'payment_status' => 'paid', 'amount_total' => $order['total_cents'],
        'payment_intent' => 'pi_test_' . $order['id'],
        'metadata' => ['order_id' => (string) $order['id'], 'order_token' => $order['token']],
        'customer_details' => ['email' => 'buyer@example.com', 'name' => 'Ona Buyer'],
        'collected_information' => ['shipping_details' => ['name' => 'Ona Buyer', 'address' => [
            'line1' => '1 Main St', 'line2' => '', 'city' => 'Fort Worth', 'state' => 'TX', 'postal_code' => '76135', 'country' => 'US']]],
        'total_details' => ['amount_tax' => 0],
    ];
}
function signed_post(string $url, array $event, string $secret = 'whsec_test', ?int $time = null): int
{
    $payload = json_encode($event);
    $time ??= time();
    $sig = 't=' . $time . ',v1=' . hash_hmac('sha256', $time . '.' . $payload, $secret);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Stripe-Signature: ' . $sig]]);
    curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $status;
}

echo "Install\n";
db_install();
check(db_installed(), 'tables created');
check(count(products_all()) === 3, 'three First Edition products');
check(stock('lithusa-tee', 'M') === 10 && stock('lithusa-tee', '2XL') === 3, 'tee stock by size');
check(stock('lithusa-trucker', 'One size') === 9 && stock('lithusa-cap', 'One size') === 22, 'hat stock');

echo "Shipping\n";
check(shipping_for(4000) === 600, '$6 on one tee');
check(shipping_for(7000) === 0, 'free on tee + hat');

echo "Reserve and pay\n";
$lines = [line('lithusa-tee', 'M', 2), line('lithusa-trucker', 'One size', 1)];
$order = order_create_pending($lines, cart_totals($lines));
check($order['status'] === 'pending' && (int) $order['total_cents'] === 11200, 'pending order, $112 with free shipping');
check(stock('lithusa-tee', 'M') === 8 && stock('lithusa-trucker', 'One size') === 8, 'stock reserved');

try {
    order_create_pending([line('lithusa-tee', 'S', 5)], ['subtotal' => 0, 'shipping' => 0, 'total' => 0]);
    check(false, 'overselling is refused');
} catch (OutOfStock) {
    check(stock('lithusa-tee', 'S') === 4, 'overselling is refused and stock untouched');
}

check(order_mark_paid(fake_session($order)) === (int) $order['id'], 'marked paid');
check(order_mark_paid(fake_session($order)) === null, 'second webhook delivery is ignored');
$items = order_items((int) $order['id']);
check($items[0]['edition_numbers'] === '1,2' && $items[1]['edition_numbers'] === '1', 'tees No. 1–2, trucker No. 1');
check(order_find((int) $order['id'])['ship_city'] === 'Fort Worth', 'shipping address saved');

$forged = fake_session($order);
$forged['metadata']['order_token'] = 'wrong';
check(order_for_session($forged) === null, 'session with a wrong token is rejected');

$second = order_create_pending([line('lithusa-tee', 'L', 1)], cart_totals([line('lithusa-tee', 'L', 1)]));
order_mark_paid(fake_session($second));
check(order_items((int) $second['id'])[0]['edition_numbers'] === '3', 'next tee is No. 3');

echo "Release\n";
$abandoned = order_create_pending([line('lithusa-cap', 'One size', 2)], cart_totals([line('lithusa-cap', 'One size', 2)]));
check(stock('lithusa-cap', 'One size') === 20, 'caps reserved');
check(order_release((int) $abandoned['id']) && stock('lithusa-cap', 'One size') === 22, 'expired checkout returns stock');
check(!order_release((int) $abandoned['id']) && stock('lithusa-cap', 'One size') === 22, 'releasing twice changes nothing');
check(order_release((int) $order['id']) === false, 'a paid order is never released');

echo "Webhook signatures\n";
$payload = '{"id":"evt_1"}';
$t = time();
$good = 't=' . $t . ',v1=' . hash_hmac('sha256', "$t.$payload", 'whsec_test');
check(stripe_verify_webhook($payload, $good, 'whsec_test')['id'] === 'evt_1', 'valid signature accepted');
foreach (['bad secret' => [$good, 'whsec_other'], 'tampered' => [$good, 'whsec_test', '{"id":"evt_2"}'],
          'stale' => ['t=' . ($t - 600) . ',v1=' . hash_hmac('sha256', ($t - 600) . ".$payload", 'whsec_test'), 'whsec_test']] as $name => $case) {
    try {
        stripe_verify_webhook($case[2] ?? $payload, $case[0], $case[1]);
        check(false, "$name signature rejected");
    } catch (RuntimeException) {
        check(true, "$name signature rejected");
    }
}

echo "Over HTTP\n";
$server = proc_open([PHP_BINARY, '-S', "127.0.0.1:$port", '-t', APP_ROOT . '/public'], [1 => ['file', '/dev/null', 'w'], 2 => ['file', "$tmp/server.log", 'w']], $pipes,
    null, ['SHOP_CONFIG' => $configFile]);
usleep(400000);
$jar = "$tmp/cookies.txt";
$http = function (string $path, ?array $post = null) use ($port, $jar): array {
    $ch = curl_init("http://127.0.0.1:$port/$path");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_FOLLOWLOCATION => true]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $body = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$status, $body];
};

[$status, $home] = $http('');
check($status === 200 && str_contains($home, '30 of 33 left'), 'home page shows real tee count (30 of 33 left)');
[$status, $page] = $http('product.php?p=lithusa-tee');
preg_match('/name="csrf" value="([a-f0-9]+)"/', $page, $m);
$csrf = $m[1] ?? '';
check($status === 200 && $csrf !== '', 'product page renders with a form');
check($http('product.php?p=nope')[0] === 404, 'unknown product is a 404');

$xlId = (int) line('lithusa-tee', 'XL', 1)['variant_id'];
[, $cart] = $http('cart.php', ['csrf' => $csrf, 'action' => 'add', 'variant_id' => $xlId, 'qty' => 9]);
check(str_contains($cart, 'Only 4 left in that size') && str_contains($cart, 'value="4"'), 'cart caps quantity at stock');
[, $after] = $http('checkout.php', ['csrf' => $csrf]);
check(str_contains($after, 'Checkout is unavailable'), 'a Stripe failure shows a friendly message');
check(stock('lithusa-tee', 'XL') === 4, 'and puts the reserved stock back');
check($http('checkout.php', ['csrf' => 'forged'])[0] === 400, 'checkout without a valid CSRF token is refused');

$pending = order_create_pending([line('lithusa-tee', 'S', 1)], cart_totals([line('lithusa-tee', 'S', 1)]));
$base = "http://127.0.0.1:$port/webhook.php";
check(signed_post($base, ['id' => 'evt_a', 'type' => 'checkout.session.completed', 'data' => ['object' => fake_session($pending)]], 'whsec_wrong') === 400, 'webhook with a bad signature gets 400');
check(order_find((int) $pending['id'])['status'] === 'pending', 'and changes nothing');
check(signed_post($base, ['id' => 'evt_b', 'type' => 'checkout.session.completed', 'data' => ['object' => fake_session($pending)]]) === 200, 'signed webhook accepted');
check(order_find((int) $pending['id'])['status'] === 'paid', 'order paid through the webhook');
$mail = (string) @file_get_contents(APP_ROOT . '/storage/mail.log');
check(str_contains($mail, 'To: owner@example.com') && str_contains($mail, 'No. 4 / 33'), 'owner email names the card number');
check(str_contains($mail, 'To: buyer@example.com'), 'customer gets a confirmation');

$expiring = order_create_pending([line('lithusa-cap', 'One size', 1)], cart_totals([line('lithusa-cap', 'One size', 1)]));
signed_post($base, ['id' => 'evt_c', 'type' => 'checkout.session.expired', 'data' => ['object' => fake_session($expiring) + ['payment_status' => 'unpaid']]]);
check(order_find((int) $expiring['id'])['status'] === 'expired' && stock('lithusa-cap', 'One size') === 22, 'expired webhook frees the cap');

[, $thanks] = $http('success.php?o=' . $pending['token']);
check(str_contains($thanks, 'No. 4 / 33'), 'thank-you page shows the edition number');

[, $signup] = $http('signup.php', ['csrf' => $csrf, 'email' => 'Fan@Example.com', 'back' => '/']);
check(str_contains($signup, 'on the list') && q('SELECT COUNT(*) FROM signups')->fetchColumn() == 1, 'drop list signup saved');
$http('signup.php', ['csrf' => $csrf, 'email' => 'fan@example.com', 'back' => '//evil.example']);
check(q('SELECT COUNT(*) FROM signups')->fetchColumn() == 1, 'duplicate signup ignored');

[, $admin] = $http('admin/');
preg_match('/name="csrf" value="([a-f0-9]+)"/', $admin, $m);
[, $wrong] = $http('admin/', ['csrf' => $m[1], 'action' => 'login', 'password' => 'nope']);
check(str_contains($wrong, 'Wrong password'), 'admin rejects a wrong password');
[, $orders] = $http('admin/', ['csrf' => $m[1], 'action' => 'login', 'password' => 'correct horse battery']);
check(str_contains($orders, 'to ship') && str_contains($orders, 'Ona Buyer'), 'admin lists paid orders');
[, $shipped] = $http('admin/', ['csrf' => $m[1], 'action' => 'ship', 'order_id' => $pending['id'], 'tracking' => '9400 1000 0000 0000 0000 00']);
check(str_contains($shipped, 'marked shipped') && order_find((int) $pending['id'])['tracking_number'] === '9400100000000000000000', 'mark shipped saves tracking');
check(str_contains((string) file_get_contents(APP_ROOT . '/storage/mail.log'), 'has shipped'), 'customer gets the tracking email');

proc_terminate($server);
@unlink(APP_ROOT . '/storage/mail.log');
array_map('unlink', glob("$tmp/*"));
rmdir($tmp);

echo $failures ? "\n$failures FAILED\n" : "\nAll checks passed\n";
exit($failures ? 1 : 0);
