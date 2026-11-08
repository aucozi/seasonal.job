<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

// Stripe calls this URL. Point a webhook endpoint at https://shop.benzilinskas.com/webhook.php with the events:
// checkout.session.completed, checkout.session.async_payment_succeeded,
// checkout.session.async_payment_failed, checkout.session.expired

$payload = (string) file_get_contents('php://input');
try {
    $event = stripe_verify_webhook($payload, (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''),
        (string) config('stripe.webhook_secret'));
} catch (Throwable $error) {
    http_response_code(400);
    exit('Invalid signature');
}

$session = $event['data']['object'] ?? [];

try {
    switch ($event['type'] ?? '') {
        case 'checkout.session.completed':
        case 'checkout.session.async_payment_succeeded':
            if (($session['payment_status'] ?? '') === 'paid') {
                $orderId = order_mark_paid($session);
                if ($orderId !== null) {
                    mail_order_paid($orderId);
                }
            }
            break;

        case 'checkout.session.expired':
        case 'checkout.session.async_payment_failed':
            $order = order_for_session($session);
            if ($order) {
                order_release((int) $order['id'], 'expired');
            }
            break;
    }
} catch (Throwable $error) {
    // A 500 makes Stripe retry later; every handler above is safe to run twice.
    error_log('Webhook ' . ($event['id'] ?? '?') . ' failed: ' . $error->getMessage());
    http_response_code(500);
    exit('Error');
}

http_response_code(200);
echo 'ok';
