<?php
/**
 * Plugin Name: seasonal job style
 * Description: Gives the shop the benzilinskas.com look (Albura type, off-white paper, black ink) on top of Twenty Twenty-Four.
 * Version: 1.0.0
 * Author: seasonal job
 *
 * Install: copy this file and the seasonal-job-assets/ folder to wp-content/mu-plugins/.
 */

defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', function (): void {
    $file = WPMU_PLUGIN_DIR . '/seasonal-job-assets/style.css';
    wp_enqueue_style(
        'seasonal-job-style',
        WPMU_PLUGIN_URL . '/seasonal-job-assets/style.css',
        [],
        is_file($file) ? (string) filemtime($file) : '1'
    );
}, 100);
