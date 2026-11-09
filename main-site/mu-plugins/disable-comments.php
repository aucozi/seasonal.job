<?php
/**
 * Plugin Name: Disable comments
 * Description: Turns off comments, pingbacks and trackbacks across benzilinskas.com (spam). Existing comments are hidden, not deleted.
 * Version: 1.0.0
 *
 * Install: wp-content/mu-plugins/ (loads automatically). Delete this file to bring comments back.
 */

defined('ABSPATH') || exit;

// Nothing on the site accepts comments, pingbacks or trackbacks.
add_filter('comments_open', '__return_false', 20);
add_filter('pings_open', '__return_false', 20);
add_filter('comments_array', '__return_empty_array', 20);
add_filter('xmlrpc_methods', function (array $methods): array {
    unset($methods['pingback.ping'], $methods['pingback.extensions.getPingbacks']);
    return $methods;
});
add_filter('wp_headers', function (array $headers): array {
    unset($headers['X-Pingback']);
    return $headers;
});

// Remove comment support from every post type, and turn off comments for new posts.
add_action('init', function (): void {
    foreach (get_post_types() as $type) {
        if (post_type_supports($type, 'comments')) {
            remove_post_type_support($type, 'comments');
            remove_post_type_support($type, 'trackbacks');
        }
    }
}, 100);
add_filter('pre_option_default_comment_status', fn () => 'closed');
add_filter('pre_option_default_ping_status', fn () => 'closed');

// Comment posting is refused even if a bot posts directly to wp-comments-post.php.
add_action('pre_comment_on_post', function (): void {
    wp_die('Comments are closed.', '', ['response' => 403]);
});

// Hide comment screens and counters in the admin.
add_action('admin_menu', function (): void {
    remove_menu_page('edit-comments.php');
    remove_submenu_page('options-general.php', 'options-discussion.php');
});
add_action('admin_init', function (): void {
    global $pagenow;
    if (in_array($pagenow, ['edit-comments.php', 'options-discussion.php'], true) && !isset($_GET['sj_show'])) {
        wp_safe_redirect(admin_url());
        exit;
    }
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
});
add_action('wp_before_admin_bar_render', function (): void {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
});

// Block themes: the Comments block renders nothing.
add_filter('render_block_core/comments', '__return_empty_string');
add_filter('render_block_core/post-comments-form', '__return_empty_string');
