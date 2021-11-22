<?php

/**
 * The admin-specific functionality of the plugin.
 * This class contains the routes needed by the UI
 *
 * @package    Bing_Webmaster
 * @subpackage Bing_Webmaster/admin-routes
 * @author     Bing Webmaster <bingwpus@microsoft.com>
 */
class Bing_Webmaster_Admin_Routes {

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

	private $prefix = "bwt-";

	public static $passed_submissions_table = "bwt_passed_submissions";

	public static $failed_submissions_table = "bwt_failed_submissions";

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

		$endpoint = '/apiKeyValidity/';
		register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => array( $this, 'check_api_key_validity' ),
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

		$endpoint = '/deleteSubmissions/';
		register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => array( $this, 'delete_submissions' ),
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

	public function check_api_key_validity( $request ) {
		return $this->try_catch(array($request, array($this, 'call_check_api_key_validity')), array($this, 'validate_api_key'));
	}

	public function get_api_settings( $request ) {
		return $this->try_catch(array($request, array($this, 'call_get_api_settings')), array($this, 'validate_api_key'));
	}

	public function update_auto_submit( $request ) {
		return $this->try_catch(array($request, array($this, 'call_update_auto_submit')), array($this, 'validate_api_key'));
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

	public function resubmit_submissions( $request ) {
		return $this->try_catch(array($request, array($this, 'call_resubmit_submissions')), array($this, 'validate_api_key'));
	}

	public function delete_submissions( $request ) {
		return $this->try_catch(array($request, array($this, 'call_delete_submissions')), array($this, 'validate_api_key'));
	}

	private function get_site_quota($api_key, $siteUrl)
	{
		$response = wp_remote_get( "https://www.bing.com/webmaster/api.svc/json/GetUrlSubmissionQuota?apikey=" . $api_key . "&siteUrl=" . $siteUrl . "&client=wp_v_" . $this->version );

		if (is_wp_error( $response )) {
			if ( true === WP_DEBUG && true === WP_DEBUG_LOG) {
				error_log(__METHOD__ . " error:WP_Error: ".$response->get_error_message()) ;
			}
			return -1;
		}
		if (isset($response['errors'])) {
			return -1;
		}
		try {
			if ($response['response']['code'] === 200) {
				$message = json_decode($response['body'])->{'d'}->{'DailyQuota'};
				return $message;
			} else {
				return -1;
			}
		} catch (\Throwable $th) {
			return -1;
		}
	}

	private function check_bwt_api_key( $api_key ) {
		$siteUrl = get_home_url();
		$data = "{\n\t\"siteUrl\":\"".$siteUrl."\"}";
		$response = wp_remote_get( "https://www.bing.com/webmaster/api.svc/json/CheckSiteVerification?apikey=" . $api_key . "&client=wp_v_" . $this->version . "&siteUrl=" . $siteUrl);

		if (is_wp_error( $response )) {
			if ( true === WP_DEBUG && true === WP_DEBUG_LOG) {
			    error_log(__METHOD__ . " error:WP_Error: ".$response->get_error_message()) ;
			}
			return "error:WP_Error";
		}
		if (isset($response['errors'])) {
			return "error:RequestFailed";
		}
		try {
			if ($response['response']['code'] === 200) {
				$is_verified = json_decode($response['body'])->{'d'};
				if (is_bool($is_verified) && $is_verified) {
					return "success";
				}
				else {
					return "error:NotVerified";
				}
			} else {
				if ($response['response']['code'] >= 500 || $response['response']['code'] === 404) {
					return "error:" . $response['response']['message'];
				} else {
					$message = json_decode($response['body'])->{'Message'};
					return "error:" . $message;
				}
			}
		}
		catch (\Throwable $th) {
			return "error:RequestFailed";
		}
	}

	private function resubmit_single_submission($siteUrl, $api_key, $submission, &$responses) {
		$is_valid_api_key = get_option( $this->prefix . 'is_valid_api_key' );
		$failed_count = get_option( $this->prefix . 'failed_count' );
		$passed_count = get_option( $this->prefix . 'passed_count' );

		$fail_count = null;
		if (is_bool($failed_count)) {
			$fail_count = new SubmissionCount();
		}
		else {
			$fail_count = $failed_count;
		}
		$pass_count = null;
		if (is_bool($passed_count)) {
			$pass_count = new SubmissionCount();
		}
		else {
			$pass_count = $passed_count;
		}

		if ($is_valid_api_key && $is_valid_api_key === "1") {
			$output = $this->submit_url_to_bwt($siteUrl, $submission->url, $api_key, $submission->type, true);
			if (substr($output, 0, 6) == 'error:') {
				$error = $this->get_api_error(substr($output, 6));
				$response = new SubmissionResponse($submission->url, false, $error);
				Bing_Webmaster_Admin_Utils::insert_submission(Bing_Webmaster_Admin_Routes::$failed_submissions_table, new Submissions($submission->url, time(), 0, $submission->type, $error));
				array_push($responses, $response);
				Bing_Webmaster_Admin_Utils::increase_count($fail_count);
				update_option( $this->prefix . 'failed_count', $fail_count );
				return false;
			} else {
				$response = new SubmissionResponse($submission->url, true, WPErrors::Success);
				array_push($responses, $response);
				Bing_Webmaster_Admin_Utils::insert_submission(Bing_Webmaster_Admin_Routes::$passed_submissions_table, new Submissions($submission->url, time(), 1, $submission->type, WPErrors::Success));
				Bing_Webmaster_Admin_Utils::increase_count($pass_count);
				update_option( $this->prefix . 'passed_count', $passed_count );
				return true;
			}
		}
		else {
			$response = new SubmissionResponse($submission->url, false, WPErrors::InvalidApiKey);
			Bing_Webmaster_Admin_Utils::insert_submission(Bing_Webmaster_Admin_Routes::$failed_submissions_table, new Submissions($submission->url, time(), 0, $submission->type), WPErrors::InvalidApiKey);
			array_push($responses, $response);
			Bing_Webmaster_Admin_Utils::increase_count($fail_count);
			update_option( $this->prefix . 'failed_count', $fail_count );
			return false;
		}
	}

	public function submit_url_to_bwt($siteUrl, $url, $api_key, $type, $is_manual_submission)
	{
		$data = "{\n\t\"siteUrl\":\"".$siteUrl."\",\n\"url\":\"".$url."\"\n}";
		$response = wp_remote_post( "https://www.bing.com/webmaster/api.svc/json/WPSubmitUrl?apikey=" . $api_key . "&auto=" . ($is_manual_submission ? "0" : "1") . "&type=" . $type . "&client=wp_v_" . $this->version . "&siteUrl=" . $siteUrl, array( 'body' => $data,
			'headers' => array( 'Content-Type' => 'application/json') ) );

		if (is_wp_error( $response )) {
			if ( true === WP_DEBUG && true === WP_DEBUG_LOG) {
			    error_log(__METHOD__ . " error:WP_Error: ".$response->get_error_message()) ;
			}
			return "error:WP_Error";
		}
		if (isset($response['errors'])) {
			return "error:RequestFailed";
		}
		try {
			if ($response['response']['code'] === 200) {
				return "success";
			} else {
				if ($response['response']['code'] >= 500) {
					return "error:" . $response['response']['message'];
				} else {
					$message = json_decode($response['body'])->{'Message'};
					return "error:" . $message;
				}
			}
		}
		catch (\Throwable $th) {
			return "error:RequestFailed";
		}
	}

	public function update_submission_output($output, $url) {
		$failed_count = get_option( $this->prefix . 'failed_count' );
		$passed_count = get_option( $this->prefix . 'passed_count' );
		if (substr($output, 0, 6) == 'error:') {
			$error_msg = substr($output, 6);
			$error_type = $this->get_api_error($error_msg);
			$failedUrl = new Submissions($url, time(), 0, "add", $error_type);
			Bing_Webmaster_Admin_Utils::insert_submission(Bing_Webmaster_Admin_Routes::$failed_submissions_table, $failedUrl);
			$fail_count = null;
			if (is_bool($failed_count)) {
				$fail_count = new SubmissionCount();
			}
			else {
				$fail_count = $failed_count;
			}
			Bing_Webmaster_Admin_Utils::increase_count($fail_count);
			// get the lastest options to avoid inconsistency
			update_option( $this->prefix . 'failed_count', $fail_count );

			return new \WP_REST_Response( array(
				'error' => $error_type
				), 200 );
		} else {
			$passedUrl = new Submissions($url, time(), 1, "add", WPErrors::Success);
			Bing_Webmaster_Admin_Utils::insert_submission(Bing_Webmaster_Admin_Routes::$passed_submissions_table, $passedUrl);
			$pass_count = null;
			if (is_bool($passed_count)) {
				$pass_count = new SubmissionCount();
			}
			else {
				$pass_count = $passed_count;
			}
			Bing_Webmaster_Admin_Utils::increase_count($pass_count);
			// get the lastest options to avoid inconsistency
			update_option( $this->prefix . 'passed_count', $pass_count );

			return new \WP_REST_Response( array(
				'error' => WPErrors::NoError
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
				'error' => WPErrors::InvalidRequest,
				'error_type' => WPErrors::InvalidRequest
			), 500 );
		}
		catch (\Exception $e) {
			return new \WP_REST_Response( array(
				'hasAPIKey' => false,
				'error' => WPErrors::InvalidRequest,
				'error_type' => WPErrors::InvalidRequest
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
				'error_type' => WPErrors::ErrorInWpOptions,
				'error' => WPErrors::ErrorInWpOptions
				), 400 );
		}
		return new \WP_REST_Response( array(
			'error_type' => WPErrors::ApiKeyNotFound,
			'error' => WPErrors::ApiKeyNotFound
			), 400 );
	}

	private function call_get_api_key( $request ) {
		$admin_api_key = get_option($this->prefix . "admin_api_key");

			if ( ! $admin_api_key || empty($admin_api_key)) {
				return new \WP_REST_Response( array(
					'hasAPIKey' => false
				), 200 );
			}

			return new \WP_REST_Response( array(
				'hasAPIKey' => true
			), 200 );
	}

	private function call_update_api_key( $request ) {
		$body = $request->get_body();
		if (isset($body)) {
			$json = json_decode($body);
			if (isset($json->APIKey) && !empty($json->APIKey)) {
				$apiKey = sanitize_text_field($json->APIKey);
				if (preg_match('/^[a-f0-9]{32}$/i', $json->APIKey)) {
					$response = $this->check_bwt_api_key($apiKey);

					if (substr($response, 0, 6) != "error:") {
						// get the lastest options to avoid inconsistency
						update_option($this->prefix . 'admin_api_key', base64_encode($apiKey));
						update_option($this->prefix . 'is_valid_api_key', "1");
						update_option($this->prefix . 'auto_submission_enabled', "1");
						return new \WP_REST_Response( array(
							'error_type' => WPErrors::NoError
							), 200 );
					}
					else {
						$message = substr($response, 6);
						$error_type = $this->get_api_error($message, true);
						return new \WP_REST_Response( array(
							'error_type' => $error_type
							), 200 );
					}
				}
				else {
					return new \WP_REST_Response( array(
						'error_type' => WPErrors::InvalidApiKeyFormat
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
					'error_type' => WPErrors::NoError
					), 200 );
			}
		}

		return new \WP_REST_Response( array(
			'error_type' => WPErrors::InvalidRequest
		), 200 );
	}

	private function call_check_api_key_validity( $request, $admin_api_key ) {
		$api_key = base64_decode($admin_api_key);
		$is_valid_api_key = get_option( $this->prefix . 'is_valid_api_key' );
		$response = $this->check_bwt_api_key($api_key);

		if (substr($response, 0, 6) != "error:") {
			if (!$is_valid_api_key || $is_valid_api_key === "2") {
				// get the lastest options to avoid inconsistency
				update_option( $this->prefix . 'is_valid_api_key', true );
			}
			return new \WP_REST_Response( array(
				'error_type' => WPErrors::NoError
				), 200 );
		}
		else {
			$message = substr($response, 6);
			$error_type = $this->get_api_error($message);
			// get the lastest options to avoid inconsistency
			update_option( $this->prefix . 'is_valid_api_key', "2" );
			return new \WP_REST_Response( array(
				'error_type' => $error_type
				), 200 );
		}
	}

	private function call_get_api_settings( $request, $admin_api_key ) {
		$auto_submission_enabled = get_option( $this->prefix . 'auto_submission_enabled' );
		if (!$auto_submission_enabled) {
			update_option( $this->prefix . 'auto_submission_enabled', "1" );
			$auto_submission_enabled = "1";
		}
		$siteUrl = get_home_url();
		return new \WP_REST_Response( array(
			'AutoSubmissionEnabled' => $auto_submission_enabled === "1",
			'SiteUrl' => $siteUrl,
			'error_type' => WPErrors::NoError
			), 200 );
	}

	private function call_update_auto_submit( $request, $admin_api_key ) {
		$body = $request->get_body();
		if (isset($body)) {
			$json = json_decode($body);
			if (isset($json->AutoSubmissionEnabled)) {
				update_option( $this->prefix . 'auto_submission_enabled', $json->AutoSubmissionEnabled ? "1" : "2" );
				return new \WP_REST_Response( array(
					'error_type' => WPErrors::NoError
					), 200 );
			}
		}

		return new \WP_REST_Response( array(
			'error_type' => WPErrors::InvalidRequest
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
						'error' => WPErrors::InvalidInputUrl
						), 200 );
				} else {
					if ($is_valid_api_key && $is_valid_api_key === "1") {
						$parsedUrl = wp_parse_url($url);
						$siteUrl = get_home_url();
						$output = $this->submit_url_to_bwt($siteUrl, $url, $api_key, "add", true);
						return $this->update_submission_output($output, $url);
					}
					return new \WP_REST_Response( array(
						'error' => WPErrors::InvalidApiKey
						), 200 );
				}
			}
			return new \WP_REST_Response( array(
				'error' => WPErrors::EmptyUrl
				), 200 );
		}
	}

	private function call_get_stats( $request, $admin_api_key ) {
		$failed_count = get_option( $this->prefix . 'failed_count' );
		$passed_count = get_option( $this->prefix . 'passed_count' );
		$is_valid_api_key = get_option( $this->prefix . 'is_valid_api_key' );
		// check if we have failed submissions
		if (is_bool($failed_count)) {
			$failed_count = new SubmissionCount();
		}
		// check if we have passed submissions
		if (is_bool($passed_count)) {
			$passed_count = new SubmissionCount();
		}
		$pass_count = Bing_Webmaster_Admin_Utils::get_count($passed_count);
		$fail_count = Bing_Webmaster_Admin_Utils::get_count($failed_count);
		// save the options, incase they got updated
		update_option( $this->prefix . 'failed_count', $failed_count );
		update_option( $this->prefix . 'passed_count', $passed_count );
		$quota = -1;
		if ($is_valid_api_key && $is_valid_api_key === "1") {
			$siteUrl = get_home_url();
			$quota = $this->get_site_quota(base64_decode($admin_api_key), $siteUrl);
		}
		return new \WP_REST_Response( array(
			'FailedSubmissionCount' => $fail_count,
			'PassedSubmissionCount' => $pass_count,
			'Quota' => $quota,
			'error_type' => WPErrors::NoError
			), 200 );
	}

	private function call_get_submissions( $request, $admin_api_key ) {
		$passed_submissions = Bing_Webmaster_Admin_Utils::get_submissions(Bing_Webmaster_Admin_Routes::$passed_submissions_table);
		$failed_submissions = Bing_Webmaster_Admin_Utils::get_submissions(Bing_Webmaster_Admin_Routes::$failed_submissions_table);
		$submissions = array_merge($failed_submissions, $passed_submissions);
		usort($submissions, function ($a, $b) {
			return $a->submission_date > $b->submission_date;
		});

		return new \WP_REST_Response( array(
			'Submissions' => $submissions,
			'error_type' => WPErrors::NoError
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
						'error_type' => WPErrors::InvalidOrNoUrls
						), 400 );
				}
				return new \WP_REST_Response( array(
					'hasError' => $has_error,
					'SubmissionErrors' => $responses,
					'error_type' => WPErrors::NoError
					), 200 );
			}
			return new \WP_REST_Response( array(
				'error_type' => WPErrors::InvalidOrNoUrls
				), 400 );
		}
	}

	private function call_delete_submissions( $request, $admin_api_key ) {
		Bing_Webmaster_Admin_Utils::delete_submissions(Bing_Webmaster_Admin_Routes::$failed_submissions_table);
		Bing_Webmaster_Admin_Utils::delete_submissions(Bing_Webmaster_Admin_Routes::$passed_submissions_table);

		return new \WP_REST_Response( array(
			'FailedSubmissions' => array(),
			'PassedSubmissions' => array(),
			'error_type' => WPErrors::NoError
			), 200 );
	}

	private function get_api_error($message, $isSite = false) {
		switch ($message) {
			case 'RequestFailed' : return WPErrors::WP_RequestFailed;
			case 'NotVerified' : return WPErrors::NotVerified;
			case 'Not Found' : return WPErrors::BWT_InvalidApiCall;
			default : break;
		}
		if (strlen($message) < 9) {
			return WPErrors::OtherError;
		}
		$error = substr($message, 9);
		switch ($error) {
			case 'InternalError' : return WPErrors::BWT_InternalError;
			case 'UnknownError' : return WPErrors::BWT_UnknownError;
			case 'InvalidApiKey' : return WPErrors::BWT_InvalidApiKey;
			case 'ThrottleUser' : return WPErrors::BWT_ThrottleUser;
			case 'ThrottleHost' : return WPErrors::BWT_ThrottleHost;
			case 'UserBlocked' : return WPErrors::BWT_UserBlocked;
			case 'InvalidUrl' : return WPErrors::BWT_InvalidUrl;
			case 'InvalidParameter' : return WPErrors::BWT_InvalidParameter;
			case 'UserNotFound' : return WPErrors::BWT_UserNotFound;
			case 'NotFound' : return WPErrors::BWT_NotFound;
			case 'NotAllowed' : return WPErrors::BWT_NotAllowed;
			case 'NotAuthorized' : return WPErrors::BWT_NotAuthorized;
			case 'ThrottleIP' : return WPErrors::BWT_ThrottleIP;
			case 'InvalidToken' : return WPErrors::BWT_InvalidToken;
			case 'SiteUriSchemeIsNotSupported' : return WPErrors::BWT_SiteUriSchemeIsNotSupported;
			case 'AuthorizationFailed' : return $isSite ? WPErrors::BWT_AuthorizationFailed_Site : WPErrors::BWT_AuthorizationFailed_Url;
			default : return $this->get_custom_api_error($error);
		}
	}

	private function get_custom_api_error($error) {
		if (stripos($error, "Invalid Urls") !== false) {
			return WPErrors::BWT_InvalidUrl;
		}
		else if (stripos($error, "null") !== false) {
			return WPErrors::BWT_NullException;
		}
		else if (stripos($error, "exceeded") !== false) {
			return WPErrors::BWT_QuotaFull;
		}
		else {
			return $error;
		}
	}
}

