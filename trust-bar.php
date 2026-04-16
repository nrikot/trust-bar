<?php
/**
 * Plugin Name: Trust Bar
 * Plugin URI: https://github.com/nrikot/trustbar/trust-bar
 * Description: Display a beautiful, configurable logo/trust bar with carousels, color modes, shortcodes, and Gutenberg block support.
 * Version: 1.0.0
 * Author: N Riko Trihendrawan
 * License: GPL v2 or later
 * Text Domain: trust-bar
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'TRUST_BAR_VERSION', '1.0.0' );
define( 'TRUST_BAR_DIR', plugin_dir_path( __FILE__ ) );
define( 'TRUST_BAR_URL', plugin_dir_url( __FILE__ ) );
define( 'TRUST_BAR_TABLE', 'trust_bar_logos' );

require_once TRUST_BAR_DIR . 'includes/class-trust-bar-db.php';
require_once TRUST_BAR_DIR . 'includes/class-trust-bar-admin.php';
require_once TRUST_BAR_DIR . 'includes/class-trust-bar-shortcode.php';
require_once TRUST_BAR_DIR . 'includes/class-trust-bar-block.php';
require_once TRUST_BAR_DIR . 'includes/class-trust-bar-widget.php';
require_once TRUST_BAR_DIR . 'includes/class-trust-bar-image.php';

register_activation_hook( __FILE__, array( 'Trust_Bar_DB', 'install' ) );
register_deactivation_hook( __FILE__, array( 'Trust_Bar_DB', 'deactivate' ) );

function trust_bar_init() {
    new Trust_Bar_Admin();
    new Trust_Bar_Shortcode();
    new Trust_Bar_Block();
    new Trust_Bar_Widget();
}
add_action( 'plugins_loaded', 'trust_bar_init' );

function trust_bar_enqueue_frontend() {
    wp_enqueue_style(
        'trust-bar-frontend',
        TRUST_BAR_URL . 'assets/css/frontend.css',
        array(),
        TRUST_BAR_VERSION
    );
    wp_enqueue_script(
        'trust-bar-frontend',
        TRUST_BAR_URL . 'assets/js/frontend.js',
        array(),
        TRUST_BAR_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'trust_bar_enqueue_frontend' );
