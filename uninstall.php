<?php
/**
 * Remove plugin options and cached lookups. Order data (company/VAT codes) is kept.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach ( array( 'enabled', 'api_key', 'active_only', 'add_fields', 'name_format', 'fill_address', 'attribution', 'show_company' ) as $kontoros_cl_key ) {
	delete_option( 'kontoros_cl_' . $kontoros_cl_key );
}

global $wpdb;
// Cached lookups are transients with generated names, so they can only be removed by pattern.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_kontoros\\_cl\\_%' OR option_name LIKE '\\_transient\\_timeout\\_kontoros\\_cl\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
