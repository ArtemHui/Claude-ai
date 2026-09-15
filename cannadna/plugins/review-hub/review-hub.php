<?php
/**
 * Plugin Name:       Review Hub
 * Plugin URI:        https://example.com/review-hub
 * Description:       Affiliate review hub for CBD/hemp brands: brand + product review content types, faceted filtering, carousels, a guided product finder, brand comparison, and outbound affiliate click tracking.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * Text Domain:       review-hub
 *
 * @package ReviewHub
 */

defined( 'ABSPATH' ) || exit;

define( 'REVIEW_HUB_VERSION', '0.1.0' );
define( 'REVIEW_HUB_FILE', __FILE__ );
define( 'REVIEW_HUB_DIR', plugin_dir_path( __FILE__ ) );
define( 'REVIEW_HUB_URL', plugin_dir_url( __FILE__ ) );

require_once REVIEW_HUB_DIR . 'includes/class-content-types.php';
require_once REVIEW_HUB_DIR . 'includes/class-fields.php';
require_once REVIEW_HUB_DIR . 'includes/class-query.php';
require_once REVIEW_HUB_DIR . 'includes/class-assets.php';
require_once REVIEW_HUB_DIR . 'includes/class-rest.php';
require_once REVIEW_HUB_DIR . 'includes/class-shortcodes.php';
require_once REVIEW_HUB_DIR . 'includes/class-clicks.php';

/**
 * Boot the plugin.
 */
function review_hub_init() {
	Review_Hub_Content_Types::init();
	Review_Hub_Fields::init();
	Review_Hub_Assets::init();
	Review_Hub_REST::init();
	Review_Hub_Shortcodes::init();
	Review_Hub_Clicks::init();
}
add_action( 'plugins_loaded', 'review_hub_init' );

/**
 * Register content types on activation, then flush permalinks once.
 */
function review_hub_activate() {
	Review_Hub_Content_Types::register();
	Review_Hub_Clicks::create_table();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'review_hub_activate' );

/**
 * Flush permalinks on deactivation so the custom routes are dropped.
 */
function review_hub_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'review_hub_deactivate' );
