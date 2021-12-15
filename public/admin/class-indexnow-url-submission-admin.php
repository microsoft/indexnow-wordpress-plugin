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
			__('IndexNow Plugin', $this->plugin_name),
			__('IndexNow', $this->plugin_name),
			'manage_options',
			$this->plugin_name,
			array($this, 'display_plugin_admin_page'),
			'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzQiIGhlaWdodD0iMzYiIHZpZXdCb3g9IjAgMCAzNCAzNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE2LjkyMjUgMEMxOC4yNDc4IDAuMDE1MzgxOCAxOS41Njc5IDAuMTY5NTY0IDIwLjg2MTUgMC40NjAwNzNDMjEuNDI2MiAwLjU4Njg5NSAyMS44NDg0IDEuMDYwOTIgMjEuOTEyNiAxLjYzOTkzTDIyLjIxOTkgNC40MTYwMkMyMi4zNTg5IDUuNjkwNjUgMjMuNDI3NiA2LjY1NjA3IDI0LjcwMTggNi42NTc0MkMyNS4wNDQzIDYuNjU3OTYgMjUuMzgzIDYuNTg2MTUgMjUuNjk5NSA2LjQ0NTE0TDI4LjIyODQgNS4zMjY0N0MyOC43NTQzIDUuMDkzOCAyOS4zNjg4IDUuMjIwNjUgMjkuNzYxNSA1LjY0Mjk1QzMxLjU4OSA3LjYwODQ0IDMyLjk1MDEgOS45NjU3NSAzMy43NDI0IDEyLjUzNzVDMzMuOTEzMiAxMy4wOTIgMzMuNzE2NiAxMy42OTQ2IDMzLjI1MjMgMTQuMDM5MUwzMS4wMTA5IDE1LjcwMjlDMzAuMzcxNCAxNi4xNzYgMjkuOTkzOSAxNi45MjczIDI5Ljk5MzkgMTcuNzI2NEMyOS45OTM5IDE4LjUyNTIgMzAuMzcxNCAxOS4yNzY1IDMxLjAxMjIgMTkuNzUwN0wzMy4yNTU4IDIxLjQxNTFDMzMuNzIgMjEuNzU5NSAzMy45MTY4IDIyLjM2MjIgMzMuNzQ2IDIyLjkxNjlDMzIuOTU0IDI1LjQ4ODEgMzEuNTkzNyAyNy44NDUyIDI5Ljc2NzIgMjkuODExMUMyOS4zNzQ5IDMwLjIzMzIgMjguNzYwOCAzMC4zNjA2IDI4LjIzNSAzMC4xMjg0TDI1LjY5NTcgMjkuMDA4MUMyNC45Njk0IDI4LjY4OCAyNC4xMzUgMjguNzM0OSAyMy40NDg1IDI5LjEzNDVDMjIuNzYyIDI5LjUzNCAyMi4zMDUxIDMwLjIzODUgMjIuMjE3OSAzMS4wMzIyTDIxLjkxMjYgMzMuODA4QzIxLjg0OTcgMzQuMzgwNCAyMS40MzY0IDM0Ljg1MTMgMjAuODgwMiAzNC45ODQ2QzE4LjI2NSAzNS42MTExIDE1LjU0MDIgMzUuNjExMSAxMi45MjQ5IDM0Ljk4NDZDMTIuMzY4NSAzNC44NTEzIDExLjk1NTMgMzQuMzgwNCAxMS44OTI0IDMzLjgwOEwxMS41ODc1IDMxLjAzNjRDMTEuNDk4IDMwLjI0NDIgMTEuMDQwNSAyOS41NDE4IDEwLjM1NDUgMjkuMTQzNkM5LjY2ODUyIDI4Ljc0NTUgOC44MzU1OCAyOC42OTg4IDguMTExNTUgMjkuMDE3MUw1LjU3MTc5IDMwLjEzNzVDNS4wNDU3MiAzMC4zNjk2IDQuNDMxNTQgMzAuMjQyMyA0LjAzOTMgMjkuODE5OUMyLjIxMTc2IDI3Ljg1MTggMC44NTE0MDggMjUuNDkxOSAwLjA2MDU3NDcgMjIuOTE3OEMtMC4xMDk3NDcgMjIuMzYzNSAwLjA4NzA1ODYgMjEuNzYxMSAwLjU1MTA1IDIxLjQxNjlMMi43OTU5MyAxOS43NTE1QzMuNDM1MzggMTkuMjc4NCAzLjgxMzAzIDE4LjUyNzEgMy44MTMwMyAxNy43MjgxQzMuODEzMDMgMTYuOTI5MSAzLjQzNTM4IDE2LjE3NzggMi43OTUxMyAxNS43MDQxTDAuNTUxNjI4IDE0LjA0MTVDMC4wODY5MzU5IDEzLjY5NzIgLTAuMTEwMDUgMTMuMDk0MSAwLjA2MDg3ODEgMTIuNTM5MkMwLjg1MzA2OSA5Ljk2NzU2IDIuMjE0MTUgNy42MTAyNSA0LjA0MTY5IDUuNjQ0NzZDNC40MzQzNiA1LjIyMjQ3IDUuMDQ4ODMgNS4wOTU2MiA1LjU3NDgyIDUuMzI4MjlMOC4xMDMyNSA2LjQ0Njc2QzguODMwODIgNi43NjgyOSA5LjY2NzIyIDYuNzE5NzIgMTAuMzU2OSA2LjMxMzk5QzExLjA0MzYgNS45MTI4OSAxMS41MDA3IDUuMjA3NjcgMTEuNTg5IDQuNDEzODlMMTEuODk2MSAxLjYzOTkzQzExLjk2MDIgMS4wNjA2MyAxMi4zODI5IDAuNTg2NDM2IDEyLjk0NzkgMC40NTk4OTFDMTQuMjQzMSAwLjE2OTg1NSAxNS41NjQ0IDAuMDE1NzI3MyAxNi45MjI1IDBaTTE5Ljc0NzcgMTAuNDU0NUgxMy44MzlMMTkuNDQ1OCAxNy43MjczTDE0LjQzOTIgMjQuMTE2NkMxNC4yNTM4IDI0LjM1MzEgMTQuMjk0IDI0LjY5NjEgMTQuNTI4NyAyNC44ODI3QzE0LjYyNDQgMjQuOTU4NyAxNC43NDI1IDI1IDE0Ljg2NDQgMjVIMTkuNzQ3N0MxOS45MTUxIDI1IDIwLjA3MjkgMjQuOTIyMiAyMC4xNzU2IDI0Ljc4OTFMMjUuMTkxMSAxOC4yODQ3QzI1LjQ0NDIgMTcuOTU2NyAyNS40NDQyIDE3LjQ5NzYgMjUuMTkxMSAxNy4xNjk2TDIwLjE3NTYgMTAuNjY1NUMyMC4wNzI5IDEwLjUzMjQgMTkuOTE1MSAxMC40NTQ1IDE5Ljc0NzcgMTAuNDU0NVpNMTIuNzA5IDExLjkxNDVMOC42MjIwMiAxNy4xNjY1QzguNDAyMzkgMTcuNDQ4NyA4LjM3MDk4IDE3LjgyOTEgOC41Mjc4NCAxOC4xMzk0TDguNjIxOTUgMTguMjg4TDEyLjIzNiAyMi45MzM4TDEyLjMyNTIgMjMuMDI0NEMxMi41MjU5IDIzLjE4NTEgMTIuODA0OSAyMy4xNzkzIDEyLjk5NzggMjMuMDI2TDEzLjA4NjQgMjIuOTM3MUwxNS40OTkgMTkuODgyOEMxNS42MjQgMTkuNzI0NiAxNS42NDk2IDE5LjUxMzggMTUuNTc1NCAxOS4zMzRMMTUuNTAxMiAxOS4yMDY0TDE0LjM0NzggMTcuNzI3M0wxNS43NTg1IDE1LjkxNjRMMTIuNzA5IDExLjkxNDVaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K'
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
				if ($new_status != 'trash' && BWT_IndexNow_Admin_Utils::url_submitted_within_last_minute(BWT_IndexNow_Admin_Routes::$passed_submissions_table, $link)) {
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
	
	/**
	 *  Renders the IndexNow page for path site_url/{apikey}.txt.
	 *
	 */
	public function check_for_indexnow_page() {
		$admin_api_key = get_option( $this->prefix . "admin_api_key" );
		$api_key       = base64_decode( $admin_api_key );
		global $wp;
		$current_url = home_url( $wp->request );

		if ( isset( $current_url ) && trailingslashit( get_home_url() ) . $api_key . '.txt' === $current_url ) {
			header( 'Content-Type: text/plain' );
			header( 'X-Robots-Tag: noindex' );
			status_header( 200 );
			esc_html_e($api_key);

			exit();
		}
	}
}
