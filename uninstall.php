<?php
/**
 * Uninstall Easy Digital Downloads
 *
 * Deletes all the plugin data i.e.
 * 		1. Custom Post types.
 * 		2. Terms & Taxonomies.
 * 		3. Plugin pages.
 * 		4. Plugin options.
 * 		5. Capabilities.
 * 		6. Roles.
 * 		7. Database tables.
 * 		8. Cron events.
 *
 * @package     EDD
 * @subpackage  Uninstall
 * @copyright   Copyright (c) 2015, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.3
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Load EDD file.
include_once( 'easy-digital-downloads.php' );

global $wpdb, $wp_roles;

if( edd_get_option( 'uninstall_on_delete' ) ) {

	/** Delete All the Custom Post Types */
	$edd_taxonomies = array( 'download_category', 'download_tag', 'edd_log_type', );
	$edd_post_types = array( 'download', 'edd_payment', 'edd_discount', 'edd_log' );
	foreach ( $edd_post_types as $post_type ) {

		$edd_taxonomies = array_merge( $edd_taxonomies, get_object_taxonomies( $post_type ) );
		$items = get_posts( array( 'post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );

		if ( $items ) {
			foreach ( $items as $item ) {
				wp_delete_post( $item, true);
			}
		}
	}

	/** Delete All the Terms & Taxonomies */
	foreach ( array_unique( array_filter( $edd_taxonomies ) ) as $taxonomy ) {

		$terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('%s') ORDER BY t.name ASC", $taxonomy ) );

		// Delete Terms.
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$wpdb->delete( $wpdb->term_relationships, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
				$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
				$wpdb->delete( $wpdb->terms, array( 'term_id' => $term->term_id ) );
			}
		}

		// Delete Taxonomies.
		$wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $taxonomy ), array( '%s' ) );
	}

	/** Delete the Plugin Pages */
	$edd_created_pages = array( 'purchase_page', 'success_page', 'failure_page', 'purchase_history_page' );
	foreach ( $edd_created_pages as $p ) {
		$page = edd_get_option( $p, false );
		if ( $page ) {
			wp_delete_post( $page, true );
		}
	}

	/** Delete all the Plugin Options */
	$edd_options = array(
		'edd_completed_upgrades',
		'edd_default_api_version',
		'edd_earnings_total',
		'edd_settings',
		'edd_tracking_notice',
		'edd_tax_rates',
		'edd_use_php_sessions',
		'edd_version',
		'edd_version_upgraded_from',

		// Widgets
		'widget_edd_product_details',
		'widget_edd_cart_widget',
		'widget_edd_categories_tags_widget',

		// Database options
		'wpdb_edd_customermeta_version',
		'wpdb_edd_customers_version',
		'wpdb_edd_discountmeta_version',
		'wpdb_edd_discounts_version',
		'wpdb_edd_logmeta_version',
		'wpdb_edd_logs_version',
		'wpdb_edd_notemeta_version',
		'wpdb_edd_notes_version',
		'wpdb_edd_order_itemmeta_version',
		'wpdb_edd_order_items_version',
		'wpdb_edd_order_extras_version',
		'wpdb_edd_ordermeta_version',
		'wpdb_edd_orders_version',

		// Deprecated 3.0.0
		'wp_edd_customers_db_version',
		'wp_edd_customermeta_db_version',
		'_edd_table_check'
	);
	foreach ( $edd_options as $option ) {
		delete_option( $option );
	}

	/** Delete Capabilities */
	EDD()->roles->remove_caps();

	/** Delete the Roles */
	$edd_roles = array( 'shop_manager', 'shop_accountant', 'shop_worker', 'shop_vendor' );
	foreach ( $edd_roles as $role ) {
		remove_role( $role );
	}

	// Remove all database tables
	$edd_db_tables = array( 'customers', 'customermeta', 'discounts', 'discountmeta', 'logs', 'logmeta', 'notes', 'notemeta', 'orders', 'ordermeta', 'order_items', 'order_itemmeta', 'order_extras' );
	foreach ( $edd_db_tables as $table ) {
		$query = "DROP TABLE IF EXISTS {$wpdb->prefix}edd_{$table}";
		$wpdb->query( $query );
	}

	/** Cleanup Cron Events */
	wp_clear_scheduled_hook( 'edd_daily_scheduled_events' );
	wp_clear_scheduled_hook( 'edd_daily_cron' );
	wp_clear_scheduled_hook( 'edd_weekly_cron' );

	// Remove any transients we've left behind
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_edd\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_edd\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_edd\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_timeout\_edd\_%'" );
}
