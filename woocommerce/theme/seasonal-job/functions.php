<?php
/**
 * seasonal job: child theme of Twenty Twenty-Four with the benzilinskas.com layout.
 * Templates are thin wrappers; the markup is drawn by the shortcodes below.
 */

defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', function (): void {
    $dir = get_stylesheet_directory();
    wp_enqueue_style('seasonal-job', get_stylesheet_uri(), [], (string) filemtime($dir . '/style.css'));
    wp_enqueue_script('seasonal-job', get_stylesheet_directory_uri() . '/assets/js/shop.js', ['jquery'], (string) filemtime($dir . '/assets/js/shop.js'), true);
}, 20);

/** Edition sizes come from the seasonal job shop mu-plugin; empty if it is missing. */
function sj_theme_edition_sizes(): array
{
    return function_exists('sj_edition_sizes') ? sj_edition_sizes() : [];
}

function sj_money(float $amount): string
{
    return '$' . number_format($amount, floor($amount) == $amount ? 0 : 2);
}

/** Pieces left across all sizes of a product. */
function sj_stock_left(WC_Product $product): int
{
    if ($product->is_type('variable')) {
        $left = 0;
        foreach ($product->get_children() as $childId) {
            $child = wc_get_product($childId);
            if ($child && $child->get_status() === 'publish') {
                $left += max(0, (int) $child->get_stock_quantity());
            }
        }
        return $left;
    }
    return max(0, (int) $product->get_stock_quantity());
}

function sj_page_url(string $slug): string
{
    $page = get_page_by_path($slug);
    return $page ? get_permalink($page) : home_url('/' . $slug . '/');
}

function sj_product_images(WC_Product $product): array
{
    return array_values(array_filter(array_merge([$product->get_image_id()], $product->get_gallery_image_ids())));
}

