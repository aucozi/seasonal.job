<?php
declare(strict_types=1);

/** Minimal Stripe API client: form-encoded requests over curl, no SDK needed on shared hosting. */
function stripe_request(string $method, string $path, array $params = []): array
{
    $ch = curl_init('https://api.stripe.com/v1/' . ltrim($path, '/'));
    $body = http_build_query(stripe_flatten_bools($params), '', '&');
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_USERPWD => config('stripe.secret_key') . ':',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        throw new RuntimeException('Could not reach Stripe: ' . $curlError);
    }
    $data = json_decode((string) $raw, true);
    if ($status >= 400 || !is_array($data)) {
        $message = is_array($data) ? ($data['error']['message'] ?? 'unknown error') : 'invalid response';
        throw new RuntimeException("Stripe error ($status): $message");
    }
    return $data;
}

function stripe_flatten_bools(array $params): array
{
    foreach ($params as $key => $value) {
        if (is_bool($value)) {
            $params[$key] = $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $params[$key] = stripe_flatten_bools($value);
        }
    }
    return $params;
}

function stripe_create_checkout(array $order, array $lines, array $totals): array
{
    $lineItems = [];
    foreach ($lines as $line) {
        $lineItems[] = [
            'quantity' => $line['qty'],
            'price_data' => [
                'currency' => 'usd',
                'unit_amount' => $line['unit_cents'],
                'product_data' => ['name' => $line['name'] . ' — First Edition (' . $line['label'] . ')'],
            ],
        ];
    }

    $params = [
        'mode' => 'payment',
        'line_items' => $lineItems,
        'client_reference_id' => (string) $order['id'],
        'metadata' => ['order_id' => (string) $order['id'], 'order_token' => $order['token']],
        'payment_intent_data' => ['metadata' => ['order_id' => (string) $order['id']]],
        // Stripe's minimum is 30 minutes; reserved stock is released when the session expires.
        'expires_at' => time() + 31 * 60,
        'success_url' => url('success.php?o=' . $order['token']),
        'cancel_url' => url('cart.php?cancel=' . $order['token']),
        'shipping_address_collection' => ['allowed_countries' => config('shipping.countries', ['US'])],
        'shipping_options' => [[
            'shipping_rate_data' => [
                'type' => 'fixed_amount',
                'display_name' => $totals['shipping'] === 0 ? 'Free US shipping (USPS)' : 'US shipping (USPS)',
                'fixed_amount' => ['amount' => $totals['shipping'], 'currency' => 'usd'],
                'delivery_estimate' => [
                    'minimum' => ['unit' => 'business_day', 'value' => 3],
                    'maximum' => ['unit' => 'business_day', 'value' => 7],
                ],
            ],
        ]],
    ];
    if (config('stripe.automatic_tax', false)) {
        $params['automatic_tax'] = ['enabled' => true];
    }
    return stripe_request('POST', 'checkout/sessions', $params);
}

function stripe_expire_session(string $sessionId): void
{
    stripe_request('POST', 'checkout/sessions/' . rawurlencode($sessionId) . '/expire');
}

/** Checks the Stripe-Signature header and returns the decoded event. */
function stripe_verify_webhook(string $payload, string $header, string $secret, int $tolerance = 300): array
{
    $timestamp = null;
    $signatures = [];
    foreach (explode(',', $header) as $part) {
        [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($key === 't') {
            $timestamp = (int) $value;
        } elseif ($key === 'v1') {
            $signatures[] = $value;
        }
    }
    if ($timestamp === null || !$signatures || $secret === '') {
        throw new RuntimeException('Missing signature');
    }
    if (abs(time() - $timestamp) > $tolerance) {
        throw new RuntimeException('Signature too old');
    }
    $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    foreach ($signatures as $signature) {
        if (hash_equals($expected, $signature)) {
            $event = json_decode($payload, true);
            if (!is_array($event)) {
                throw new RuntimeException('Invalid payload');
            }
            return $event;
        }
    }
    throw new RuntimeException('Bad signature');
}
