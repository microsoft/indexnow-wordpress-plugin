<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Bing_Webmaster
 * @subpackage Bing_Webmaster/includes
 * @author     Bing Webmaster <bingwpus@microsoft.com>
 */
class Bing_Webmaster_Activator {

	/**
	 *
	 * @since    0.01.01
	 */
	public static function activate($plugin_name) {
		self::add_failed_submissions_table();
		self::add_passed_submissions_table();
	}

	public static function add_passed_submissions_table() {
		global $wpdb;
	
		$table_name = $wpdb->prefix . 'bwt_passed_submissions';
		
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
	
		$table_name = $wpdb->prefix . 'bwt_failed_submissions';
		
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
