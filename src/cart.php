<?php
declare(strict_types=1);

/** The cart lives in the session as [variant_id => qty]. Stock is only reserved at checkout. */
function cart_get(): array
{
    start_session();
    return $_SESSION['cart'] ?? [];
}

function cart_set(int $variantId, int $qty): void
{
    start_session();
    if ($qty <= 0) {
        unset($_SESSION['cart'][$variantId]);
    } else {
        $_SESSION['cart'][$variantId] = $qty;
    }
}

function cart_clear(): void
{
    start_session();
    $_SESSION['cart'] = [];
}

function cart_count(): int
{
    return array_sum(cart_get());
}

/** Cart lines with product details; quantities are clamped to what is in stock right now. */
function cart_lines(): array
{
    $cart = cart_get();
    if (!$cart) {
        return [];
    }
    $ids = array_map('intval', array_keys($cart));
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $rows = q("SELECT v.id AS variant_id, v.label, v.stock, p.id AS product_id, p.slug, p.name, p.price_cents, p.image
               FROM variants v JOIN products p ON p.id = v.product_id
               WHERE v.id IN ($marks) AND p.active = 1
               ORDER BY p.sort, v.sort", $ids)->fetchAll();

    $lines = [];
    foreach ($rows as $row) {
        $qty = min((int) $cart[$row['variant_id']], (int) $row['stock']);
        if ($qty !== (int) $cart[$row['variant_id']]) {
            cart_set((int) $row['variant_id'], $qty);
        }
        if ($qty <= 0) {
            continue;
        }
        $row['qty'] = $qty;
        $row['unit_cents'] = (int) $row['price_cents'];
        $row['line_cents'] = $qty * (int) $row['price_cents'];
        $lines[] = $row;
    }
    return $lines;
}

function shipping_for(int $subtotalCents): int
{
    if ($subtotalCents <= 0) {
        return 0;
    }
    if ($subtotalCents >= (int) config('shipping.free_over_cents', 6500)) {
        return 0;
    }
    return (int) config('shipping.flat_cents', 600);
}

function cart_totals(array $lines): array
{
    $subtotal = array_sum(array_column($lines, 'line_cents'));
    $shipping = shipping_for($subtotal);
    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => $subtotal + $shipping,
        'count' => array_sum(array_column($lines, 'qty')),
    ];
}
