<?php

/**
 * The admin-specific functionality of the plugin.
 * This class contains the routes needed by the UI
 *
 * @package    BWT_IndexNow
 * @subpackage BWT_IndexNow/admin-routes
 * @author     Microsoft Bing <bingwpus@microsoft.com>
*/
class BWT_IndexNow_Admin_Routes {

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

	public static $passed_submissions_table = "indexnow_passed_submissions";

	public static $failed_submissions_table = "indexnow_failed_submissions";

	public static $retry_queue_table = "indexnow_retry_queue";

	/**
	 * Maximum number of URLs allowed in a single IndexNow API urlList request.
	 */
	const URL_LIST_MAX_SIZE = 10000;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.01.01
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
    public function __construct( $plugin_name, $version, $prefix ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->prefix = $prefix;
	}

	// This function registers all the necessary routes needed by the UI
    public function register_routes() {
        $namespace = $this->plugin_name . '/v_' . $this->version;

        $endpoint = '/apiKey/';
		register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => array( $this, 'get_api_key' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
            ),
		) );

		register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::EDITABLE,
                'callback'              => array( $this, 'update_api_key' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
            ),
		) );

		$endpoint = '/apiSettings/';
		register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => array( $this, 'get_api_settings' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
            ),
		) );

		$endpoint = '/automaticSubmission/';
		register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::EDITABLE,
                'callback'              => array( $this, 'update_auto_submit' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
            ),
		) );

		$endpoint = '/submitUrl/';
		register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::EDITABLE,
                'callback'              => array( $this, 'submit_url' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
            ),
		) );

		$endpoint = '/getStats/';
		register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => array( $this, 'get_stats' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
            ),
		) );

		$endpoint = '/allSubmissions/';
		register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => array( $this, 'get_submissions' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
            ),
		) );

		register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::EDITABLE,
                'callback'              => array( $this, 'resubmit_submissions' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
            ),
		) );

		$endpoint = '/getIndexNowInsightsUrl/';
		register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => array( $this, 'get_indexnow_insights_url' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
            ),
		) );

		$endpoint = '/deleteSubmissions/';
		register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => array( $this, 'delete_submissions' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
            ),
		) );

		$endpoint = '/excludedPaths/';
		register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => array( $this, 'get_excluded_paths' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
            ),
		) );

		register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::EDITABLE,
                'callback'              => array( $this, 'update_excluded_paths' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
            ),
		) );
    }

	public function admin_permissions_check( $request ) {
        return current_user_can( "manage_options" );
    }

    public function get_api_key( $request ) {
		return $this->try_catch($request, array($this, 'call_get_api_key'));
	}

	public function update_api_key( $request ) {
		return $this->try_catch($request, array($this, 'call_update_api_key'));
	}

	public function get_api_settings( $request ) {
		return $this->try_catch(array($request, array($this, 'call_get_api_settings')), array($this, 'validate_api_key'));
	}

	public function update_auto_submit( $request ) {
		return $this->try_catch(array($request, array($this, 'call_update_auto_submit')), array($this, 'validate_api_key'));
	}

	public function get_excluded_paths( $request ) {
		return $this->try_catch(array($request, array($this, 'call_get_excluded_paths')), array($this, 'validate_api_key'));
	}

	public function update_excluded_paths( $request ) {
		return $this->try_catch(array($request, array($this, 'call_update_excluded_paths')), array($this, 'validate_api_key'));
	}

	/**
	 *  Submitting the Url
	 */
	public function submit_url($request) {
		return $this->try_catch(array($request, array($this, 'call_submit_url')), array($this, 'validate_api_key'));
	}

	public function get_stats( $request ) {
		return $this->try_catch(array($request, array($this, 'call_get_stats')), array($this, 'validate_api_key'));
	}

	public function get_submissions( $request ) {
		return $this->try_catch(array($request, array($this, 'call_get_submissions')), array($this, 'validate_api_key'));
	}

	public function get_indexnow_insights_url( $request ) {
		return $this->try_catch(array($request, array($this, 'call_get_indexnow_insights_url')), array($this, 'validate_api_key'));
	}

	public function resubmit_submissions( $request ) {
		return $this->try_catch(array($request, array($this, 'call_resubmit_submissions')), array($this, 'validate_api_key'));
	}

	public function delete_submissions( $request ) {
		return $this->try_catch(array($request, array($this, 'call_delete_submissions')), array($this, 'validate_api_key'));
	}

	private function resubmit_single_submission($siteUrl, $api_key, $submission, &$responses) {
		$is_valid_api_key = get_option( $this->prefix . 'is_valid_api_key' );
		$failed_count = get_option( $this->prefix . 'failed_count' );
		$passed_count = get_option( $this->prefix . 'passed_count' );

		$fail_count = null;
		if (is_bool($failed_count)) {
			$fail_count = new IndexNowSubmissionCount();
		}
		else {
			$fail_count = $failed_count;
		}
		$pass_count = null;
		if (is_bool($passed_count)) {
			$pass_count = new IndexNowSubmissionCount();
		}
		else {
			$pass_count = $passed_count;
		}

		if ($is_valid_api_key && $is_valid_api_key === "1") {
			$output = $this->submit_url_to_bwt($siteUrl, $submission->url, $api_key, $submission->type, true);
			if (substr($output, 0, 6) == 'error:') {
				$error = $this->get_api_error(substr($output, 6));
				$response = new IndexNowSubmissionResponse($submission->url, false, $error);
				BWT_IndexNow_Admin_Utils::insert_submission(BWT_IndexNow_Admin_Routes::$failed_submissions_table, new IndexNowSubmissions($submission->url, time(), 0, $submission->type, $error));
				array_push($responses, $response);
				BWT_IndexNow_Admin_Utils::increase_count($fail_count);
				update_option( $this->prefix . 'failed_count', $fail_count );
				return false;
			} else {
				$response = new IndexNowSubmissionResponse($submission->url, true, WP_IN_Errors::Success);
				array_push($responses, $response);
				BWT_IndexNow_Admin_Utils::insert_submission(BWT_IndexNow_Admin_Routes::$passed_submissions_table, new IndexNowSubmissions($submission->url, time(), 1, $submission->type, WP_IN_Errors::Success));
				BWT_IndexNow_Admin_Utils::increase_count($pass_count);
				update_option( $this->prefix . 'passed_count', $passed_count );
				return true;
			}
		}
		else {
			$response = new IndexNowSubmissionResponse($submission->url, false, WP_IN_Errors::InvalidApiKey);
			BWT_IndexNow_Admin_Utils::insert_submission(BWT_IndexNow_Admin_Routes::$failed_submissions_table, new IndexNowSubmissions($submission->url, time(), 0, $submission->type), WP_IN_Errors::InvalidApiKey);
			array_push($responses, $response);
			BWT_IndexNow_Admin_Utils::increase_count($fail_count);
			update_option( $this->prefix . 'failed_count', $fail_count );
			return false;
		}
	}
	
	/**
	 * Removes scheme/protocol from thr url.
	 *
	 */
	private function remove_scheme( $url ) {
		if ( 'http://' === substr( $url, 0, 7 ) ) {
			return substr( $url, 7 );
		}
		if ( 'https://' === substr( $url, 0, 8 ) ) {
			return substr( $url, 8 );
		}
		return $url;
	}

	public function submit_url_to_bwt($site_url, $url, $api_key, $type, $is_manual_submission)
	{
		return $this->submit_urls_batch_to_bwt( $site_url, array( $url ), $api_key, $type, $is_manual_submission );
	}

	/**
	 * Submit a batch of URLs to IndexNow API.
	 * The urlList is capped at URL_LIST_MAX_SIZE (10,000) URLs per request.
	 *
	 * @param string $site_url   The site URL.
	 * @param array  $urls       Array of URLs to submit (max 10,000).
	 * @param string $api_key    The API key.
	 * @param string $type       The submission type (add/update/delete).
	 * @param bool   $is_manual  Whether this is a manual submission.
	 * @return string 'success' or 'error:...' string.
	 */
	public function submit_urls_batch_to_bwt($site_url, $urls, $api_key, $type, $is_manual_submission)
	{
		// Enforce the 10K URL list limit
		if ( count( $urls ) > self::URL_LIST_MAX_SIZE ) {
			$urls = array_slice( $urls, 0, self::URL_LIST_MAX_SIZE );
			if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
				error_log( __METHOD__ . ' URL list truncated to ' . self::URL_LIST_MAX_SIZE . ' URLs' );
			}
		}

		$data = json_encode(
			array(
				'host'         => $this->remove_scheme( $site_url ),
				'key'          => $api_key,
				'keyLocation'  => trailingslashit( $site_url ) . $api_key . '.txt',
				'urlList'      => array_values( $urls ),
			)
		);

		$response = wp_remote_post(
			'https://api.indexnow.org/indexnow/',
			array(
				'body'    => $data,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'X-Source-Info' => 'https://wordpress.com/' . $this->version . '/' . $is_manual_submission
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
				error_log( __METHOD__ . ' error:WP_Error: ' . $response->get_error_message() );
			}
			return 'error:WP_Error';
		}
		if ( isset( $response['errors'] ) ) {
			return 'error:RequestFailed';
		}
		try {
			$code = $response['response']['code'];
			if ( 200 === $code || 202 === $code ) {
				return 'success';
			}
			switch ( $code ) {
				case 400:
					return 'error:InvalidRequest';
				case 403:
					return 'error:InvalidApiKey';
				case 422:
					return 'error:InvalidUrl';
				case 429:
					if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
						error_log( __METHOD__ . ' 429 Too Many Requests - rate limited by IndexNow API' );
					}
					return 'error:TooManyRequests';
				default:
					if ( true === WP_DEBUG && true === WP_DEBUG_LOG ) {
						$body_msg = '';
						if ( ! empty( $response['body'] ) ) {
							$decoded = json_decode( $response['body'] );
							$body_msg = isset( $decoded->message ) ? $decoded->message : $response['body'];
						}
						error_log( __METHOD__ . ' body: ' . $body_msg );
					}
					return 'error: ' . $response['response']['message'];
			}
		} catch ( \Throwable $th ) {
			return 'error:RequestFailed';
		}
	}

	public function update_submission_output($output, $url) {
		$failed_count = get_option( $this->prefix . 'failed_count' );
		$passed_count = get_option( $this->prefix . 'passed_count' );
		if (substr($output, 0, 6) == 'error:') {
			$error_msg = substr($output, 6);
			$error_type = $this->get_api_error($error_msg);
			$failedUrl = new IndexNowSubmissions($url, time(), 0, "add", $error_type);
			BWT_IndexNow_Admin_Utils::insert_submission(BWT_IndexNow_Admin_Routes::$failed_submissions_table, $failedUrl);
			$fail_count = null;
			if (is_bool($failed_count)) {
				$fail_count = new IndexNowSubmissionCount();
			}
			else {
				$fail_count = $failed_count;
			}
			BWT_IndexNow_Admin_Utils::increase_count($fail_count);
			// get the lastest options to avoid inconsistency
			update_option( $this->prefix . 'failed_count', $fail_count );

			return new \WP_REST_Response( array(
				'error' => $error_type
				), 200 );
		} else {
			$passedUrl = new IndexNowSubmissions($url, time(), 1, "add", WP_IN_Errors::Success);
			BWT_IndexNow_Admin_Utils::insert_submission(BWT_IndexNow_Admin_Routes::$passed_submissions_table, $passedUrl);
			$pass_count = null;
			if (is_bool($passed_count)) {
				$pass_count = new IndexNowSubmissionCount();
			}
			else {
				$pass_count = $passed_count;
			}
			BWT_IndexNow_Admin_Utils::increase_count($pass_count);
			// get the lastest options to avoid inconsistency
			update_option( $this->prefix . 'passed_count', $pass_count );

			return new \WP_REST_Response( array(
				'error' => WP_IN_Errors::NoError
				), 200 );
		}
	}

	private function try_catch( $parameters, $function ) {
		try {
			return call_user_func($function, $parameters);
		}
		catch (\Throwable $th) {
			return new \WP_REST_Response( array(
				'hasAPIKey' => false,
				'error' => WP_IN_Errors::InvalidRequest,
				'error_type' => WP_IN_Errors::InvalidRequest
			), 500 );
		}
		catch (\Exception $e) {
			return new \WP_REST_Response( array(
				'hasAPIKey' => false,
				'error' => WP_IN_Errors::InvalidRequest,
				'error_type' => WP_IN_Errors::InvalidRequest
			), 500 );
		}
	}

	private function validate_api_key( $parameters ) {
		$admin_api_key = get_option($this->prefix . "admin_api_key");
		if ($admin_api_key && !empty($admin_api_key)) {
			return call_user_func($parameters[1], $parameters[0], $admin_api_key);
		}
		if (!$admin_api_key) {
			return new \WP_REST_Response( array(
				'error_type' => WP_IN_Errors::ErrorInWpOptions,
				'error' => WP_IN_Errors::ErrorInWpOptions
				), 400 );
		}
		return new \WP_REST_Response( array(
			'error_type' => WP_IN_Errors::ApiKeyNotFound,
			'error' => WP_IN_Errors::ApiKeyNotFound
			), 400 );
	}

	private function call_get_api_key( $request ) {
		$admin_api_key = get_option($this->prefix . "admin_api_key");

		$api_key = base64_decode($admin_api_key);
		$is_valid_api_key = get_option( $this->prefix . 'is_valid_api_key' );
			if ( ! $admin_api_key || empty($admin_api_key) || !$is_valid_api_key || $is_valid_api_key == '2') {
				return new \WP_REST_Response( array(
					'hasAPIKey' => false,
					'APIKey' => $api_key
				), 200 );
			}

			return new \WP_REST_Response( array(
				'hasAPIKey' => true,
				'APIKey' => $api_key
			), 200 );
	}

	private function call_update_api_key( $request ) {
		$body = $request->get_body();
		if (isset($body)) {
			$json = json_decode($body);
			if (isset($json->APIKey) && !empty($json->APIKey)) {
				$apiKey = sanitize_text_field($json->APIKey);
				if (preg_match('/^[a-z0-9]{32}$/i', $json->APIKey)) {
					
					// get the lastest options to avoid inconsistency
					update_option($this->prefix . 'admin_api_key', base64_encode($apiKey));
					update_option($this->prefix . 'is_valid_api_key', "1");
					update_option($this->prefix . 'auto_submission_enabled', "1");
					return new \WP_REST_Response( array(
						'error_type' => WP_IN_Errors::NoError
						), 200 );
					
				}
				else {
					return new \WP_REST_Response( array(
						'error_type' => WP_IN_Errors::InvalidApiKeyFormat
					), 200 );
				}
			}
			// REMOVE LATER
			else if(isset($json->APIKey) && empty($json->APIKey)) {
				// get the lastest options to avoid inconsistency
				update_option($this->prefix . 'admin_api_key', $json->APIKey);
				// php treats "0" as false which makes it difficult to check in option is false or value is false
				update_option($this->prefix . 'is_valid_api_key', "2");
				update_option($this->prefix . 'auto_submission_enabled', "1");
				return new \WP_REST_Response( array(
					'error_type' => WP_IN_Errors::NoError
					), 200 );
			}
		}

		return new \WP_REST_Response( array(
			'error_type' => WP_IN_Errors::InvalidRequest
		), 200 );
	}

	private function call_get_api_settings( $request, $admin_api_key ) {
		$auto_submission_enabled = get_option( $this->prefix . 'auto_submission_enabled' );
		if (!$auto_submission_enabled) {
			update_option( $this->prefix . 'auto_submission_enabled', "1" );
			$auto_submission_enabled = "1";
		}
		$excluded_paths = get_option( $this->prefix . 'excluded_paths', '' );
		$siteUrl = get_home_url();
		return new \WP_REST_Response( array(
			'AutoSubmissionEnabled' => $auto_submission_enabled === "1",
			'ExcludedPaths' => $excluded_paths,
			'SiteUrl' => $siteUrl,
			'error_type' => WP_IN_Errors::NoError
			), 200 );
	}

	private function call_update_auto_submit( $request, $admin_api_key ) {
		$body = $request->get_body();
		if (isset($body)) {
			$json = json_decode($body);
			if (isset($json->AutoSubmissionEnabled)) {
				update_option( $this->prefix . 'auto_submission_enabled', $json->AutoSubmissionEnabled ? "1" : "2" );
				return new \WP_REST_Response( array(
					'error_type' => WP_IN_Errors::NoError
					), 200 );
			}
		}

		return new \WP_REST_Response( array(
			'error_type' => WP_IN_Errors::InvalidRequest
		), 200 );
	}

	private function call_get_excluded_paths( $request, $admin_api_key ) {
		$excluded_paths = get_option( $this->prefix . 'excluded_paths', '' );
		return new \WP_REST_Response( array(
			'ExcludedPaths' => $excluded_paths,
			'error_type' => WP_IN_Errors::NoError
		), 200 );
	}

	private function call_update_excluded_paths( $request, $admin_api_key ) {
		$max_excluded_paths = 20; // Maximum number of excluded paths allowed
		$body = $request->get_body();
		if (isset($body)) {
			$json = json_decode($body);
			if (isset($json->ExcludedPaths)) {
				// Sanitize each line of the excluded paths
				$paths = sanitize_textarea_field($json->ExcludedPaths);
				
				// Enforce maximum limit
				$path_lines = array_filter(array_map('trim', explode("\n", $paths)));
				if (count($path_lines) > $max_excluded_paths) {
					return new \WP_REST_Response( array(
						'error_type' => 'MaxPathsExceeded',
						'error_message' => "Maximum of {$max_excluded_paths} excluded paths allowed."
					), 200 );
				}
				
				update_option( $this->prefix . 'excluded_paths', $paths );
				return new \WP_REST_Response( array(
					'error_type' => WP_IN_Errors::NoError
				), 200 );
			}
		}

		return new \WP_REST_Response( array(
			'error_type' => WP_IN_Errors::InvalidRequest
		), 200 );
	}

	private function call_submit_url( $request, $admin_api_key ) {
		$api_key = base64_decode(($admin_api_key));
		$is_valid_api_key = get_option( $this->prefix . 'is_valid_api_key' );
		$body = $request->get_body();
		if (isset($body)) {
			$json = json_decode($body);
			if (isset($json->url) && !empty($json->url)) {
				$url = sanitize_text_field($json->url);
				if (empty($url) || !preg_match('/^(https?:\/\/([-\w\.]+)+(:\d+)?(\/([-\w\/_\.]*(\?\S+)?)?)?)$/i', $url, $matches)) {
					return new \WP_REST_Response( array(
						'error' => WP_IN_Errors::InvalidInputUrl
						), 200 );
				} else {
					if ($is_valid_api_key && $is_valid_api_key === "1") {
						$parsedUrl = wp_parse_url($url);
						$siteUrl = get_home_url();
						$output = $this->submit_url_to_bwt($siteUrl, $url, $api_key, "add", true);
						return $this->update_submission_output($output, $url);
					}
					return new \WP_REST_Response( array(
						'error' => WP_IN_Errors::InvalidApiKey
						), 200 );
				}
			}
			return new \WP_REST_Response( array(
				'error' => WP_IN_Errors::EmptyUrl
				), 200 );
		}
	}

	private function call_get_stats( $request, $admin_api_key ) {
		$failed_count = get_option( $this->prefix . 'failed_count' );
		$passed_count = get_option( $this->prefix . 'passed_count' );
		$is_valid_api_key = get_option( $this->prefix . 'is_valid_api_key' );
		// check if we have failed submissions
		if (is_bool($failed_count)) {
			$failed_count = new IndexNowSubmissionCount();
		}
		// check if we have passed submissions
		if (is_bool($passed_count)) {
			$passed_count = new IndexNowSubmissionCount();
		}
		$pass_count = BWT_IndexNow_Admin_Utils::get_count($passed_count);
		$fail_count = BWT_IndexNow_Admin_Utils::get_count($failed_count);
		// save the options, incase they got updated
		update_option( $this->prefix . 'failed_count', $failed_count );
		update_option( $this->prefix . 'passed_count', $passed_count );

		return new \WP_REST_Response( array(
			'FailedSubmissionCount' => $fail_count,
			'PassedSubmissionCount' => $pass_count,
			'error_type' => WP_IN_Errors::NoError
			), 200 );
	}

	private function call_get_submissions( $request, $admin_api_key ) {
		$passed_submissions = BWT_IndexNow_Admin_Utils::get_submissions(BWT_IndexNow_Admin_Routes::$passed_submissions_table);
		$failed_submissions = BWT_IndexNow_Admin_Utils::get_submissions(BWT_IndexNow_Admin_Routes::$failed_submissions_table);
		$submissions = array_merge($failed_submissions, $passed_submissions);
		usort($submissions, function ($a, $b) {
			return $a->submission_date > $b->submission_date;
		});

		return new \WP_REST_Response( array(
			'Submissions' => $submissions,
			'error_type' => WP_IN_Errors::NoError
			), 200 );
	}

	private function call_get_indexnow_insights_url( $request, $admin_api_key ) {
		
		$bwt_site_auth_key = wp_generate_uuid4();
		$updated = update_option( $this->prefix . 'admin_bwt_site_auth_key', $bwt_site_auth_key );
		if ($updated == false) 
		{
			return new \WP_REST_Response( array(
				'InsightsUrl' => "",
				'error_type' => WP_IN_Errors::InvalidRequest
				), 200 );
		}
		$currenttimestamp = time();
		$updated = update_option( $this->prefix . 'admin_bwt_site_auth_timestamp', $currenttimestamp );
		if ($updated == false) 
		{
			return new \WP_REST_Response( array(
				'InsightsUrl' => "",
				'error_type' => WP_IN_Errors::InvalidRequest
				), 200 );
		}

		$siteUrl = get_home_url();
		$insightsurl = "https://bing.com/webmasters/indexnow?siteUrl=" . $siteUrl . "&itoken=" . base64_encode($bwt_site_auth_key);
		
		return new \WP_REST_Response( array(
			'InsightsUrl' => $insightsurl,
			'error_type' => WP_IN_Errors::NoError
			), 200 );
	}

	private function call_resubmit_submissions( $request, $admin_api_key ) {
		$api_key = base64_decode(($admin_api_key));
		$body = $request->get_body();
		if (isset($body)) {
			$json = json_decode($body);
			if (isset($json->Submissions) && count($json->Submissions) > 0) {
				$responses = array();
				$siteUrl = get_home_url();

				$submissions = $json->Submissions;
				$has_error = false;

				foreach ($submissions as $submission) {
					$has_error = $has_error || !$this->resubmit_single_submission($siteUrl, $api_key, $submission, $responses);
				}
				if (count($responses) == 0) {
					return new \WP_REST_Response( array(
						'error_type' => WP_IN_Errors::InvalidOrNoUrls
						), 400 );
				}
				return new \WP_REST_Response( array(
					'hasError' => $has_error,
					'SubmissionErrors' => $responses,
					'error_type' => WP_IN_Errors::NoError
					), 200 );
			}
			return new \WP_REST_Response( array(
				'error_type' => WP_IN_Errors::InvalidOrNoUrls
				), 400 );
		}
	}

	private function call_delete_submissions( $request, $admin_api_key ) {
		BWT_IndexNow_Admin_Utils::delete_submissions(BWT_IndexNow_Admin_Routes::$failed_submissions_table);
		BWT_IndexNow_Admin_Utils::delete_submissions(BWT_IndexNow_Admin_Routes::$passed_submissions_table);

		return new \WP_REST_Response( array(
			'FailedSubmissions' => array(),
			'PassedSubmissions' => array(),
			'error_type' => WP_IN_Errors::NoError
			), 200 );
	}

	private function get_api_error($message, $isSite = false) {
		switch ($message) {

			case 'RequestFailed' : return WP_IN_Errors::WP_RequestFailed;
			case 'Not Found' : return WP_IN_Errors::BWT_InvalidApiCall;
			case 'InternalError' : return WP_IN_Errors::BWT_InternalError;
			case 'UnknownError' : return WP_IN_Errors::BWT_UnknownError;
			case 'InvalidApiKey' : return WP_IN_Errors::BWT_InvalidApiKey;
			case 'InvalidUrl' : return WP_IN_Errors::BWT_InvalidUrl;
			case 'InvalidParameter' : return WP_IN_Errors::BWT_InvalidParameter;
			case 'NotAllowed' : return WP_IN_Errors::BWT_NotAllowed;
			case 'InvalidRequest' : return WP_IN_Errors::InvalidRequest;
			case 'TooManyRequests' : return WP_IN_Errors::TooManyRequests;
			default : return $this->get_custom_api_error($message);
		}
	}

	private function get_custom_api_error($error) {
		if (stripos($error, "Invalid Urls") !== false) {
			return WP_IN_Errors::BWT_InvalidUrl;
		}
		else if (stripos($error, "null") !== false) {
			return WP_IN_Errors::BWT_NullException;
		}
		else {
			return $error;
		}
	}
}

