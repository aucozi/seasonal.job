<?php
declare(strict_types=1);

function send_mail(string $to, string $subject, string $body): bool
{
    if ($to === '') {
        return false;
    }
    if (config('mail.log_only', false)) {
        $entry = "To: $to\nSubject: $subject\n\n$body\n" . str_repeat('-', 60) . "\n";
        return file_put_contents(APP_ROOT . '/storage/mail.log', $entry, FILE_APPEND) !== false;
    }
    $from = (string) config('mail.from');
    $fromName = (string) config('mail.from_name', 'seasonal.job');
    $headers = [
        'From' => mb_encode_mimeheader($fromName) . " <$from>",
        'Reply-To' => (string) config('mail.reply_to', $from),
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
    ];
    return mail($to, mb_encode_mimeheader($subject), $body, $headers, '-f' . $from);
}

function order_summary_text(array $order, array $items): string
{
    $sizes = edition_sizes();
    $lines = [];
    foreach ($items as $item) {
        $label = edition_label($item, (int) ($sizes[$item['product_id']] ?? 0));
        $lines[] = sprintf('%d × %s (%s)  %s%s', $item['qty'], $item['product_name'], $item['variant_label'],
            money((int) $item['unit_cents'] * (int) $item['qty']), $label ? "  — $label" : '');
    }
    $lines[] = '';
    $lines[] = 'Shipping: ' . ($order['shipping_cents'] ? money((int) $order['shipping_cents']) : 'free');
    if ((int) $order['tax_cents'] > 0) {
        $lines[] = 'Tax: ' . money((int) $order['tax_cents']);
    }
    $lines[] = 'Total: ' . money((int) $order['total_cents']);
    return implode("\n", $lines);
}

function shipping_address_text(array $order): string
{
    return implode("\n", array_filter([
        $order['ship_name'],
        $order['ship_line1'],
        $order['ship_line2'],
        trim($order['ship_city'] . ', ' . $order['ship_state'] . ' ' . $order['ship_postal'], ', '),
        $order['ship_country'],
    ]));
}

function mail_order_paid(int $orderId): void
{
    $order = order_find($orderId);
    $items = order_items($orderId);
    $summary = order_summary_text($order, $items);
    $address = shipping_address_text($order);

    send_mail((string) config('mail.admin'), "New order #{$order['id']} — pack and ship",
        "Order #{$order['id']} is paid.\n\n$summary\n\nShip to:\n$address\n\nEmail: {$order['email']}\n\n"
        . "Write the edition numbers above on the cards. Mark it shipped with the tracking number here:\n"
        . url('admin/?view=order&id=' . $order['id']) . "\n");

    send_mail($order['email'], "Your seasonal.job order #{$order['id']}",
        "Thanks for picking up a piece of the First Edition.\n\n$summary\n\nShipping to:\n$address\n\n"
        . "We pack every order by hand and ship within 2 business days. You'll get the tracking number by email.\n\n"
        . "— seasonal.job\n" . config('instagram_url') . "\n");
}

function mail_order_shipped(int $orderId): void
{
    $order = order_find($orderId);
    $tracking = $order['tracking_number'];
    send_mail($order['email'], "Your seasonal.job order #{$order['id']} has shipped",
        "Your First Edition order is on its way.\n\nUSPS tracking: $tracking\n"
        . 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . rawurlencode($tracking) . "\n\n"
        . "When it arrives, tag @seasonal.job with your number.\n\n— seasonal.job\n");
}
