<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Bing_Webmaster
 * @subpackage Bing_Webmaster/includes
 * @author     Bing Webmaster <bingwpus@microsoft.com>
 */
class Bing_Webmaster_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    0.01.01
	 */
	public static function deactivate( $plugin_name ) {
		delete_option( 'bwt-failed_count' );
		delete_option( 'bwt-passed_count' );
		delete_option( 'bwt-is_valid_api_key' );
		delete_option( 'bwt-admin_api_key' );
		delete_option( 'bwt-auto_submission_enabled' );

		global $wpdb;

		$table_name = $wpdb->prefix . 'bwt_failed_submissions';
		//phpcs:disable 
		$wpdb->query( 'DROP TABLE  ' . $table_name );
		//phpcs:enable 
		$table_name = $wpdb->prefix . 'bwt_passed_submissions';
		//phpcs:disable
		$wpdb->query( 'DROP TABLE  ' . $table_name );
		//phpcs:enable 
	}

}
