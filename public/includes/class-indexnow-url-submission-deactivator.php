<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    BWT_IndexNow
 * @subpackage BWT_IndexNow/includes
 * @author     Microsoft Bing <bingwpus@microsoft.com>
 */
class BWT_IndexNow_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    0.01.01
	 */
	public static function deactivate( $plugin_name ) {
		delete_option( 'indexnow-failed_count' );
		delete_option( 'indexnow-passed_count' );
		delete_option( 'indexnow-is_valid_api_key' );
		delete_option( 'indexnow-admin_api_key' );
		delete_option( 'indexnow-auto_submission_enabled' );
		delete_option( 'indexnow-excluded_paths' );

		// Clear the retry queue cron event
		$timestamp = wp_next_scheduled( 'indexnow_process_retry_queue' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'indexnow_process_retry_queue' );
		}

		global $wpdb;

		$table_name = $wpdb->prefix . 'indexnow_failed_submissions';
		//phpcs:disable 
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name );
		//phpcs:enable 
		$table_name = $wpdb->prefix . 'indexnow_passed_submissions';
		//phpcs:disable
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name );
		//phpcs:enable

		$table_name = $wpdb->prefix . 'indexnow_retry_queue';
		//phpcs:disable
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name );
		//phpcs:enable
	}

}
