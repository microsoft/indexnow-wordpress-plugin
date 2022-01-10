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

		$api_key = wp_generate_uuid4();
		$api_key = preg_replace('[-]', '', $api_key);
		update_option( 'indexnow-is_valid_api_key', '2' );
		update_option( 'indexnow-admin_api_key', base64_encode( $api_key ) );
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
}
