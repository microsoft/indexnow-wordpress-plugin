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
