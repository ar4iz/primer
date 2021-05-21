<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       test.example.com
 * @since      1.0.0
 *
 * @package    Primer
 * @subpackage Primer/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Primer
 * @subpackage Primer/includes
 * @author     test_user <testwe@gmail.com>
 */
class Primer {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Primer_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name = 'primer';

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version = PRIMER_VERSION;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Primer_Loader. Orchestrates the hooks of the plugin.
	 * - Primer_i18n. Defines internationalization functionality.
	 * - Primer_Admin. Defines all hooks for the admin area.
	 * - Primer_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		global $pagenow;

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once PRIMER_PATH . 'includes/class-primer-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once PRIMER_PATH . 'includes/class-primer-i18n.php';

		require_once PRIMER_PATH . 'admin/includes/primer-admin-notices.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once PRIMER_PATH . 'admin/class-primer-admin.php';

		require_once PRIMER_PATH . 'admin/includes/primer-admin-options.php';
		if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' || $pagenow == 'user-edit.php' || $pagenow == 'user-new.php' ) {
			require_once PRIMER_PATH . 'admin/includes/primer-admin-metaboxes.php';
		}

		require_once PRIMER_PATH . 'includes/class-primer-settings.php';
		require_once PRIMER_PATH . 'includes/vendor/cmb2/init.php';

		require_once PRIMER_PATH . 'includes/template-tags/primer-tags-receipt.php';

		require_once PRIMER_PATH . 'includes/template-tags/primer-tags-display-modules.php';

//		require_once SLICED_PATH . 'admin/includes/primer-admin-notices.php';
//		require_once SLICED_PATH . 'admin/includes/primer-admin-notifications.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once PRIMER_PATH . 'public/class-primer-public.php';

		$this->loader = new Primer_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Primer_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Primer_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );
		$plugin_i18n->load_plugin_textdomain();

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Primer_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_filter( 'cmb2_script_dependencies', $plugin_admin, 'cmb2_enqueue_datepicker' );

		$this->loader->add_filter( 'admin_body_class', $plugin_admin, 'add_admin_body_class', 11 );

		$this->loader->add_filter( 'plugin_action_links_' . trailingslashit( $this->get_plugin_name() ) . $this->get_plugin_name() . '.php', $plugin_admin, 'plugin_action_links' );

		$this->loader->add_action( 'init', $plugin_admin, 'new_taxonomy_receipt_status', 1 );
		$this->loader->add_action( 'init', $plugin_admin, 'new_cpt_receipt', 1 );
		$this->loader->add_action( 'init', $plugin_admin, 'register_new_terms', 1 );

		$this->loader->add_action( 'init', $plugin_admin, 'settings_check' );

		// Additional Checkout billing fields
		$this->loader->add_filter('woocommerce_admin_billing_fields', $plugin_admin, 'primer_add_woocommerce_admin_billing_fields');

		$this->loader->add_action('woocommerce_admin_order_data_after_order_details', $plugin_admin, 'primer_editable_order_meta_general');
		$this->loader->add_action('woocommerce_process_shop_order_meta', $plugin_admin, 'primer_save_general_details');


		$this->loader->add_filter('woocommerce_found_customer_details', $plugin_admin, 'primer_add_woocommerce_found_customer_details', 10, 3);
		$this->loader->add_filter('woocommerce_customer_meta_fields', $plugin_admin, 'primer_add_woocommerce_customer_meta_fields');
		$this->loader->add_filter('woocommerce_order_formatted_billing_address', $plugin_admin, 'primer_add_woocommerce_order_fields', 10, 2);
		$this->loader->add_filter('woocommerce_formatted_address_replacements', $plugin_admin, 'primer_add_woocommerce_formatted_address_replacements', 10, 2);
		$this->loader->add_filter('woocommerce_billing_fields', $plugin_admin, 'primer_add_woocommerce_billing_fields');
		$this->loader->add_action('woocommerce_checkout_process', $plugin_admin, 'primer_checkout_field_process');
		$this->loader->add_action('manage_shop_order_posts_custom_column', $plugin_admin, 'primer_icon_to_order_notes_column', 15, 1);


		$this->loader->add_filter( 'admin_notices', $plugin_admin, 'custom_admin_notices' );

		$this->loader->add_action( 'primer_receipts_hourly_tasks', $plugin_admin, 'primer_receipts_hourly_tasks' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Primer_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'primer_head', $plugin_public, 'output_styles' );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_filter( 'private_title_format', $plugin_public, 'title_format');
		$this->loader->add_filter( 'protected_title_format', $plugin_public, 'title_format');

		$this->loader->add_filter( 'single_template', $plugin_public, 'receipt_template', 999 );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Primer_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
