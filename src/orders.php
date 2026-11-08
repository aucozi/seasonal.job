<?php
declare(strict_types=1);

final class OutOfStock extends RuntimeException
{
}

/**
 * Reserves stock and records a pending order. Stock comes back if the Stripe
 * session expires or is cancelled (see order_release).
 */
function order_create_pending(array $lines, array $totals): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        foreach ($lines as $line) {
            $taken = q('UPDATE variants SET stock = stock - ? WHERE id = ? AND stock >= ?',
                [$line['qty'], $line['variant_id'], $line['qty']])->rowCount();
            if ($taken !== 1) {
                throw new OutOfStock($line['name'] . ' (' . $line['label'] . ')');
            }
        }

        $token = bin2hex(random_bytes(16));
        q('INSERT INTO orders (token, status, subtotal_cents, shipping_cents, total_cents, created_at)
           VALUES (?, ?, ?, ?, ?, ?)',
            [$token, 'pending', $totals['subtotal'], $totals['shipping'], $totals['total'], now()]);
        $orderId = (int) $pdo->lastInsertId();

        foreach ($lines as $line) {
            q('INSERT INTO order_items (order_id, product_id, variant_id, product_name, variant_label, unit_cents, qty)
               VALUES (?, ?, ?, ?, ?, ?, ?)', [
                $orderId, $line['product_id'], $line['variant_id'], $line['name'], $line['label'],
                $line['unit_cents'], $line['qty'],
            ]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
    return order_find($orderId);
}

function order_find(int $id): ?array
{
    return q('SELECT * FROM orders WHERE id = ?', [$id])->fetch() ?: null;
}

function order_find_by_token(string $token): ?array
{
    return q('SELECT * FROM orders WHERE token = ?', [$token])->fetch() ?: null;
}

function order_items(int $orderId): array
{
    return q('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId])->fetchAll();
}

function order_attach_session(int $orderId, string $sessionId): void
{
    q('UPDATE orders SET stripe_session_id = ? WHERE id = ?', [$sessionId, $orderId]);
}

/** Finds our order for a Stripe Checkout Session, checking the token we sent so ids can't be forged. */
function order_for_session(array $session): ?array
{
    $order = order_find((int) ($session['metadata']['order_id'] ?? 0));
    if (!$order || !hash_equals($order['token'], (string) ($session['metadata']['order_token'] ?? ''))) {
        return null;
    }
    return $order;
}

/**
 * Marks the order paid, saves the shipping address and hands out edition numbers.
 * Returns the order id the first time, null if it was already handled.
 */
function order_mark_paid(array $session): ?int
{
    $order = order_for_session($session);
    if (!$order) {
        return null;
    }

    $customer = $session['customer_details'] ?? [];
    $shipping = $session['collected_information']['shipping_details'] ?? $session['shipping_details'] ?? [];
    $address = $shipping['address'] ?? [];

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $updated = q("UPDATE orders SET status = 'paid', paid_at = ?, email = ?, name = ?,
                ship_name = ?, ship_line1 = ?, ship_line2 = ?, ship_city = ?, ship_state = ?, ship_postal = ?, ship_country = ?,
                tax_cents = ?, total_cents = ?, stripe_payment_intent = ?
             WHERE id = ? AND status = 'pending'", [
            now(),
            (string) ($customer['email'] ?? ''),
            (string) ($customer['name'] ?? ''),
            (string) ($shipping['name'] ?? $customer['name'] ?? ''),
            (string) ($address['line1'] ?? ''),
            (string) ($address['line2'] ?? ''),
            (string) ($address['city'] ?? ''),
            (string) ($address['state'] ?? ''),
            (string) ($address['postal_code'] ?? ''),
            (string) ($address['country'] ?? ''),
            (int) ($session['total_details']['amount_tax'] ?? 0),
            (int) ($session['amount_total'] ?? $order['total_cents']),
            (string) ($session['payment_intent'] ?? ''),
            $order['id'],
        ])->rowCount();

        if ($updated !== 1) {
            $pdo->rollBack();
            return null;
        }

        foreach (order_items((int) $order['id']) as $item) {
            q('UPDATE products SET next_number = next_number + ? WHERE id = ?', [$item['qty'], $item['product_id']]);
            $next = (int) q('SELECT next_number FROM products WHERE id = ?', [$item['product_id']])->fetchColumn();
            $numbers = range($next - (int) $item['qty'], $next - 1);
            q('UPDATE order_items SET edition_numbers = ? WHERE id = ?', [implode(',', $numbers), $item['id']]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
    return (int) $order['id'];
}

/** Puts reserved stock back for an order that was never paid. Safe to call more than once. */
function order_release(int $orderId, string $status = 'expired'): bool
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $updated = q("UPDATE orders SET status = ? WHERE id = ? AND status = 'pending'", [$status, $orderId])->rowCount();
        if ($updated === 1) {
            foreach (order_items($orderId) as $item) {
                q('UPDATE variants SET stock = stock + ? WHERE id = ?', [$item['qty'], $item['variant_id']]);
            }
        }
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
    return $updated === 1;
}

function order_mark_shipped(int $orderId, string $tracking): bool
{
    return q("UPDATE orders SET status = 'shipped', shipped_at = ?, tracking_number = ?
              WHERE id = ? AND status = 'paid'", [now(), $tracking, $orderId])->rowCount() === 1;
}

/** "Tee No. 7, 8 / 33" style label for one order item. */
function edition_label(array $item, ?int $editionSize = null): string
{
    if ($item['edition_numbers'] === '') {
        return '';
    }
    $numbers = str_replace(',', ', ', $item['edition_numbers']);
    return 'No. ' . $numbers . ($editionSize ? ' / ' . $editionSize : '');
}

function edition_sizes(): array
{
    return array_column(q('SELECT id, edition_size FROM products')->fetchAll(), 'edition_size', 'id');
}
