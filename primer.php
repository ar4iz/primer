<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              test.example.com
 * @since             1.0.0
 * @package           Primer
 *
 * @wordpress-plugin
 * Plugin Name:       Primer receipts
 * Plugin URI:        test.example.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            test_user
 * Author URI:        test.example.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       primer
 * Domain Path:       /languages
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PRIMER_VERSION', '1.0.0' );

/**
 * Currently plugin path.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you change path.
 */
define( 'PRIMER_PATH', plugin_dir_path( __FILE__ ) );
define( 'PRIMER_URL', plugins_url( '', __FILE__ ) );


if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) || is_multisite() && in_array('woocommerce/woocommerce.php', array_flip(get_site_option('active_sitewide_plugins'))) ) {
	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-primer-activator.php
	 */
	function activate_primer() {
		require_once PRIMER_PATH . 'includes/class-primer-activator.php';
		Primer_Activator::activate();
	}

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-primer-deactivator.php
	 */
	function deactivate_primer() {
		require_once PRIMER_PATH . 'includes/class-primer-deactivator.php';
		Primer_Deactivator::deactivate();
	}

	register_activation_hook( __FILE__, 'activate_primer' );
	register_deactivation_hook( __FILE__, 'deactivate_primer' );


	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require PRIMER_PATH . 'includes/class-primer.php';

	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 */
	function run_primer() {

		$plugin = new Primer();
		$plugin->run();

	}

	add_action( 'plugins_loaded', 'run_primer' ); // wait until 'plugins_loaded' hook fires, for WP Multisite compatibility
} else {

	add_action('admin_notices', 'primer_error_notice');

	function primer_error_notice() {
		global $current_screen;
		if ($current_screen->parent_base == 'plugins') {
			echo '<div class="error"><p>Primer '.__('requires <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a> to be activated to work. Please install and activate <a href="'.admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce').'" target="_blank">Woocommerce</a> first.', 'primer').'</p></div>';
		}
	}

	$plugin = plugin_basename(__FILE__);

	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	if(is_plugin_active($plugin)){
		deactivate_plugins( $plugin);
	}

	if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

}



// Patch for Sage-based themes
function primer_patch_for_sage_based_themes() {
	// The following is our own solution to the problem of Sage-based themes
	// which force their own "wrapper", injecting code into our templates where
	// it is not wanted, breaking them.
	// i.e.: https://discourse.roots.io/t/single-template-filter-from-plugins/6637
	global $wp_filter;
	$tag = 'template_include';
	$priority = 99;
	if ( ! isset( $wp_filter[ $tag ] ) ) {
		return FALSE;
	}
	if ( is_object( $wp_filter[ $tag ] ) && isset( $wp_filter[ $tag ]->callbacks ) ) {
		$fob       = $wp_filter[ $tag ];
		$callbacks = &$wp_filter[ $tag ]->callbacks;
	} else {
		$callbacks = &$wp_filter[ $tag ];
	}
	if ( ! isset( $callbacks[ $priority ] ) || empty( $callbacks[ $priority ] ) ) {
		return FALSE;
	}
	foreach ( (array) $callbacks[ $priority ] as $filter_id => $filter ) {
		if ( ! isset( $filter['function'] ) || ! is_array( $filter['function'] ) ) {
			continue;
		}
		if ( $filter['function'][1] !== 'wrap' ) {
			continue;
		}
		if ( isset( $fob ) ) {
			$fob->remove_filter( $tag, $filter['function'], $priority );
		} else {
			unset( $callbacks[ $priority ][ $filter_id ] );
			if ( empty( $callbacks[ $priority ] ) ) {
				unset( $callbacks[ $priority ] );
			}
			if ( empty( $callbacks ) ) {
				$callbacks = array();
			}
			unset( $GLOBALS['merged_filters'][ $tag ] );
		}
		return TRUE;
	}
	return FALSE;
}
add_action( 'get_template_part_primer-receipt-display', 'primer_patch_for_sage_based_themes' );
