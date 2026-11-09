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
        $cartAttr = str_starts_with($label, 'Cart') ? ' data-sj-cart-link' : '';
        $nav .= '<a href="' . esc_url($href) . '"' . $cartAttr . ($active ? ' aria-current="page"' : '') . '>' . esc_html($label) . '</a>';
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
        . '<p class="sj-foot-links"><a href="' . esc_url(sj_page_url('returns')) . '">Returns</a><a href="' . esc_url(sj_page_url('terms')) . '">Terms</a><a href="' . esc_url($privacy) . '">Privacy</a></p>'
        . '<p class="sj-copyright">Ben Zilinskas / Seasonal Job All rights reserved © ' . esc_html(gmdate('Y')) . '</p>'
        . '</div>';
});

/** Footer for checkout and order confirmation: links and copyright, no signup form. */
add_shortcode('sj_footer_min', function (): string {
    $full = do_shortcode('[sj_footer]');
    $start = strpos($full, '<div class="sj-foot">');
    return $start === false ? $full : substr($full, $start);
});

/** Order confirmation: what happens next, in the shop's words. */
add_shortcode('sj_order_next_steps', function (): string {
    return '<div class="sj-next-steps"><h2>What happens next</h2><ol>'
        . '<li>We pack your order by hand in Fort Worth within 2 business days.</li>'
        . '<li>Each First Edition piece gets its number, written and signed on the hang tag.</li>'
        . '<li>You get the tracking number by email when it ships.</li>'
        . '</ol><p class="sj-small">Questions? Reply to your order email or send a DM to <a href="https://instagram.com/seasonal.job">@seasonal.job</a>.</p>'
        . '<p><a href="' . esc_url(home_url('/')) . '">← Back to the shop</a></p></div>';
});

add_shortcode('sj_notices', function (): string {
    return function_exists('wc_print_notices') ? (string) wc_print_notices(true) : '';
});

/** Shop home: heading, intro and the two-column grid. */
/** The First Edition product cards (photo, name, price and how many are left), as on the home page. */
function sj_product_cards(): string
{
    $sizes = sj_theme_edition_sizes();
    $ids = $sizes ? array_keys($sizes) : [];
    $products = wc_get_products($ids ? ['include' => $ids, 'status' => 'publish', 'limit' => -1, 'orderby' => 'include']
        : ['status' => 'publish', 'limit' => -1, 'orderby' => 'menu_order', 'order' => 'ASC']);
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
    return '<div class="sj-grid">' . $cards . '</div>';
}

add_shortcode('sj_shop_home', function (): string {
    $made = array_sum(sj_theme_edition_sizes());
    $flat = 7;
    $free = 65;

    return (string) wc_print_notices(true)
        . '<h1 class="sj-page-title">First Edition</h1>'
        . '<p class="sj-lede">LITHUSA, the first run. ' . ($made ?: '') . ' pieces with their original hang tags, each shipped with a numbered card. When they\'re gone, they\'re gone.</p>'
        . '<p class="sj-small">Ships from Texas · US ' . sj_money($flat) . ', free over ' . sj_money($free) . ' · worldwide from $20</p>'
        . sj_product_cards();
});

/* Empty cart: a plain line and the product cards, instead of WooCommerce's sad face, dots and
   "New in store". The block's own wrapper stays, so the cart still switches to it when emptied. */
add_filter('render_block_woocommerce/empty-cart-block', function (string $html): string {
    if (!preg_match('/^\s*(<div\b[^>]*>)/', $html, $m)) {
        return $html;
    }
    $open = preg_replace('/class="/', 'class="sj-empty-cart ', $m[1], 1);
    return $open
        . '<p class="sj-lede">Your cart is empty.</p>'
        . '<p class="sj-small">The First Edition is still here while it lasts.</p>'
        . sj_product_cards()
        . '</div>';
});

/**
 * "Added to cart" panel, shown right after the product form is submitted (in place of WooCommerce's notice):
 * the piece, the cart subtotal, a Checkout button and a View cart link.
 */
