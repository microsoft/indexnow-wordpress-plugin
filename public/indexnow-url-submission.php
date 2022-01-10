<?php

/**
 * @link              https://www.bing.com/webmaster
 * @since             0.01.01
 * @package           BWT_IndexNow
 *
 * @wordpress-plugin
 * Plugin Name:       IndexNow
 * Plugin URI:        https://www.bing.com/webmaster
 * Description:       A small plugin to allow Url submissions to IndexNow.
 * Version:           1.0.1
 * Author:            Microsoft Bing
 * Author URI:        https://www.bing.com/indexnow
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       indexnow
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */

define( 'BWT_INDEXNOW_PLUGIN_VERSION', '1.0.1' );

/**
 * Plugin name.
 */

define( 'BWT_INDEXNOW_PLUGIN_NAME', 'indexnow' );

/**
 * The code that runs during plugin activation.
 */
function activate_indexnow_url_submission() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-indexnow-url-submission-activator.php';
	BWT_IndexNow_Activator::activate(BWT_INDEXNOW_PLUGIN_NAME);
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_indexnow_url_submission() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-indexnow-url-submission-deactivator.php';
	BWT_IndexNow_Deactivator::deactivate(BWT_INDEXNOW_PLUGIN_NAME);
}

register_activation_hook( __FILE__, 'activate_indexnow_url_submission' );
register_deactivation_hook( __FILE__, 'deactivate_indexnow_url_submission' );

/**
 * The core plugin class that is used to define admin-specific hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-indexnow-url-submission.php';

/**
 * Begins execution of the plugin.
 *
 * @since    0.01.01
 */
function run_indexnow_url_submission() {

	$plugin = new BWT_IndexNow(BWT_INDEXNOW_PLUGIN_NAME);
	$plugin->run();

}
run_indexnow_url_submission();