/** Left column: name, BENZILINSKAS.COM / SHOP and the menu. */
add_shortcode('sj_sidebar', function (): string {
    $count = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
    $current = untrailingslashit(strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?'));
    $items = [
        ['First Edition', home_url('/')],
        ['About', sj_page_url('about')],
        ['Size guide', sj_page_url('size-guide')],
        ['Shipping', sj_page_url('shipping')],
        ['Cart' . ($count ? " ($count)" : ''), function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')],
    ];
    $nav = '';
    foreach ($items as [$label, $href]) {
        $path = untrailingslashit((string) wp_parse_url($href, PHP_URL_PATH));
        $active = $path === $current || ($label === 'First Edition' && (is_front_page() || is_shop() || is_product()));
        $nav .= '<a href="' . esc_url($href) . '"' . ($active ? ' aria-current="page"' : '') . '>' . esc_html($label) . '</a>';
    }
    return '<div class="sj-sidebar">'
        . '<p class="sj-site-name"><a href="' . esc_url(home_url('/')) . '">seasonal job</a></p>'
        . '<p class="sj-site-title"><a href="https://benzilinskas.com">benzilinskas.com</a> <span>/ shop</span></p>'
        . '<nav class="sj-nav" aria-label="Shop">' . $nav . '</nav>'
        . '</div>';
});

/** Drop-list signup (content column) and the social links + copyright (left column). */
add_shortcode('sj_footer', function (): string {
    $signup = shortcode_exists('sj_signup') ? do_shortcode('[sj_signup]') : '';
    $instagram = '<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12,4.622c2.403,0,2.688,0.009,3.637,0.052c0.877,0.04,1.354,0.187,1.671,0.31c0.42,0.163,0.72,0.358,1.035,0.673 c0.315,0.315,0.51,0.615,0.673,1.035c0.123,0.317,0.27,0.794,0.31,1.671c0.043,0.949,0.052,1.234,0.052,3.637 s-0.009,2.688-0.052,3.637c-0.04,0.877-0.187,1.354-0.31,1.671c-0.163,0.42-0.358,0.72-0.673,1.035 c-0.315,0.315-0.615,0.51-1.035,0.673c-0.317,0.123-0.794,0.27-1.671,0.31c-0.949,0.043-1.233,0.052-3.637,0.052 s-2.688-0.009-3.637-0.052c-0.877-0.04-1.354-0.187-1.671-0.31c-0.42-0.163-0.72-0.358-1.035-0.673 c-0.315-0.315-0.51-0.615-0.673-1.035c-0.123-0.317-0.27-0.794-0.31-1.671C4.631,14.688,4.622,14.403,4.622,12 s0.009-2.688,0.052-3.637c0.04-0.877,0.187-1.354,0.31-1.671c0.163-0.42,0.358-0.72,0.673-1.035 c0.315-0.315,0.615-0.51,1.035-0.673c0.317-0.123,0.794-0.27,1.671-0.31C9.312,4.631,9.597,4.622,12,4.622 M12,3 C9.556,3,9.249,3.01,8.289,3.054C7.331,3.098,6.677,3.25,6.105,3.472C5.513,3.702,5.011,4.01,4.511,4.511 c-0.5,0.5-0.808,1.002-1.038,1.594C3.25,6.677,3.098,7.331,3.054,8.289C3.01,9.249,3,9.556,3,12c0,2.444,0.01,2.751,0.054,3.711 c0.044,0.958,0.196,1.612,0.418,2.185c0.23,0.592,0.538,1.094,1.038,1.594c0.5,0.5,1.002,0.808,1.594,1.038 c0.572,0.222,1.227,0.375,2.185,0.418C9.249,20.99,9.556,21,12,21s2.751-0.01,3.711-0.054c0.958-0.044,1.612-0.196,2.185-0.418 c0.592-0.23,1.094-0.538,1.594-1.038c0.5-0.5,0.808-1.002,1.038-1.594c0.222-0.572,0.375-1.227,0.418-2.185 C20.99,14.751,21,14.444,21,12s-0.01-2.751-0.054-3.711c-0.044-0.958-0.196-1.612-0.418-2.185c-0.23-0.592-0.538-1.094-1.038-1.594 c-0.5-0.5-1.002-0.808-1.594-1.038c-0.572-0.222-1.227-0.375-2.185-0.418C14.751,3.01,14.444,3,12,3L12,3z M12,7.378 c-2.552,0-4.622,2.069-4.622,4.622S9.448,16.622,12,16.622s4.622-2.069,4.622-4.622S14.552,7.378,12,7.378z M12,15 c-1.657,0-3-1.343-3-3s1.343-3,3-3s3,1.343,3,3S13.657,15,12,15z M16.804,6.116c-0.596,0-1.08,0.484-1.08,1.08 s0.484,1.08,1.08,1.08c0.596,0,1.08-0.484,1.08-1.08S17.401,6.116,16.804,6.116z"></path></svg>';
    $spotify = '<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12,2C6.477,2,2,6.477,2,12c0,5.523,4.477,10,10,10c5.523,0,10-4.477,10-10C22,6.477,17.523,2,12,2 M16.586,16.424 c-0.18,0.295-0.563,0.387-0.857,0.207c-2.348-1.435-5.304-1.76-8.785-0.964c-0.335,0.077-0.67-0.133-0.746-0.469 c-0.077-0.335,0.132-0.67,0.469-0.746c3.809-0.871,7.077-0.496,9.713,1.115C16.673,15.746,16.766,16.13,16.586,16.424 M17.81,13.7 c-0.226,0.367-0.706,0.482-1.072,0.257c-2.687-1.652-6.785-2.131-9.965-1.166C6.36,12.917,5.925,12.684,5.8,12.273 C5.675,11.86,5.908,11.425,6.32,11.3c3.632-1.102,8.147-0.568,11.234,1.328C17.92,12.854,18.035,13.335,17.81,13.7 M17.915,10.865 c-3.223-1.914-8.54-2.09-11.618-1.156C5.804,9.859,5.281,9.58,5.131,9.086C4.982,8.591,5.26,8.069,5.755,7.919 c3.532-1.072,9.404-0.865,13.115,1.338c0.445,0.264,0.59,0.838,0.327,1.282C18.933,10.983,18.359,11.129,17.915,10.865"></path></svg>';
    $privacy = get_privacy_policy_url() ?: sj_page_url('privacy-policy');
    return '<div class="sj-signup-block">' . $signup . '</div>'
        . '<div class="sj-foot">'
        . '<ul class="sj-social">'
        . '<li><a href="https://instagram.com/seasonal.job" rel="noopener">' . $instagram . '<span>Seasonal Job</span></a></li>'
        . '<li><a href="https://open.spotify.com/artist/2Cdr4xt4kY61ECVbILaAFp" rel="noopener">' . $spotify . '<span>Spotify</span></a></li>'
        . '</ul>'
        . '<p class="sj-foot-links"><a href="' . esc_url(sj_page_url('returns')) . '">Returns</a><a href="' . esc_url($privacy) . '">Privacy</a></p>'
        . '<p class="sj-copyright">Ben Zilinskas / Seasonal Job All rights reserved © ' . esc_html(gmdate('Y')) . '</p>'
        . '</div>';
});

add_shortcode('sj_notices', function (): string {
    return function_exists('wc_print_notices') ? (string) wc_print_notices(true) : '';
});

/** Shop home: heading, intro and the two-column grid. */
add_shortcode('sj_shop_home', function (): string {
    $sizes = sj_theme_edition_sizes();
    $ids = $sizes ? array_keys($sizes) : [];
    $products = wc_get_products($ids ? ['include' => $ids, 'status' => 'publish', 'limit' => -1, 'orderby' => 'include']
        : ['status' => 'publish', 'limit' => -1, 'orderby' => 'menu_order', 'order' => 'ASC']);
    $made = array_sum($sizes);
    $flat = 7;
    $free = 65;

    $cards = '';
    foreach ($products as $product) {
        $left = sj_stock_left($product);
        $size = $sizes[$product->get_id()] ?? 0;
        $images = sj_product_images($product);
        $main = $images ? wp_get_attachment_image($images[0], 'woocommerce_single', false, ['alt' => $product->get_name(), 'loading' => 'lazy']) : '';
        $alt = isset($images[1]) ? wp_get_attachment_image($images[1], 'woocommerce_single', false, ['alt' => '', 'class' => 'alt', 'loading' => 'lazy']) : '';
        $meta = sj_money((float) $product->get_price()) . ' · ' . ($left > 0 ? ($size ? "$left of $size left" : "$left left") : 'sold out');
        $cards .= '<a class="sj-card' . ($left > 0 ? '' : ' is-sold-out') . '" href="' . esc_url($product->get_permalink()) . '">'
            . '<span class="sj-card-media">' . $main . $alt . '</span>'
            . '<span class="sj-card-name">' . esc_html(wp_strip_all_tags($product->get_name())) . '</span>'
            . '<span class="sj-card-meta">' . esc_html($meta) . '</span>'
            . '</a>';
    }

    return (string) wc_print_notices(true)
        . '<h1 class="sj-page-title">First Edition</h1>'
        . '<p class="sj-lede">LITHUSA, the original fair run. ' . ($made ?: '') . ' pieces with their original hang tags, each shipped with a numbered card. When they\'re gone, they\'re gone.</p>'
        . '<p class="sj-small">Ships from Texas · US ' . sj_money($flat) . ', free over ' . sj_money($free) . ' · worldwide from $20</p>'
        . '<div class="sj-grid">' . $cards . '</div>';
});

/** Product page: title, price line, photos with thumbnails, WooCommerce's add-to-cart form, description and facts. */
add_shortcode('sj_product', function (): string {
    $product = wc_get_product(get_queried_object_id());
    if (!$product) {
        return '';
    }
    $GLOBALS['product'] = $product;
    $sizes = sj_theme_edition_sizes();
    $size = $sizes[$product->get_id()] ?? 0;
    $left = sj_stock_left($product);
    $name = wp_strip_all_tags($product->get_name());
    $images = sj_product_images($product);

    $gallery = '';
    if ($images) {
        $gallery .= wp_get_attachment_image($images[0], 'woocommerce_single', false, ['class' => 'sj-gallery-main', 'data-main' => '1', 'alt' => $name]);
        if (count($images) > 1) {
            $gallery .= '<div class="sj-thumbs">';
            foreach ($images as $i => $imageId) {
                $full = wp_get_attachment_image_url($imageId, 'woocommerce_single');
                $gallery .= '<a href="' . esc_url($full) . '" data-thumb' . ($i === 0 ? ' aria-current="true"' : '') . '>'
                    . wp_get_attachment_image($imageId, 'woocommerce_thumbnail', false, ['alt' => $name . ', photo ' . ($i + 1), 'loading' => 'lazy'])
                    . '</a>';
            }
            $gallery .= '</div>';
        }
    }

    ob_start();
    if ($left > 0) {
        woocommerce_template_single_add_to_cart();
    }
    $form = (string) ob_get_clean();

    $subtitle = $size ? "First Edition · $size made · $left left" : "$left left";
    $sizeGuide = $product->is_type('variable')
        ? '<p class="sj-small sj-size-guide">Size · <a href="' . esc_url(sj_page_url('size-guide')) . '">size guide</a></p>'
        : '<p class="sj-small sj-size-guide">One size</p>';
    $buy = $left > 0
        ? '<div class="sj-buy">' . $sizeGuide . $form . '</div>'
        : '<p class="sj-notice">All ' . ($size ?: '') . ' are gone. Join the list below to hear about Edition 02.</p>';

    $facts = '<ul class="sj-facts sj-small">'
        . '<li>Original hang tag from the fair run</li>'
        . ($size ? '<li>Numbered card out of ' . $size . '</li>' : '')
        . '<li>Ships from Texas within 2 business days</li>'
        . '<li>US shipping $7, free over $65 · worldwide from $20</li>'
        . '</ul>';

    return (string) wc_print_notices(true)
        . '<p class="sj-small sj-back"><a href="' . esc_url(home_url('/')) . '">← First Edition</a></p>'
        . '<h1 class="sj-page-title">' . esc_html($name) . '</h1>'
        . '<p class="sj-price-line">' . esc_html(sj_money((float) $product->get_price())) . ' <span>· ' . esc_html($subtitle) . '</span></p>'
        . '<div class="sj-gallery" data-gallery>' . $gallery . '</div>'
        . $buy
        . '<div class="sj-description">' . apply_filters('the_content', $product->get_description()) . '</div>'
        . $facts;
});
