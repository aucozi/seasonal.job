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
        175 => 33, // LITHUSA Tee
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

/* ---------------------------------------------------------------------------------------
 * Drop list: emails from the "Next drop" form, kept in their own table so they survive
 * theme changes. WooCommerce → Drop list shows them and downloads a CSV.
 * ------------------------------------------------------------------------------------- */

function sj_signups_table(): string
{
    global $wpdb;
    return $wpdb->prefix . 'sj_signups';
}

function sj_signups_install(): void
{
    global $wpdb;
    if (get_option('sj_signups_db') === '1') {
        return;
    }
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta('CREATE TABLE ' . sj_signups_table() . " (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        email varchar(190) NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY email (email)
    ) " . $wpdb->get_charset_collate() . ';');
    update_option('sj_signups_db', '1', false);
}

add_shortcode('sj_signup', function (): string {
    $status = sanitize_key($_GET['sj_signup'] ?? '');
    $message = ['ok' => 'You’re on the list. We’ll email you before the next drop.', 'bad' => 'That email address doesn’t look right. Try again?'][$status] ?? '';
    $back = remove_query_arg('sj_signup', home_url(wp_unslash($_SERVER['REQUEST_URI'] ?? '/')));
    return '<h2 id="sj-next-drop">Next drop</h2>'
        . '<p>Edition 02 and new designs are coming, shipping to the US and Europe. Get the link a day early.</p>'
        . ($message ? '<p class="sj-signup-msg" role="status">' . esc_html($message) . '</p>' : '')
        . '<form class="sj-signup" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'
        . '<input type="hidden" name="action" value="sj_signup">'
        . '<input type="hidden" name="back" value="' . esc_attr($back) . '">'
        . '<label class="sj-hp" aria-hidden="true">Leave empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label>'
        . '<label class="screen-reader-text" for="sj-signup-email">Email</label>'
        . '<input id="sj-signup-email" type="email" name="email" required autocomplete="email" placeholder="your@email.com">'
        . '<button class="wp-element-button" type="submit">Notify me</button>'
        . '</form>';
});

function sj_handle_signup(): void
{
    $back = wp_validate_redirect(wp_unslash($_POST['back'] ?? ''), home_url('/'));
    if (!empty($_POST['website'])) { // Filled-in honeypot: a bot.
        wp_safe_redirect($back);
        exit;
    }
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    if (!is_email($email)) {
        wp_safe_redirect(add_query_arg('sj_signup', 'bad', $back) . '#sj-next-drop');
        exit;
    }
    sj_signups_install();
    global $wpdb;
    $wpdb->query($wpdb->prepare(
        'INSERT IGNORE INTO ' . sj_signups_table() . ' (email, created_at) VALUES (%s, %s)',
        strtolower($email),
        current_time('mysql')
    ));
    wp_safe_redirect(add_query_arg('sj_signup', 'ok', $back) . '#sj-next-drop');
    exit;
}
add_action('admin_post_nopriv_sj_signup', 'sj_handle_signup');
add_action('admin_post_sj_signup', 'sj_handle_signup');

add_action('admin_menu', function (): void {
    add_submenu_page('woocommerce', 'Drop list', 'Drop list', 'manage_woocommerce', 'sj-drop-list', function (): void {
        sj_signups_install();
        global $wpdb;
        $rows = $wpdb->get_results('SELECT email, created_at FROM ' . sj_signups_table() . ' ORDER BY id DESC');
        $csv = wp_nonce_url(admin_url('admin-post.php?action=sj_signups_csv'), 'sj_signups_csv');
        echo '<div class="wrap"><h1>Drop list</h1><p>' . count($rows) . ' people want to hear about the next drop. '
            . '<a class="button" href="' . esc_url($csv) . '">Download CSV</a></p>'
            . '<table class="widefat striped"><thead><tr><th>Email</th><th>Joined</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr><td>' . esc_html($row->email) . '</td><td>' . esc_html($row->created_at) . '</td></tr>';
        }
        echo $rows ? '' : '<tr><td colspan="2">No signups yet.</td></tr>';
        echo '</tbody></table></div>';
    });
}, 60);

add_action('admin_post_sj_signups_csv', function (): void {
    if (!current_user_can('manage_woocommerce') || !check_admin_referer('sj_signups_csv')) {
        wp_die('Not allowed');
    }
    sj_signups_install();
    global $wpdb;
    $rows = $wpdb->get_results('SELECT email, created_at FROM ' . sj_signups_table() . ' ORDER BY id', ARRAY_N);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="drop-list-' . gmdate('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Joined']);
    foreach ($rows as $row) {
        fputcsv($out, array_map(fn ($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : $v, $row));
    }
    exit;
});

/* ---------------------------------------------------------------------------------------
 * WooCommerce → Edition numbers: see and set the next number for each First Edition run,
 * e.g. back to 1 after a refunded test order, or past pieces sold by hand at an event.
 * ------------------------------------------------------------------------------------- */

add_action('admin_menu', function (): void {
    add_submenu_page('woocommerce', 'Edition numbers', 'Edition numbers', 'manage_woocommerce', 'sj-editions', 'sj_editions_page');
}, 61);

function sj_editions_page(): void
{
    $sizes = sj_edition_sizes();
    $saved = false;
    if (isset($_POST['sj_next']) && check_admin_referer('sj_editions')) {
        foreach ((array) $_POST['sj_next'] as $productId => $next) {
            $productId = (int) $productId;
            if (isset($sizes[$productId])) {
                add_option('sj_edition_next_' . $productId, '1', '', false);
                update_option('sj_edition_next_' . $productId, (string) max(1, (int) $next), false);
            }
        }
        $saved = true;
    }
    echo '<div class="wrap"><h1>Edition numbers</h1>';
    if ($saved) {
        echo '<div class="notice notice-success"><p>Saved.</p></div>';
    }
    echo '<p>Each paid order gives its First Edition pieces the next numbers automatically. They show on the order as '
        . '"Edition number", and that is what you write on the card. Change a number here only to undo a test order '
        . 'or to skip numbers you handed out yourself.</p>';
    echo '<form method="post">';
    wp_nonce_field('sj_editions');
    echo '<table class="widefat striped" style="max-width:640px"><thead><tr><th>Product</th><th>Made</th><th>Next number</th></tr></thead><tbody>';
    foreach ($sizes as $productId => $size) {
        $product = wc_get_product($productId);
        $next = (int) get_option('sj_edition_next_' . $productId, 1);
        echo '<tr><td>' . esc_html($product ? wp_strip_all_tags($product->get_name()) : '#' . $productId) . '</td><td>' . (int) $size . '</td>'
            . '<td><input type="number" min="1" name="sj_next[' . (int) $productId . ']" value="' . $next . '" style="width:90px"></td></tr>';
    }
    echo '</tbody></table><p><button class="button button-primary" type="submit">Save</button></p></form></div>';
}
