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
			'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/Pgo8IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDIwMDEwOTA0Ly9FTiIKICJodHRwOi8vd3d3LnczLm9yZy9UUi8yMDAxL1JFQy1TVkctMjAwMTA5MDQvRFREL3N2ZzEwLmR0ZCI+CjxzdmcgdmVyc2lvbj0iMS4wIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiB3aWR0aD0iMjE4LjAwMDAwMHB0IiBoZWlnaHQ9IjE4Ni4wMDAwMDBwdCIgdmlld0JveD0iMCAwIDIxOC4wMDAwMDAgMTg2LjAwMDAwMCIKIHByZXNlcnZlQXNwZWN0UmF0aW89InhNaWRZTWlkIG1lZXQiPgoKPGcgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMC4wMDAwMDAsMTg2LjAwMDAwMCkgc2NhbGUoMC4xMDAwMDAsLTAuMTAwMDAwKSIKZmlsbD0iIzAwMDAwMCIgc3Ryb2tlPSJub25lIj4KPHBhdGggZD0iTTAgOTMwIGwwIC05MzAgMTA5MCAwIDEwOTAgMCAwIDkzMCAwIDkzMCAtMTA5MCAwIC0xMDkwIDAgMCAtOTMwegptMTg1MCA2MzcgYzM3IC0xOSAzNyAtNjggLTEgLTE3OSAtOTcgLTI4NCAtMTkwIC00MzMgLTM2OSAtNTkzIC0xNzggLTE2MAotMzg3IC0yNTQgLTYzMyAtMjg1IC0xNDAgLTE3IC0xMzUgLTE5IC0xOTkgNjIgLTMyIDM5IC01OCA4MCAtNTggOTEgMCAzMyA1NwoxOTIgOTkgMjc0IDE0MyAyODMgNDE5IDUxNiA3MDIgNTkyIDE2NCA0NSA0MDYgNjUgNDU5IDM4eiBtLTExNDkgLTQ0MiBjNDQKLTIzIDQ0IC0yMiAyOSAtMzkgLTEwIC0xNCAtMTggLTEyIC02MSA5IC0yNiAxNCAtNTMgMjUgLTU4IDI1IC0xNSAwIC0zNzgKLTI5NyAtMzg1IC0zMTYgLTkgLTIzIDIgLTUyIDIzIC02NCAxNiAtOCAxODcgNiAyOTQgMjUgMjIgNCAyNyAxIDI3IC0xNSAwCi0yMyAtMyAtMjQgLTE3NCAtNDUgLTE0NiAtMTggLTE3NiAtMTMgLTIwMSAzNiAtMTkgMzcgLTE5IDU4IDMgOTEgMjMgMzcgMzU5CjMwOSAzOTEgMzE4IDI5IDggNjEgMSAxMTIgLTI1eiBtLTExMSAtNTQ1IGMwIC02IDE2IC0yOCAzNiAtNTAgMzMgLTM2IDM2IC00MwoyNiAtNjUgLTIwIC00NCAtMTUzIC0xMjcgLTI0OSAtMTU1IC0zOSAtMTIgLTQ1IC0xMSAtNjQgNSAtMjQgMjMgLTIwIDUyIDE4CjExMCAzOSA2MCAxMzQgMTU2IDE2OCAxNzAgMzAgMTMgNjUgNSA2NSAtMTV6IG02MjIgLTg3IGMxMyAtNTQgMTQgLTczIDUgLTk1Ci03IC0xNyAtODcgLTkwIC0yMDIgLTE4MyAtMTY3IC0xMzcgLTE5MyAtMTU1IC0yMjYgLTE1NSAtNTIgMCAtODkgMzcgLTg4IDg3CjAgMjEgMjIgMTAxIDQ3IDE3OCA0MCAxMTggNTAgMTQwIDY4IDE0MyAyMSAzIDIwIC00IC0yNyAtMTQ5IC0yNyAtODQgLTQ5Ci0xNjMgLTQ5IC0xNzUgMCAtMjQgMjYgLTQ0IDU1IC00NCA5IDAgOTkgNjggMjAxIDE1MSAyMDAgMTYzIDE5NyAxNTkgMTczIDI0MgotMTIgNDMgLTcgNjcgMTQgNjcgOCAwIDE5IC0yNiAyOSAtNjd6Ii8+CjxwYXRoIGQ9Ik0xNjIzIDE1MzMgYy0yOSAtMiAtNTMgLTcgLTUzIC0xMCAwIC0zIDI4IC0zNiA2MyAtNzIgMzQgLTM3IDc2IC05MQo5MiAtMTE5IDE3IC0yOCAzMyAtNTIgMzYgLTUyIDEwIDAgNzkgMTk4IDc5IDIyNSAwIDEyIC03IDI2IC0xNiAyOSAtMTYgNgotMTA0IDUgLTIwMSAtMXoiLz4KPHBhdGggZD0iTTE0MTMgMTQ5NSBjLTE3OSAtNDkgLTM1NyAtMTU5IC01MDQgLTMxMSAtNzcgLTgxIC0xMzEgLTE1OSAtMTg2Ci0yNjkgLTM3IC03MyAtOTMgLTIyMyAtOTMgLTI0NyAwIC02IDIxIC0zNyA0OCAtNjkgbDQ3IC02MCA4MCA3IGMxMDIgOCAyNDEKNDMgMzQ5IDg3IDIyNyA5MyA0NDUgMjk3IDU2MSA1MjcgbDI4IDU1IC0zNCA2NSBjLTIwIDM5IC02NSA5OCAtMTEzIDE0OCAtODYKOTAgLTkxIDkyIC0xODMgNjd6IG0yNyAtMjA1IGMyMiAtMTEgNDYgLTMzIDU1IC01MCAxOCAtMzUgMjAgLTk5IDMgLTEzNSAtMTcKLTM3IC03MyAtNjggLTEyNCAtNjggLTE0NiAwIC0xODQgMTk5IC00OSAyNTkgNDIgMTkgNzAgMTcgMTE1IC02eiIvPgo8cGF0aCBkPSJNMTM0MCAxMjU5IGMtMzcgLTE1IC02MCAtNDkgLTYwIC04OSAwIC00NiAyMSAtNzYgNjYgLTkwIDY2IC0yMiAxMjQKMjEgMTI0IDkyIDAgNjggLTY3IDExMyAtMTMwIDg3eiIvPgo8cGF0aCBkPSJNNDY4IDQ5OCBjLTU5IC02MiAtMTE1IC0xNDQgLTEwNSAtMTU0IDE4IC0xOCAxNzAgNTggMjI5IDExMyBsMjcgMjYKLTI0IDE5IGMtMTMgMTAgLTI3IDI3IC0zMCAzOCAtMTEgMzMgLTM0IDIzIC05NyAtNDJ6Ii8+CjwvZz4KPC9zdmc+Cg=='
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