class Submissions {
	public function __construct($url, $submission_date, $submission_type, $type, $error = WPErrors::Success) {
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

class SubmissionResponse {
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
class WPErrors {
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
	const BWT_ThrottleUser = "User Throttled";
	const BWT_ThrottleHost = "Host Throttled";
	const BWT_UserBlocked = "User Blocked";
	const BWT_InvalidUrl = "Invalid Url";
	const BWT_InvalidParameter = "Invalid Parameter";
	const BWT_UserNotFound = "User Not Found";
	const BWT_NotFound = "Not Found";
	const BWT_NotAllowed = "Not Allowed";
	const BWT_NotAuthorized = "Not Authorized";
	const BWT_ThrottleIP = "IP Throttled";
	const BWT_InvalidToken = "Invalid Token";
	const BWT_SiteUriSchemeIsNotSupported = "Site Uri Scheme Is Not Supported";
	const BWT_AuthorizationFailed_Site = "Site Not Registered/Verified In Bing Webmaster";
	const BWT_AuthorizationFailed_Url = "URL Doesn't Belong To The Site";
	const BWT_AuthorizationFailed = "Authorization Failed";
	const BWT_NullException =  "Null Value Found";
	const BWT_QuotaFull = "Quota Exceeded";
	const BWT_InvalidApiCall = "Invalid API Call";
	const OtherError = "Unknown Error Occured";
}
