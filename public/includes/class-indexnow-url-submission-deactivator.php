<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    BWT_IndexNow
 * @subpackage BWT_IndexNow/includes
 * @author     Bing Webmaster <bingwpus@microsoft.com>
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
		delete_option( 'bwt-indexnow-failed_count' );
		delete_option( 'bwt-indexnow-passed_count' );
		delete_option( 'bwt-indexnow-is_valid_api_key' );
		delete_option( 'bwt-indexnow-admin_api_key' );
		delete_option( 'bwt-indexnow-auto_submission_enabled' );

		global $wpdb;

		$table_name = $wpdb->prefix . 'bwt_indexnow_failed_submissions';
		//phpcs:disable 
		$wpdb->query( 'DROP TABLE  ' . $table_name );
		//phpcs:enable 
		$table_name = $wpdb->prefix . 'bwt_indexnow_passed_submissions';
		//phpcs:disable
		$wpdb->query( 'DROP TABLE  ' . $table_name );
		//phpcs:enable 
	}

}
