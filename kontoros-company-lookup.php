<?php
/**
 * Plugin Name:       Kontoros Company Lookup for WooCommerce
 * Plugin URI:        https://www.kontoros.lt/api
 * Description:       Lithuanian company autocomplete for the WooCommerce checkout: company name, company code, VAT code and registered address, from the free kontoros.lt API.
 * Version:           1.1.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Kontoros
 * Author URI:        https://www.kontoros.lt
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kontoros-company-lookup
 * Domain Path:       /languages
 * WC requires at least: 8.9
 * WC tested up to:   11.3
 * Requires Plugins:  woocommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'KONTOROS_CL_VERSION', '1.1.0' );
define( 'KONTOROS_CL_FILE', __FILE__ );
define( 'KONTOROS_CL_DIR', plugin_dir_path( __FILE__ ) );
define( 'KONTOROS_CL_URL', plugin_dir_url( __FILE__ ) );

// Declare compatibility with HPOS and the block checkout.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	function () {
		load_plugin_textdomain( 'kontoros-company-lookup', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		require_once KONTOROS_CL_DIR . 'includes/class-settings.php';
		require_once KONTOROS_CL_DIR . 'includes/class-api.php';
		require_once KONTOROS_CL_DIR . 'includes/class-checkout.php';
		Kontoros_CL_Settings::init();
		Kontoros_CL_Api::init();
		Kontoros_CL_Checkout::init();
	}
);

// Block checkout fields must be registered on woocommerce_init (WooCommerce 8.9+).
add_action(
	'woocommerce_init',
	function () {
		if ( class_exists( 'Kontoros_CL_Checkout' ) ) {
			Kontoros_CL_Checkout::register_block_fields();
		}
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=kontoros' ) ) . '">' . esc_html__( 'Settings', 'kontoros-company-lookup' ) . '</a>' );
		return $links;
	}
);
