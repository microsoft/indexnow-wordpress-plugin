<?php

/**
 * The admin-specific functionality of the plugin.
 * Utilities used by the plugin
 *
 * @package    BWT_IndexNow
 * @subpackage BWT_IndexNow/admin-utils
 * @author     Microsoft Bing <bingwpus@microsoft.com>
*/
class BWT_IndexNow_Admin_Utils {

	/**
	 * This function finds out the count of submissions in last 48 hours.
	 */
	public static function get_count( IndexNowSubmissionCount $submission_count ) {
		$curr_time = time();
		self::set_last_date( $submission_count, $curr_time );
		$count = 0;
		for ( $i = 0; $i < 48; $i++ ) {
			$count += $submission_count->hourly_count[ $i ];
		}
		return $count;
	}

	/**
	 * Increase the count by 1.
	 */
	public static function increase_count( IndexNowSubmissionCount $submission_count ) {
		$curr_time = time();
		self::set_last_date( $submission_count, $curr_time );
		$submission_count->hourly_count[ $submission_count->index ]++;
	}

	/**
	 * Set the last date when count was accessed/increased.
	 * We store the count in array of size 48, count per hour so that we don't need to store every submission.
	 */
	private static function set_last_date( IndexNowSubmissionCount $submission_count, $curr_time ) {
		$curr_hour = (int) ( $curr_time / 3600 );
		$last_hour = (int) ( $submission_count->last_count_date / 3600 );
		if ( $curr_hour - $last_hour <= 48 ) {
			$i = 0;
			for ( $i = ( $submission_count->index + 1 ) % 48; $last_hour < $curr_hour; $i = ( $i + 1 ) % 48, $last_hour++ ) {
				$submission_count->hourly_count[ $i ] = 0;
			}
			$submission_count->index = ( $i + 47 ) % 48;
		} else {
			for ( $i = 0; $i < 48; $i++ ) {
				$submission_count->hourly_count[ $i ] = 0;
			}
			$submission_count->index = $curr_hour % 24;
		}
		$submission_count->last_count_date = $curr_time;
	}

	/**
	 * This function retrieves the latest submitted urls upto 20.
	 *  It deletes the older submitted urls from the table.
	 */
	public static function get_submissions( $table ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $table;
		$date = time() - 48 * 60 * 60;
			$results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $table_name . ' WHERE submission_date > %d ORDER BY submission_date DESC LIMIT 21', $date ), OBJECT );
		if ( is_array( $results ) && count( $results ) === 21 ) {
			$ids = array();
			$results = array_slice( $results, 0, 20 );
			foreach ( $results as $result ) {
				array_push( $ids, $result->id );
			}
			$res = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $table_name . ' WHERE id not in ( %s )', implode( ',', $ids ) ) );
		}
		return $results;
	}

	public static function url_submitted_within_last_minute( $table, $url ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $table;
		$date = time() - 60;
		$results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $table_name . ' WHERE submission_date > %d AND url =  %s LIMIT 1', array( $date, $url ) ), OBJECT );
		return is_array( $results ) && count( $results ) > 0;
	}

	public static function insert_submission( $table, IndexNowSubmissions $submission ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $table;
		$results = $wpdb->insert(
			$table_name,
			array(
				'url' => $submission->url,
				'submission_type' => $submission->submission_type,
				'submission_date' => $submission->submission_date,
				'error' => $submission->error,
				'type' => $submission->type,
			)
		);
	}

	public static function delete_submissions( $table ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $table;
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->query( 'DELETE FROM ' . $table_name );
		return $results;
		//phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function is_localhost( $ip = array( '127.0.0.1', '::1' ) ) {
		return in_array( $_SERVER['REMOTE_ADDR'], $ip );
	}

	public  static function generate_indexnow_key() 
	{
		$api_key = wp_generate_uuid4();
		$api_key = preg_replace('[-]', '', $api_key);
		update_option( 'indexnow-is_valid_api_key', '2' );
		update_option( 'indexnow-admin_api_key', base64_encode( $api_key ) );
		update_option( 'indexnow-is_valid_api_key', '1' );
	}
}

class IndexNowSubmissionCount {

	public function __construct() {

		$this->hourly_count = array();
		for ( $i = 0; $i < 48; $i++ ) {
			array_push( $this->hourly_count, 0 );
		}
		$this->last_count_date = time();

	}

	public $hourly_count;
	public $last_count_date;
	public $index;
}
