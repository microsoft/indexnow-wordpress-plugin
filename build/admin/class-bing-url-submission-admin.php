<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Bing_Webmaster
 * @subpackage Bing_Webmaster/admin
 * @author     Bing Webmaster <bingwpus@microsoft.com>
 */
class Bing_Webmaster_Admin {

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

	private $routes;
	
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.01.01
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/utils/class-bing-url-submission-admin-utils.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/utils/class-bing-url-submission-admin-routes.php';
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->routes = new Bing_Webmaster_Admin_Routes($this->plugin_name, $this->version, $this->prefix);

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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/bing-url-submission-admin.js', array( 'jquery' ), $this->version, false );

		$JSfiles = scandir(dirname(__FILE__) . '/../static/js/');
		   $react_js_to_load = '';
		   foreach($JSfiles as $filename) {
			   if(strpos($filename,'.js')&&strpos($filename,'.js')+3 === strlen($filename)) {
				   $react_js_to_load = plugin_dir_url( __FILE__ ) . '../static/js/' . $filename;
				   wp_enqueue_script($filename, $react_js_to_load, '', mt_rand(10,1000), true);
			   }
		}

		wp_localize_script( $this->plugin_name, 'wpr_object', array(
			'api_nonce'   => wp_create_nonce( 'wp_rest' ),
			'api_url'	  => rest_url( $this->plugin_name . '/v_' . $this->version .'/' ),
			)
		);

	}

	public function register_routes()
	{
		$this->routes->register_routes();
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    0.01.01
	 */
	public function add_plugin_admin_menu()
	{
		/*
		 * Add a menu page for this plugin.
		 */
		add_menu_page(
			__('Bing Webmaster Tools Url Submission', $this->plugin_name),
			__('Bing Webmaster', $this->plugin_name),
			'manage_options',
			$this->plugin_name,
			array($this, 'display_plugin_admin_page'),
			'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNC45MiIgaGVpZ2h0PSIyMS4zMjgiIHZpZXdCb3g9IjAgMCAxNC45MiAyMS4zMjgiPg0KICAgIDxnIGZpbGw9IiNmZmYiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDMxMS41IC03OTYuMTY5KSI+DQogICAgICAgIDxwYXRoIGQ9Ik0tMzExLjUsNzk2LjE2OWw0LjI2MSwxLjV2MTVsNi0zLjQ2NC0yLjk0Mi0xLjM4LTEuODU2LTQuNjIsOS40NTYsMy4zMjJ2NC44M2wtMTAuNjU2LDYuMTQ2LTQuMjYzLTIuMzcxWiI+PC9wYXRoPg0KICAgIDwvZz4NCjwvc3ZnPg=='
		);
	}

	/**
	 * Render the admin page for this plugin.
	 *
	 * @since    0.01.01
	 */
	public function display_plugin_admin_page()
	{
		include_once('partials/bing-url-submission-admin-react.php');
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
			'<a href="' . admin_url('admin.php?page=' . $this->plugin_name) . '">' . __('Settings', $this->plugin_name) . '</a>',
		);
		return array_merge($settings_link, $links);
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

				$siteUrl = get_home_url();

				// check if same url was submitted recently(within a minute)
				if ($new_status != 'trash' && Bing_Webmaster_Admin_Utils::url_submitted_within_last_minute(Bing_Webmaster_Admin_Routes::$passed_submissions_table, $link)) {
					return;
				}
				$api_key = base64_decode($admin_api_key);
				$output = $this->routes->submit_url_to_bwt($siteUrl, $link, $api_key, $type, false);
				$this->routes->update_submission_output($output, $link);
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

}
