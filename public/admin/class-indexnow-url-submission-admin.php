<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    BWT_IndexNow
 * @subpackage BWT_IndexNow/admin
 * @author     Microsoft Bing <bingwpus@microsoft.com>
 */
class BWT_IndexNow_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.01.01
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.01.01
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $prefix = "indexnow-";

	private $routes;

	const RETRY_QUEUE_BATCH_SIZE = 1000;

	const RETRY_QUEUE_MAX_ITEMS = 50000;

	const RETRY_CRON_STALE_THRESHOLD = 86400;

	const RETRY_CRON_LAST_RUN_OPTION = 'retry_cron_last_run';
	
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.01.01
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version           The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/utils/class-indexnow-url-submission-admin-utils.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/utils/class-indexnow-url-submission-admin-routes.php';
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->routes = new BWT_IndexNow_Admin_Routes($this->plugin_name, $this->version, $this->prefix);

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    0.01.01
	 */
	public function enqueue_styles() {

		$CSSfiles = scandir(dirname(__FILE__) . '/../static/css/');
		   foreach($CSSfiles as $filename) {
			if(strpos($filename,'.css')&&strpos($filename,'.css')+4 === strlen($filename)) {
				wp_enqueue_style( $filename, plugin_dir_url( __FILE__ ) . '../static/css/' . $filename, array(), mt_rand(10,1000), 'all' );
			}
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    0.01.01
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/indexnow-url-submission-admin.js', array( 'jquery' ), $this->version, false );

		$JSfiles = scandir(dirname(__FILE__) . '/../static/js/');
		   $react_js_to_load = '';
		   foreach($JSfiles as $filename) {
			   if(strpos($filename,'.js')&&strpos($filename,'.js')+3 === strlen($filename)) {
				   $react_js_to_load = plugin_dir_url( __FILE__ ) . '../static/js/' . $filename;
				   wp_enqueue_script($filename, $react_js_to_load, '', mt_rand(10,1000), true);
			   }
		}

		wp_localize_script( $this->plugin_name, 'indexnow_wpr_object', array(
			'api_nonce'   => wp_create_nonce( 'wp_rest' ),
			'indexnow_api_url'	  => rest_url( $this->plugin_name . '/v_' . $this->version .'/' ),
			)
		);

	}

	public function register_routes()
	{
		$this->routes->register_routes();
	}

	/**
	 * Register the administration menu for this plugin under the WordPress Settings menu.
	 *
	 * @since    0.01.01
	 */
	public function add_plugin_admin_menu()
	{
		/*
		 * Add a menu page for this plugin under Settings.
		 */
		add_menu_page(
			__('IndexNow Plugin', $this->plugin_name),
			__('IndexNow', $this->plugin_name),
			'manage_options',
			$this->plugin_name,
			array($this, 'display_plugin_admin_page'),
			'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPCFET0NUWVBFIHN2ZyBQVUJMSUMgIi0vL1czQy8vRFREIFNWRyAxLjEvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkIj4KPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZlcnNpb249IjEuMSIgd2lkdGg9IjEyOHB4IiBoZWlnaHQ9IjEyOHB4IiBzdHlsZT0ic2hhcGUtcmVuZGVyaW5nOmdlb21ldHJpY1ByZWNpc2lvbjsgdGV4dC1yZW5kZXJpbmc6Z2VvbWV0cmljUHJlY2lzaW9uOyBpbWFnZS1yZW5kZXJpbmc6b3B0aW1pemVRdWFsaXR5OyBmaWxsLXJ1bGU6ZXZlbm9kZDsgY2xpcC1ydWxlOmV2ZW5vZGQiIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIj4KPGc+PHBhdGggc3R5bGU9Im9wYWNpdHk6MSIgZmlsbD0iIzAxMDMwNCIgZD0iTSA2MS41LDE0LjUgQyA2Ni4yMjI2LDE1LjAwNzEgNzAuODg5MywxNS44NDA0IDc1LjUsMTdDIDc3LjAyMTUsMjAuNjQ0MiA3Ny44NTQ4LDI0LjQ3NzUgNzgsMjguNUMgNzkuODIwOCwzMS45OTA3IDgyLjY1NDIsMzMuMTU3NCA4Ni41LDMyQyA4OS4yMDgxLDMwLjkzMDUgOTEuODc0NywyOS43NjM5IDk0LjUsMjguNUMgMTAyLjkwMSwzNC4zMDEyIDEwNy40MDEsNDIuMzAxMiAxMDgsNTIuNUMgMTA1LjMzMyw1NC41IDEwMi42NjcsNTYuNSAxMDAsNTguNUMgOTguNjI3LDYwLjY5NTQgOTguMjkzNyw2My4wMjg4IDk5LDY1LjVDIDEwMS43MDMsNjguNTM4IDEwNC43MDMsNzEuMjA0NiAxMDgsNzMuNUMgMTA3LjMxLDgyLjIyNTUgMTAzLjgxLDg5LjcyNTUgOTcuNSw5NkMgOTIuNjc5Nyw5Ni4yODU5IDg4LjE3OTcsOTUuMTE5MiA4NCw5Mi41QyA4MS4wODQ4LDkzLjA4MjggNzkuMDg0OCw5NC43NDk1IDc4LDk3LjVDIDc3Ljc0MDEsMTAxLjI1IDc3LjA3MzQsMTA0LjkxNiA3NiwxMDguNUMgNjcuOTI2MywxMTAuOTcyIDU5Ljc1OTcsMTExLjEzOCA1MS41LDEwOUMgNDkuNDU5OSwxMDUuMTY1IDQ4LjI5MzIsMTAwLjk5OCA0OCw5Ni41QyA0My44OTkyLDkxLjg4MTYgMzkuMzk5Miw5MS43MTQ5IDM0LjUsOTZDIDMyLjgzMzMsOTYuNjY2NyAzMS4xNjY3LDk2LjY2NjcgMjkuNSw5NkMgMjMuNzE5NSw5MC40NDUzIDE5LjcxOTUsODMuNzc4NiAxNy41LDc2QyAxOS4wNzY1LDcxLjk1MjEgMjEuOTA5OCw2OS4xMTg4IDI2LDY3LjVDIDI3LjQyMTcsNjQuOTY5OSAyNy43NTUsNjIuMzAzMyAyNyw1OS41QyAyMy4xMjY5LDU2Ljk2MDIgMTkuOTYwMiw1My43OTM2IDE3LjUsNTBDIDE5LjI1NTEsNDQuOTg5MiAyMS40MjE3LDQwLjE1NTkgMjQsMzUuNUMgMjUuNjk0NiwzMi4zMDU1IDI4LjE5NDYsMjkuOTcyMiAzMS41LDI4LjVDIDQ0LjQzOCwzNi40MDc1IDUwLjYwNDYsMzIuNzQwOSA1MCwxNy41QyA1My44NDE3LDE2LjEzNyA1Ny42NzUsMTUuMTM3IDYxLjUsMTQuNSBaIE0gNTYuNSw0My41IEMgNjEuNTExMSw0My4zMzQxIDY2LjUxMTEsNDMuNTAwNyA3MS41LDQ0QyA3Ni4wNDUyLDUwLjI1NjQgODAuNzExOSw1Ni40MjMgODUuNSw2Mi41QyA4MS4wMjU0LDY4LjgwODUgNzYuMzU4Nyw3NC45NzUyIDcxLjUsODFDIDY2LjUsODEuNjY2NyA2MS41LDgxLjY2NjcgNTYuNSw4MUMgNjEuMTY2Nyw3NSA2NS44MzMzLDY5IDcwLjUsNjNDIDY1LjY5MTgsNTYuNTI5NiA2MS4wMjUxLDUwLjAyOTYgNTYuNSw0My41IFogTSA1MS41LDQ4LjUgQyA1NC4zMDIxLDUxLjQ3NTggNTYuOTY4OCw1NC42NDI1IDU5LjUsNThDIDU1LjUsNjEuMzMzMyA1NS41LDY0LjY2NjcgNTkuNSw2OEMgNTcuMzU0Nyw3MS4zMTI0IDU0LjY4OCw3NC4xNDU3IDUxLjUsNzYuNUMgNDcuNzk1NSw3Mi4yNDk5IDQ0LjQ2MjIsNjcuNzQ5OSA0MS41LDYzQyA0NC4yNTg1LDU3Ljc0NjQgNDcuNTkxOSw1Mi45MTMxIDUxLjUsNDguNSBaIi8+PC9nPgo8L3N2Zz4K'
			);
	}

	/**
	 * Render the admin page for this plugin.
	 *
	 * @since    0.01.01
	 */
	public function display_plugin_admin_page()
	{
		include_once('partials/indexnow-url-submission-admin-react.php');
	}

	/**
	 * Add action link to the plugins page.
	 *
	 * @since    0.01.01
	 */
	public function add_action_links($links)
	{
		/*
		*  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
		*/
		$settings_link = array(
			'<a href="' . admin_url('options-general.php?page=' . $this->plugin_name) . '">' . __('Settings', $this->plugin_name) . '</a>',
		);
		return array_merge($settings_link, $links);
	}

	/**
	 * Check if a post/page has noindex directive set.
	 * This method fetches the actual rendered HTML and checks for the standard
	 * <meta name="robots" content="noindex"> tag, which works with any SEO plugin.
	 * 
	 * @param WP_Post $post The post object to check
	 * @param string $url The URL to check for noindex
	 * @return bool True if noindex is set, false otherwise
	 */
	private function has_noindex_directive($post, $url) {
		// Check if WordPress site-wide search engine visibility is disabled
		if (get_option('blog_public') == '0') {
			if (true === WP_DEBUG && true === WP_DEBUG_LOG) {
				error_log(__METHOD__ . " Site-wide search engine discouragement enabled");
			}
			return true;
		}

		// Fetch the page and check for noindex meta tag and X-Robots-Tag header
		// Use a short timeout to avoid delays in post publishing
		$response = wp_safe_remote_get($url, array(
			'timeout' => 5
		));

		if (is_wp_error($response)) {
			if (true === WP_DEBUG && true === WP_DEBUG_LOG) {
				error_log(__METHOD__ . " Could not fetch URL for noindex check: " . $url . " - " . $response->get_error_message());
			}
			return false;
		}

		// Check X-Robots-Tag HTTP header
		$headers = wp_remote_retrieve_headers($response);
		$robots_header = '';
		if (isset($headers['x-robots-tag'])) {
			$robots_header = $headers['x-robots-tag'];
		} elseif (isset($headers['X-Robots-Tag'])) {
			$robots_header = $headers['X-Robots-Tag'];
		}
		
		if (!empty($robots_header) && stripos($robots_header, 'noindex') !== false) {
			if (true === WP_DEBUG && true === WP_DEBUG_LOG) {
				error_log(__METHOD__ . " X-Robots-Tag noindex header detected for URL: " . $url);
			}
			return true;
		}

		// Check HTML meta robots tag
		$body = wp_remote_retrieve_body($response);
		if (!empty($body)) {
			// Match <meta name="robots" content="...noindex..."> (case insensitive)
			// Also check for googlebot, bingbot specific directives
			if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex[^"\']*["\'][^>]*>/i', $body) ||
				preg_match('/<meta[^>]+content=["\'][^"\']*noindex[^"\']*["\'][^>]+name=["\']robots["\'][^>]*>/i', $body)) {
				if (true === WP_DEBUG && true === WP_DEBUG_LOG) {
					error_log(__METHOD__ . " Meta robots noindex tag detected for URL: " . $url);
				}
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a URL path matches any excluded path patterns.
	 * 
	 * @param string $url The URL to check
	 * @return bool True if URL should be excluded, false otherwise
	 */
	private function is_path_excluded($url) {
		$excluded_paths = get_option($this->prefix . 'excluded_paths', '');
		
		if (empty($excluded_paths)) {
			return false;
		}

		// Parse URL to get the path
		$parsed_url = wp_parse_url($url);
		$path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';

		// Split excluded paths by newline and filter empty lines
		$patterns = array_filter(array_map('trim', explode("\n", $excluded_paths)));

		foreach ($patterns as $pattern) {
			// Skip empty patterns
			if (empty($pattern)) {
				continue;
			}

			// Support wildcard patterns (e.g., /private/*, /draft-*)
			$regex_pattern = str_replace(
				array('\*', '\?'),
				array('.*', '.'),
				preg_quote($pattern, '/')
			);

			if (preg_match('/^' . $regex_pattern . '$/i', $path)) {
				if (true === WP_DEBUG && true === WP_DEBUG_LOG) {
					error_log(__METHOD__ . " URL excluded by path pattern: " . $url . " matched " . $pattern);
				}
				return true;
			}
		}

		return false;
	}

	// This function checks the type of update on a page/post and accordingly calls the submit api if enabled
	public function on_post_published($new_status, $old_status, $post)
	{
		$admin_api_key = get_option( $this->prefix . "admin_api_key" );
		$is_valid_api_key = get_option( $this->prefix . "is_valid_api_key" );
		$auto_submission_enabled = get_option( $this->prefix . "auto_submission_enabled" );
		$is_change = false;
		$type = "add";
		if ($old_status === 'publish' && $new_status === 'publish') {
			$is_change = true;
			$type = "update";
		}
		else if ($old_status != 'publish' && $new_status === 'publish') {
			$is_change = true;
			$type = "add";
		}
		else if ($old_status === 'publish' && $new_status === 'trash') {
			$is_change = true;
			$type = "delete";
		}
		if ($is_change) {
			if (isset($is_valid_api_key) && $is_valid_api_key && $auto_submission_enabled && $auto_submission_enabled === "1") {
				$link = get_permalink($post);
				// remove __trashed from page url
				if (strpos($link, "__trashed") > 0) {
					$link = substr($link, 0, strlen($link) - 10) . "/";
				}
				if(empty($link)){
					if ( true === WP_DEBUG && true === WP_DEBUG_LOG) error_log(__METHOD__ . " link is empty");
					return;
				}
				
				if(function_exists('is_post_publicly_viewable')){
					$is_public_post = is_post_publicly_viewable($post);

					if ( true === WP_DEBUG && true === WP_DEBUG_LOG) {
						error_log(__METHOD__ . " is_public_post". (int)$is_public_post);
						error_log(__METHOD__ . " link ". $link);
					}

					if(!$is_public_post &&  $type != 'delete'){	
						return;
					}
				}else{
					$http_response_header = wp_safe_remote_head($link);
					$res_code = wp_remote_retrieve_response_code($http_response_header);
					
					if (true === WP_DEBUG && true === WP_DEBUG_LOG) error_log(__METHOD__ . " link ". $link." ".$res_code);
					
					if(empty($res_code) || ($res_code != 200 &&  $type != 'delete')){	
						return;
					}
				}

				// Check for noindex directive - don't submit URLs that should not be indexed
				if ($type != 'delete' && $this->has_noindex_directive($post, $link)) {
					if (true === WP_DEBUG && true === WP_DEBUG_LOG) {
						error_log(__METHOD__ . " Skipping URL submission due to noindex directive: " . $link);
					}
					return;
				}

				// Check for excluded paths - don't submit URLs that match excluded patterns
				if ($this->is_path_excluded($link)) {
					if (true === WP_DEBUG && true === WP_DEBUG_LOG) {
						error_log(__METHOD__ . " Skipping URL submission due to excluded path: " . $link);
					}
					return;
				}

				// check if same url was submitted recently(within a minute)
				if ($new_status != 'trash' && BWT_IndexNow_Admin_Utils::url_submitted_within_last_minute(BWT_IndexNow_Admin_Routes::$passed_submissions_table, $link)) {
					return;
				}
				$siteUrl = get_home_url();
				$api_key = base64_decode($admin_api_key);
				$output = $this->routes->submit_url_to_bwt($siteUrl, $link, $api_key, $type, false);

				if ( $output === 'success' ) {
					$this->routes->update_submission_output( $output, $link );
					return;
				}

				// Permanent client errors should not be retried
				$non_retryable_errors = array( 'error:InvalidRequest', 'error:InvalidApiKey', 'error:InvalidUrl' );
				if ( in_array( $output, $non_retryable_errors, true ) ) {
					$this->routes->update_submission_output( $output, $link );
					return;
				}

				// Transient/server errors: queue for retry with exponential backoff
				if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
					error_log( __METHOD__ . ' Submission failed (' . $output . '), queueing URL for retry: ' . $link );
				}

				if ( ! $this->ensure_retry_cron_scheduled() ) {
					// Avoid unbounded queue growth when cron cannot process retries.
					$this->routes->update_submission_output( $output, $link );
					return;
				}

				BWT_IndexNow_Admin_Utils::add_to_retry_queue( $link, $type, 60, 3, $output );
				$this->record_discarded_retry_items( BWT_IndexNow_Admin_Utils::trim_retry_queue( self::RETRY_QUEUE_MAX_ITEMS ) );
			}
		}
	}

	public function options_update()
	{
		register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));
	}

	public function validate($input)
	{
		return $input;
	}


	/**
	 * Ensure retry cron is scheduled before queueing retry items.
	 *
	 * @return bool True when retry cron is available, false otherwise.
	 */
	private function ensure_retry_cron_scheduled() {
		$next_scheduled = wp_next_scheduled( 'indexnow_process_retry_queue' );
		if ( false !== $next_scheduled ) {
			$last_run = $this->get_retry_cron_last_run();
			$stale_cutoff = time() - self::RETRY_CRON_STALE_THRESHOLD;

			if ( $next_scheduled >= $stale_cutoff || $last_run >= $stale_cutoff ) {
				return true;
			}

			$discarded = BWT_IndexNow_Admin_Utils::clear_retry_queue();
			$this->record_discarded_retry_items( $discarded );
			if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
				error_log( __METHOD__ . ' Retry cron appears stale; retry queue cleared for ' . count( $discarded ) . ' item(s). Last run: ' . $last_run );
			}
			return false;
		}

		$scheduled = wp_schedule_event( time() + 60, 'every_minute', 'indexnow_process_retry_queue' );
		if ( false === $scheduled || is_wp_error( $scheduled ) ) {
			$discarded = BWT_IndexNow_Admin_Utils::clear_retry_queue();
			$this->record_discarded_retry_items( $discarded );
			if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
				$error = is_wp_error( $scheduled ) ? $scheduled->get_error_message() : 'wp_schedule_event returned false';
				error_log( __METHOD__ . ' Retry cron could not be scheduled; retry queue cleared for ' . count( $discarded ) . ' item(s): ' . $error );
			}
			return false;
		}

		return true;
	}

	/**
	 * Get the last retry cron run timestamp directly from the database.
	 *
	 * Bulk post updates can keep an older autoloaded option value in memory while
	 * WP-Cron updates the option in a separate request.
	 *
	 * @return int Last retry cron run timestamp.
	 */
	private function get_retry_cron_last_run() {
		global $wpdb;

		$last_run = $wpdb->get_var( $wpdb->prepare(
			"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
			$this->prefix . self::RETRY_CRON_LAST_RUN_OPTION
		) );

		return false === $last_run ? 0 : (int) $last_run;
	}

	/**
	 * Record discarded retry queue items as failed submissions.
	 *
	 * @param array $items Retry queue row objects.
	 */
	private function record_discarded_retry_items( $items ) {
		if ( empty( $items ) ) {
			return;
		}

		foreach ( $items as $item ) {
			$reason = isset( $item->last_error ) ? trim( (string) $item->last_error ) : '';
			if ( '' === $reason ) {
				$reason = 'error:RequestFailed';
			} elseif ( 0 !== strpos( $reason, 'error:' ) ) {
				$reason = 'error:' . $reason;
			}

			$type = isset( $item->type ) ? $item->type : 'add';
			$this->routes->update_submission_output( $reason, $item->url, $type );
		}
	}

	/**
	 * Register a custom "every_minute" cron interval for retry queue processing.
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified cron schedules.
	 */
	public function add_cron_intervals( $schedules ) {
		if ( ! isset( $schedules['every_minute'] ) ) {
			$schedules['every_minute'] = array(
				'interval' => 60,
				'display'  => __( 'Every Minute' ),
			);
		}
		return $schedules;
	}

	/**
	 * Process the retry queue: re-submit URLs that previously failed due to transient errors
	 * (e.g., 429 rate limiting, network failures, server errors).
	 * Called by the WP-Cron event 'indexnow_process_retry_queue'.
	 *
	 * Uses batch submission (up to 1000 URLs per request) and exponential backoff.
	 */
	public function process_retry_queue() {
		update_option( $this->prefix . self::RETRY_CRON_LAST_RUN_OPTION, time() );

		if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
			error_log( __METHOD__ . ' Retry cron triggered' );
		}

		$admin_api_key = get_option( $this->prefix . 'admin_api_key' );
		$is_valid_api_key = get_option( $this->prefix . 'is_valid_api_key' );

		if ( empty( $admin_api_key ) || '1' !== (string) $is_valid_api_key ) {
			if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
				error_log( __METHOD__ . ' Retry queue skipped: API key missing or invalid state (' . (string) $is_valid_api_key . ')' );
			}
			return;
		}

		$pending = BWT_IndexNow_Admin_Utils::get_pending_retries();

		if ( empty( $pending ) ) {
			if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
				error_log( __METHOD__ . ' Retry cron found no due items' );
			}
			return;
		}

		if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
			error_log( __METHOD__ . ' Retry cron processing ' . count( $pending ) . ' due item(s)' );
		}

		$api_key = base64_decode( $admin_api_key );
		$siteUrl = get_home_url();

		// Group by type so we can batch URLs of the same type
		$grouped = array();
		foreach ( $pending as $item ) {
			$grouped[ $item->type ][] = $item;
		}

		foreach ( $grouped as $type => $items ) {
			$chunks = array_chunk( $items, self::RETRY_QUEUE_BATCH_SIZE );

			if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
				error_log( __METHOD__ . ' Retry cron processing ' . count( $items ) . ' URL(s) of type ' . $type );
			}

			foreach ( $chunks as $chunk ) {
				$urls = array_map( function( $item ) {
					return $item->url;
				}, $chunk );

				$output = $this->routes->submit_urls_batch_to_bwt( $siteUrl, $urls, $api_key, $type, false );
				if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
					error_log( __METHOD__ . ' Retry cron batch result: ' . $output . ' for ' . count( $chunk ) . ' URL(s)' );
				}

				if ( $output === 'success' ) {
					// Success: remove from queue, log as passed
					foreach ( $chunk as $item ) {
						BWT_IndexNow_Admin_Utils::delete_retry_item( $item->id );
						$this->routes->update_submission_output( $output, $item->url );
					}
					if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
						error_log( __METHOD__ . ' Retry batch succeeded: ' . count( $chunk ) . ' URLs of type ' . $type );
					}
				} elseif ( $output === 'error:TooManyRequests' ) {
					// Still rate-limited: increment retry count with exponential backoff
					foreach ( $chunk as $item ) {
						$new_count = (int) $item->retry_count + 1;
						if ( $new_count >= (int) $item->max_retries ) {
							// Max retries exceeded: move to failed submissions
							BWT_IndexNow_Admin_Utils::delete_retry_item( $item->id );
							$this->routes->update_submission_output( 'error:TooManyRequests', $item->url );
							if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
								error_log( __METHOD__ . ' Max retries exceeded for: ' . $item->url );
							}
						} else {
							BWT_IndexNow_Admin_Utils::update_retry_item( $item->id, $new_count, WP_IN_Errors::TooManyRequests );
						}
					}
				} else {
					// Other error: increment retry or fail permanently
					foreach ( $chunk as $item ) {
						$new_count = (int) $item->retry_count + 1;
						if ( $new_count >= (int) $item->max_retries ) {
							BWT_IndexNow_Admin_Utils::delete_retry_item( $item->id );
							$this->routes->update_submission_output( $output, $item->url );
						} else {
							$error_msg = substr( $output, 0, 6 ) === 'error:' ? substr( $output, 6 ) : $output;
							BWT_IndexNow_Admin_Utils::update_retry_item( $item->id, $new_count, $error_msg );
						}
					}
				}
			}
		}
	}
	
	/**
	 *  Renders the IndexNow page for path site_url/{apikey}.txt OR site_url/{bwtsiteauthkey}.ttl.
	 *
	 */
	public function check_for_indexnow_page() {
		$admin_api_key = get_option( $this->prefix . "admin_api_key" );
		$api_key       = base64_decode( $admin_api_key );
		
		$admin_bwt_site_auth_key = get_option( $this->prefix . "admin_bwt_site_auth_key" );
		$bwt_site_auth_key       = $admin_bwt_site_auth_key;

		$admin_bwt_site_auth_timestamp  = get_option($this->prefix . "admin_bwt_site_auth_timestamp");
		
		global $wp;
		$current_url = home_url( $wp->request );

		if ( isset( $current_url ) && trailingslashit( get_home_url() ) . $api_key . '.txt' === $current_url ) {
			header( 'Content-Type: text/plain' );
			header( 'X-Robots-Tag: noindex' );
			status_header( 200 );
			esc_html_e($api_key);
			exit();
		}

		if ( isset( $current_url ) && trailingslashit( get_home_url() ) . $admin_bwt_site_auth_key . '.ttl' === $current_url ) {
			header( 'Content-Type: text/plain' );
			header( 'X-Robots-Tag: noindex' );
			status_header( 200 );
			esc_html_e($admin_bwt_site_auth_timestamp);			
			exit();
		}
		
	}
}
