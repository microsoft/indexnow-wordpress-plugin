<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    BWT_IndexNow
 * @subpackage BWT_IndexNow/includes
 * @author     Microsoft Bing <bingwpus@microsoft.com>
 */
class BWT_IndexNow_Activator {

	/**
	 *
	 * @since    0.01.01
	 */
	public static function activate($plugin_name) {
		self::add_failed_submissions_table();
		self::add_passed_submissions_table();
		self::add_retry_queue_table();

		$api_key = wp_generate_uuid4();
		$api_key = preg_replace('[-]', '', $api_key);
		update_option( 'indexnow-is_valid_api_key', '2' );
		update_option( 'indexnow-admin_api_key', base64_encode( $api_key ) );

		// Schedule the retry queue cron event
		if ( ! wp_next_scheduled( 'indexnow_process_retry_queue' ) ) {
			$scheduled = wp_schedule_event( time() + 60, 'every_minute', 'indexnow_process_retry_queue', array(), true );
			if ( false === $scheduled || is_wp_error( $scheduled ) ) {
				if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
					$error = is_wp_error( $scheduled ) ? $scheduled->get_error_message() : 'wp_schedule_event returned false';
					error_log( __METHOD__ . ' Could not schedule retry cron event: ' . $error );
				}
			}
		}
	}


	public static function add_passed_submissions_table() {
		global $wpdb;
	
		$table_name = $wpdb->prefix . 'indexnow_passed_submissions';
		
		$charset_collate = $wpdb->get_charset_collate();
	
		$sql = "CREATE TABLE $table_name (
			id mediumint(10) NOT NULL AUTO_INCREMENT,
			submission_date bigint(20) NOT NULL,
			submission_type tinytext NOT NULL,
			type tinytext NOT NULL,
			error text NOT NULL,
			url varchar(200) DEFAULT '' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public static function add_failed_submissions_table() {
		global $wpdb;
	
		$table_name = $wpdb->prefix . 'indexnow_failed_submissions';
		
		$charset_collate = $wpdb->get_charset_collate();
	
		$sql = "CREATE TABLE $table_name (
			id mediumint(10) NOT NULL AUTO_INCREMENT,
			submission_date bigint(20) NOT NULL,
			submission_type tinytext NOT NULL,
			type tinytext NOT NULL,
			error text NOT NULL,
			url varchar(200) DEFAULT '' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Create the retry queue table for storing failed submissions
	 * that need to be retried (e.g., after 429 Too Many Requests).
	 */
	public static function add_retry_queue_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'indexnow_retry_queue';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(10) NOT NULL AUTO_INCREMENT,
			url varchar(200) DEFAULT '' NOT NULL,
			type tinytext NOT NULL,
			retry_count tinyint(3) NOT NULL DEFAULT 0,
			max_retries tinyint(3) NOT NULL DEFAULT 3,
			next_retry_at bigint(20) NOT NULL,
			created_at bigint(20) NOT NULL,
			last_error varchar(255) DEFAULT '' NOT NULL,
			PRIMARY KEY  (id),
			KEY next_retry_at (next_retry_at)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}
