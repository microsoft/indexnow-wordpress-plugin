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
			$results = array_slice( $results, 0, 20 );
			$ids = array_map( function( $result ) { return (int) $result->id; }, $results );
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE id NOT IN ( $placeholders )", $ids ) );
			//phpcs:enable
		}
		return $results;
	}

	public static function url_submitted_within_last_minute( $table, $url ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $table;
		$date = time() - 60;
		$count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT 1 FROM ' . $table_name . ' WHERE submission_date > %d AND url = %s LIMIT 1', $date, $url ) );
		return $count > 0;
	}

	public static function insert_submission( $table, IndexNowSubmissions $submission ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $table;
		$wpdb->insert(
			$table_name,
			array(
				'url'             => $submission->url,
				'submission_type' => $submission->submission_type,
				'submission_date' => $submission->submission_date,
				'error'           => $submission->error,
				'type'            => $submission->type,
			),
			array( '%s', '%d', '%d', '%s', '%s' )
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

	// ---- Retry queue helpers ----

	/**
	 * Add a URL to the retry queue with a specified delay.
	 *
	 * @param string $url           The URL to retry.
	 * @param string $type          Submission type (add/update/delete).
	 * @param int    $delay_seconds Seconds to wait before retrying (default 60).
	 * @param int    $max_retries   Maximum number of retry attempts (default 3).
	 */
	public static function add_to_retry_queue( $url, $type, $delay_seconds = 60, $max_retries = 3 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'indexnow_retry_queue';

		// Avoid duplicates: skip if URL is already queued
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE url = %s",
			$url
		) );

		if ( $existing > 0 ) {
			if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
				error_log( __METHOD__ . " URL already in retry queue, skipping: " . $url );
			}
			return;
		}

		$now = time();
		$wpdb->insert(
			$table_name,
			array(
				'url'           => $url,
				'type'          => $type,
				'retry_count'   => 0,
				'max_retries'   => $max_retries,
				'next_retry_at' => $now + $delay_seconds,
				'created_at'    => $now,
				'last_error'    => '',
			),
			array( '%s', '%s', '%d', '%d', '%d', '%d', '%s' )
		);

		if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
			error_log( __METHOD__ . " Added to retry queue: " . $url . " (retry in {$delay_seconds}s)" );
		}
	}

	/**
	 * Get pending retry items that are due for processing.
	 *
	 * @param int $limit Maximum number of items to return (default 100, max 10000).
	 * @return array Array of retry queue row objects.
	 */
	public static function get_pending_retries( $limit = 100 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'indexnow_retry_queue';
		$now = time();
		$limit = min( $limit, 10000 );

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE next_retry_at <= %d ORDER BY next_retry_at ASC LIMIT %d",
			$now,
			$limit
		), OBJECT );

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Update a retry queue item after a failed retry attempt.
	 * Uses exponential backoff: delay = 60 * 2^retry_count seconds.
	 *
	 * @param int    $id          The retry queue item ID.
	 * @param int    $retry_count The updated retry count.
	 * @param string $last_error  The error message from the last attempt.
	 */
	public static function update_retry_item( $id, $retry_count, $last_error = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'indexnow_retry_queue';

		// Exponential backoff: 60s, 120s, 240s, ...
		$delay = 60 * pow( 2, $retry_count );
		$next_retry_at = time() + $delay;

		$wpdb->update(
			$table_name,
			array(
				'retry_count'   => $retry_count,
				'next_retry_at' => $next_retry_at,
				'last_error'    => $last_error,
			),
			array( 'id' => $id ),
			array( '%d', '%d', '%s' ),
			array( '%d' )
		);

		if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
			error_log( __METHOD__ . " Retry item #{$id} updated: attempt {$retry_count}, next retry in {$delay}s" );
		}
	}

	/**
	 * Remove a retry queue item (after success or max retries exceeded).
	 *
	 * @param int $id The retry queue item ID.
	 */
	public static function delete_retry_item( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'indexnow_retry_queue';
		$wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Get the count of items currently in the retry queue.
	 *
	 * @return int Number of queued items.
	 */
	public static function get_retry_queue_count() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'indexnow_retry_queue';
		//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
		//phpcs:enable
	}

	/**
	 * Delete all items from the retry queue.
	 */
	public static function clear_retry_queue() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'indexnow_retry_queue';
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( 'DELETE FROM ' . $table_name );
		//phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
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