class IndexNowSubmissions {
	public function __construct($url, $submission_date, $submission_type, $type, $error = WP_IN_Errors::Success) {
		$this->url = $url;
		$this->submission_type = $submission_type;
		$this->submission_date = $submission_date;
		$this->error = $error;
		$this->type = $type;
	}

	public $url;
	public $submission_date;
	public $submission_type;
	public $error;
	public $type;
}

class IndexNowSubmissionResponse {
	public function __construct($url, $isSubmitted, $error_msg = "") {
		$this->url = $url;
		$this->isSubmitted = $isSubmitted;
		$this->error_msg = $error_msg;
	}
	public $url;
	public $isSubmitted;
	public $error_msg;
}

// The list of potential erorr. All may not be used.
class WP_IN_Errors {
	const __default = self::Success;

	const NoError = "";
	const Success = "Success";
	const InvalidApiKeyFormat = "Invalid API Key Format";
	const InvalidRequest = "Invalid Request";
	const ErrorInWpOptions = "Error In Fetching WordPress Data";
	const ApiKeyNotFound = "API Key Not Found";
	const InvalidInputUrl = "Invalid Input URL";
	const InvalidApiKey = "Invalid API Key";
	const InvalidOrNoUrls = "Invalid/Missing URLs";
	const NotVerified = "Not Verified";
	const EmptyUrl = "Empty URL";
	const WP_RequestFailed = "Request Failed";
	const BWT_InternalError = "Internal Server Error";
	const BWT_UnknownError = "Unknown Error";
	const BWT_InvalidApiKey = "Invalid API Key";
	const BWT_InvalidUrl = "Invalid Url";
	const BWT_InvalidParameter = "Invalid Parameter";
	const BWT_NotAllowed = "Not Allowed";
	const BWT_NullException =  "Null Value Found";
	const BWT_InvalidApiCall = "Invalid API Call";
	const TooManyRequests = "Too Many Requests (429)";
	const OtherError = "Unknown Error Occured";
}
