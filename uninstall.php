<?php
/**
 * Uninstall handler — runs when the plugin is deleted via WP admin.
 *
 * @package SCWhatsApp
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}scwa_notification_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}scwa_message_templates" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// Delete all plugin options.
$options = array(
	'scwa_phone_number_id',
	'scwa_business_account_id',
	'scwa_api_access_token',
	'scwa_api_version',
	'scwa_default_country_code',
	'scwa_admin_phone',
	'scwa_enable_order_confirmed',
	'scwa_enable_fulfillment_created',
	'scwa_enable_refund_created',
	'scwa_enable_admin_new_order',
	'scwa_db_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Clean up transients.
delete_transient( 'scwa_connection_status' );