function sj_added_panel(): string
{
    if (empty($_REQUEST['add-to-cart']) || !function_exists('WC') || !WC()->cart || wc_notice_count('error') > 0) {
        return '';
    }
    $items = WC()->cart->get_cart();
    if (!$items) {
        return '';
    }
    wc_clear_notices();
    $item = end($items);
    $product = $item['data'];
    $parent = $product->get_parent_id() ? wc_get_product($product->get_parent_id()) : $product;
    $size = $item['variation']['attribute_sizes'] ?? ($item['variation']['attribute_pa_sizes'] ?? 'One size');
    $count = WC()->cart->get_cart_contents_count();
    $subtotal = sj_money((float) WC()->cart->get_subtotal());
    return '<div class="sj-added" data-sj-added role="dialog" aria-modal="false" aria-labelledby="sj-added-title">'
        . '<div class="sj-added-panel">'
        . '<p class="sj-added-head"><span id="sj-added-title">✓ Added to cart</span>'
        . '<button type="button" class="sj-added-close" data-sj-added-close aria-label="Close">×</button></p>'
        . '<div class="sj-added-item">' . $product->get_image('woocommerce_thumbnail')
        . '<div><p class="sj-added-name">' . esc_html(wp_strip_all_tags($parent->get_name())) . '</p>'
        . '<p class="sj-small">' . esc_html($size) . ' · ' . esc_html(sj_money((float) $product->get_price())) . '</p></div></div>'
        . '<p class="sj-added-subtotal"><span>Cart subtotal (' . (int) $count . ' ' . ($count === 1 ? 'item' : 'items') . ')</span><span>' . esc_html($subtotal) . '</span></p>'
        . '<a class="sj-button" href="' . esc_url(wc_get_checkout_url()) . '">Checkout</a>'
        . '<a class="sj-button sj-button-outline" href="' . esc_url(wc_get_cart_url()) . '">View cart</a>'
        . '<button type="button" class="sj-added-continue" data-sj-added-close>Keep shopping</button>'
        . '</div></div>';
}

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
        ? '<div class="sj-buy" data-sj-buy data-price="' . esc_attr(sj_money((float) $product->get_price())) . '">' . $sizeGuide . $form . '</div>'
        : '<p class="sj-notice">All ' . ($size ?: '') . ' are gone. Join the list below to hear about Edition 02.</p>';

    $facts = '<ul class="sj-facts sj-small">'
        . '<li>Original hang tag</li>'
        . ($size ? '<li>Numbered card out of ' . $size . '</li>' : '')
        . '<li>Ships from Texas within 2 business days</li>'
        . '<li>US shipping $7, free over $65 · worldwide from $20</li>'
        . '</ul>';

    $added = sj_added_panel();
    return $added . (string) wc_print_notices(true)
        . '<p class="sj-small sj-back"><a href="' . esc_url(home_url('/')) . '">← First Edition</a></p>'
        . '<h1 class="sj-page-title">' . esc_html($name) . '</h1>'
        . '<p class="sj-price-line">' . esc_html(sj_money((float) $product->get_price())) . ' <span>· ' . esc_html($subtitle) . '</span></p>'
        . '<div class="sj-gallery" data-gallery>' . $gallery . '</div>'
        . $buy
        . '<div class="sj-description">' . apply_filters('the_content', $product->get_description()) . '</div>'
        . $facts;
});

/** Link previews (Instagram, iMessage, WhatsApp): title, description, photo and, on products, the price. */
add_action('wp_head', function (): void {
    $title = 'seasonal job — LITHUSA First Edition';
    $description = 'The first LITHUSA run by Ben Zilinskas: 64 numbered pieces, shipped from Texas, worldwide.';
    $url = is_singular() ? get_permalink() : home_url('/');
    $image = '';
    $price = '';
    if (function_exists('is_product') && is_product() && ($product = wc_get_product(get_queried_object_id()))) {
        $title = wp_strip_all_tags($product->get_name()) . ' — seasonal job';
        $description = trim(wp_strip_all_tags($product->get_short_description())) ?: $description;
        $image = (string) wp_get_attachment_image_url($product->get_image_id(), 'large');
        $price = (string) $product->get_price();
    }
    if ($image === '' && ($sizes = sj_theme_edition_sizes()) && ($first = wc_get_product(array_key_first($sizes)))) {
        $image = (string) wp_get_attachment_image_url($first->get_image_id(), 'large');
    }
    $tags = [
        'og:site_name' => 'seasonal job',
        'og:type' => $price !== '' ? 'product' : 'website',
        'og:title' => $title,
        'og:description' => $description,
        'og:url' => $url,
        'og:image' => $image,
        'product:price:amount' => $price,
        'product:price:currency' => $price !== '' ? get_woocommerce_currency() : '',
    ];
    foreach ($tags as $property => $content) {
        if ($content !== '') {
            echo '<meta property="' . esc_attr($property) . '" content="' . esc_attr($content) . '">' . "\n";
        }
    }
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
}, 5);

