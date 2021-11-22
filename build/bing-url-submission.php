<?php

/**
 * @link              https://www.bing.com/webmaster
 * @since             0.01.01
 * @package           Bing_Webmaster
 *
 * @wordpress-plugin
 * Plugin Name:       Bing Webmaster Url Submission
 * Plugin URI:        https://www.bing.com/webmaster
 * Description:       A small plugin to allow Url submissions to Bing Webmaster Tools.
 * Version:           1.0.12
 * Author:            Bing Webmaster
 * Author URI:        https://www.bing.com/webmaster
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bing-url-submission
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'BWT_URL_SUBMISSION_PLUGIN_VERSION', '1.0.12' );

/**
 * Plugin name.
 */
define( 'BWT_URL_SUBMISSION_PLUGIN_NAME', 'bing-url-submission' );

/**
 * The code that runs during plugin activation.
 */
function activate_bing_webmaster_url_submission() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bing-url-submission-activator.php';
	Bing_Webmaster_Activator::activate(BWT_URL_SUBMISSION_PLUGIN_NAME);
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_bing_webmaster_url_submission() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bing-url-submission-deactivator.php';
	Bing_Webmaster_Deactivator::deactivate(BWT_URL_SUBMISSION_PLUGIN_NAME);
}

register_activation_hook( __FILE__, 'activate_bing_webmaster_url_submission' );
register_deactivation_hook( __FILE__, 'deactivate_bing_webmaster_url_submission' );

/**
 * The core plugin class that is used to define admin-specific hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-bing-url-submission.php';

/**
 * Begins execution of the plugin.
 *
 * @since    0.01.01
 */
function run_bing_webmaster_url_submission() {

	$plugin = new Bing_Webmaster(BWT_URL_SUBMISSION_PLUGIN_NAME);
	$plugin->run();

}
run_bing_webmaster_url_submission();
