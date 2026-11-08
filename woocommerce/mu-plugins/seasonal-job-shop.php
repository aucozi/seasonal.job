<?php
/**
 * Plugin Name: seasonal job shop
 * Description: First Edition numbering for LITHUSA orders, and hides paid shipping when free shipping applies.
 * Version: 1.0.0
 * Author: seasonal job
 *
 * Install: copy this file to wp-content/mu-plugins/ (must-use plugins load automatically).
 */

defined('ABSPATH') || exit;

/**
 * First Edition runs: product ID => how many exist. Numbers start at 1 for each product
 * and are handed out in the order payments arrive.
 */
function sj_edition_sizes(): array
{
    return apply_filters('sj_edition_sizes', [
        175 => 33, // "LITHUSA" T-Shirt
        334 => 9,  // LITHUSA Trucker Hat
        335 => 22, // LITHUSA Cap
    ]);
}

/** Takes the next $count numbers for a product, safely even if two orders are paid at once. */
function sj_take_edition_numbers(int $productId, int $count): array
{
    global $wpdb;
    $option = 'sj_edition_next_' . $productId;
    add_option($option, '1', '', false);
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->options} SET option_value = option_value + %d WHERE option_name = %s",
        $count,
        $option
    ));
    wp_cache_delete($option, 'options');
    $next = (int) $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $option));
    return range($next - $count, $next - 1);
}

/**
 * Gives each First Edition piece in a paid order its number, saved on the order line as
 * "Edition number: No. 7 / 33". It shows in the order screen, the Woo app and the order emails.
 */
function sj_assign_edition_numbers($orderId): void
{
    $order = wc_get_order($orderId);
    if (!$order || $order->get_meta('_sj_editions_assigned')) {
        return;
    }
    $sizes = sj_edition_sizes();
    foreach ($order->get_items() as $item) {
        $productId = (int) $item->get_product_id();
        if (!isset($sizes[$productId]) || $item->get_meta('Edition number')) {
            continue;
        }
        $numbers = sj_take_edition_numbers($productId, (int) $item->get_quantity());
        $item->add_meta_data('Edition number', 'No. ' . implode(', ', $numbers) . ' / ' . $sizes[$productId], true);
        $item->save();
    }
    $order->update_meta_data('_sj_editions_assigned', current_time('mysql'));
    $order->save();
}
// Runs as the order becomes paid, before WooCommerce sends the order emails.
add_action('woocommerce_order_status_processing', 'sj_assign_edition_numbers', 5);
add_action('woocommerce_order_status_completed', 'sj_assign_edition_numbers', 5);

/** When an order qualifies for free shipping, don't also offer the paid rate. */
add_filter('woocommerce_package_rates', function (array $rates): array {
    $free = array_filter($rates, fn ($rate) => $rate->get_method_id() === 'free_shipping');
    return $free ?: $rates;
}, 100);