/** Order emails in the shop's look: paper background, Albura/Georgia, italic headings, thin black rules. */
add_filter('woocommerce_email_styles', function (string $css): string {
    $serif = "Albura, Georgia, 'Times New Roman', serif";
    return $css . "
body, #outer_wrapper, #wrapper, #inner_wrapper { background-color: #f5f3f1 !important; }
#wrapper { padding: 0 !important; max-width: 600px; }
#template_container { background-color: #f5f3f1 !important; border: 0 !important; border-radius: 0 !important; box-shadow: none !important; }
#template_header { background-color: #f5f3f1 !important; color: #000000 !important; border-radius: 0 !important; border: 0 !important; }
#header_wrapper { padding: 24px 48px 0 !important; }
#header_wrapper h1, #template_header h1 { font-family: $serif !important; font-style: italic; font-weight: 400 !important; font-size: 32px !important; line-height: 1.2 !important; color: #000000 !important; text-align: left !important; text-shadow: none !important; margin: 0 !important; }
#template_body, #body_content { background-color: #f5f3f1 !important; }
#body_content table > tbody > tr > td, #body_content_inner_cell { padding: 20px 48px 8px !important; }
#body_content_inner, #body_content_inner p, #body_content_inner td, #body_content_inner th, #body_content_inner li { font-family: $serif !important; font-size: 17px !important; line-height: 1.55 !important; color: #000000 !important; }
#body_content_inner h2, #body_content_inner h3 { font-family: $serif !important; font-style: italic; font-weight: 400 !important; font-size: 22px !important; color: #000000 !important; margin: 28px 0 10px !important; text-align: left !important; }
#body_content_inner a, #credit a { color: #000000 !important; text-decoration: underline !important; }
.sj-email-brand a { color: #000000 !important; text-decoration: none !important; }
.td, #body_content_inner table.td, #body_content_inner table.td th, #body_content_inner table.td td { border: 0 !important; border-bottom: 1px solid #000000 !important; background: transparent !important; color: #000000 !important; padding: 10px 6px 10px 0 !important; }
#body_content_inner table.td { border-top: 1px solid #000000 !important; }
#body_content_inner table.td thead th { font-size: 14px !important; font-weight: 400 !important; color: #6d6466 !important; }
#body_content_inner .wc-item-meta, #body_content_inner .wc-item-meta li, #body_content_inner .wc-item-meta p { font-size: 15px !important; color: #000000 !important; margin: 4px 0 0 !important; padding: 0 !important; list-style: none !important; }
#body_content_inner .wc-item-meta strong { font-weight: 400 !important; color: #6d6466 !important; }
#body_content_inner .wc-item-meta p { display: inline !important; }
#body_content_inner address, .address { font-family: $serif !important; font-style: normal !important; color: #000000 !important; border: 1px solid #000000 !important; border-radius: 0 !important; padding: 12px 14px !important; background: transparent !important; }
#body_content_inner .email-additional-content, #body_content_inner .email-additional-content p { color: #000000 !important; }
#template_footer, #template_footer td, #credit { background-color: #f5f3f1 !important; border: 0 !important; }
.email-order-item-meta, .email-order-detail-heading span { color: #6d6466 !important; }
img { border: 0 !important; }
";
}, 20);

// The product attributes are named "Colors" and "Sizes"; show them singular in the cart, checkout and emails.
add_filter('woocommerce_attribute_label', function (string $label): string {
    return ['Colors' => 'Color', 'Sizes' => 'Size'][$label] ?? $label;
});
