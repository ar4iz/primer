<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       test.example.com
 * @since      1.0.0
 *
 * @package    Primer
 * @subpackage Primer/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Primer
 * @subpackage Primer/admin
 * @author     test_user <testwe@gmail.com>
 */
class Primer_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Primer Recurring Tasks
		if ( ! wp_next_scheduled( 'primer_receipts_hourly_tasks' ) ) {
			wp_schedule_event( time(), 'hourly', 'primer_receipts_hourly_tasks' );
		}
	}

	/*
	 * Make sure the jQuery UI datepicker is enqueued for CMB2.
	 *
	 * If there are no cmb2 fields of the datepicker type on a page, cmb2 will
	 * not enqueue the datepicker scripts.  Since we are now using cmb2 field
	 * type 'text' for dates and initializing datepickers on our own, we need to
	 * manually add them as cmb2 dependencies.
	 *
	 * @since   3.8.0
	 */
	public function cmb2_enqueue_datepicker( $dependencies ) {
		$dependencies['jquery-ui-core'] = 'jquery-ui-core';
		$dependencies['jquery-ui-datepicker'] = 'jquery-ui-datepicker';
		$dependencies['jquery-ui-datetimepicker'] = 'jquery-ui-datetimepicker';
		return $dependencies;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Primer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Primer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/primer-admin.css', array(), $this->version, 'all' );

		//jQuery UI style
		wp_register_style('primer-jquery-ui', PRIMER_URL . '/public/css/jquery-ui.min.css', array(), PRIMER_VERSION);
		wp_enqueue_style('primer-jquery-ui');
		wp_enqueue_script('jquery-ui-datepicker');

		//Bootstrap select
		$screen = get_current_screen();
		if ( $screen->id == "toplevel_page_wp_ajax_list_order" || $screen->id == "primer-receipts_page_primer_receipts" ) {
			wp_register_style('primer-bootstrap-css', PRIMER_URL . '/public/css/bootstrap.min.css', array(), PRIMER_VERSION);
			wp_enqueue_style('primer-bootstrap-css');
			wp_register_style('primer-bootstrap-select-css', PRIMER_URL . '/public/css/bootstrap-select.min.css', array(), PRIMER_VERSION);
			wp_enqueue_style('primer-bootstrap-select-css');
			wp_enqueue_script('primer-bootstrap-js', PRIMER_URL . '/public/js/bootstrap.bundle.min.js', array('jquery'), PRIMER_VERSION, true);
			wp_enqueue_script('primer-bootstrap-select-js', PRIMER_URL . '/public/js/bootstrap-select.min.js', array('jquery'), PRIMER_VERSION, true);
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Primer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Primer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/primer-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Creates a new custom post type
	 *
	 * @since 	1.0.0
	 */
	public function new_cpt_receipt() {

		$translate = get_option( 'primer_translate' );

		$cap_type = 'post';
		$plural = primer_get_receipt_label_plural();
		$single = primer_get_receipt_label();
		$cpt_name = 'primer_receipt';

		$opts['can_export']             = TRUE;
		$opts['capability_type']        = $cap_type;
		$opts['description']            = '';
		$opts['exclude_from_search']    = TRUE;
		$opts['has_archive']            = FALSE;
		$opts['hierarchical']           = TRUE;
		$opts['map_meta_cap']           = TRUE;
		$opts['menu_icon']              = 'dashicons-text-page';
		$opts['public']                 = TRUE;
		$opts['publicly_querable']      = TRUE;
		$opts['query_var']              = TRUE;
		$opts['register_meta_box_cb']   = '';
		$opts['rewrite']                = FALSE;
		$opts['show_in_admin_bar']      = TRUE;
		$opts['show_in_menu']           = TRUE;
		$opts['show_in_nav_menu']       = TRUE;
		$opts['show_ui']                = TRUE;
		$opts['supports']			    = array( 'title', 'comments' );
		$opts['taxonomies']				= array( 'receipt_status' );

		$opts['capabilities']['delete_others_posts']	= "delete_others_{$cap_type}s";
		$opts['capabilities']['delete_post']			= "delete_{$cap_type}";
		$opts['capabilities']['delete_posts']			= "delete_{$cap_type}s";
		$opts['capabilities']['delete_private_posts']	= "delete_private_{$cap_type}s";
		$opts['capabilities']['delete_published_posts']	= "delete_published_{$cap_type}s";
		$opts['capabilities']['edit_others_posts']		= "edit_others_{$cap_type}s";
		$opts['capabilities']['edit_post']				= "edit_{$cap_type}";
		$opts['capabilities']['edit_posts']				= "edit_{$cap_type}s";
		$opts['capabilities']['edit_private_posts']		= "edit_private_{$cap_type}s";
		$opts['capabilities']['edit_published_posts']	= "edit_published_{$cap_type}s";
		$opts['capabilities']['publish_posts']			= "publish_{$cap_type}s";
		$opts['capabilities']['read_post']				= "read_{$cap_type}";
		$opts['capabilities']['read_private_posts']		= "read_private_{$cap_type}s";

		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['add_new']						= sprintf( __( 'Add New %s', 'primer' ), $single );
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['add_new_item']					= sprintf( __( 'Add New %s', 'primer' ), $single );
		$opts['labels']['all_items']					= $plural;
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['edit_item']					= sprintf( __( 'Edit %s' , 'primer' ), $single );
		$opts['labels']['menu_name']					= $plural;
		$opts['labels']['name']							= $plural;
		$opts['labels']['name_admin_bar']				= $single;
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['new_item']						= sprintf( __( 'New %s', 'primer' ), $single );
		/* translators: %s is a placeholder for the localized word "Receipts" (plural) */
		$opts['labels']['not_found']					= sprintf( __( 'No %s Found', 'primer' ), $plural );
		/* translators: %s is a placeholder for the localized word "Receipts" (plural) */
		$opts['labels']['not_found_in_trash']			= sprintf( __( 'No %s Found in Trash', 'primer' ), $plural );
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['parent_item_colon']			= sprintf( __( 'Parent %s:', 'primer' ), $single );
		/* translators: %s is a placeholder for the localized word "Receipts" (plural) */
		$opts['labels']['search_items']					= sprintf( __( 'Search %s', 'primer' ), $plural );
		$opts['labels']['singular_name']				= $single;
		/* translators: %s is a placeholder for the localized word "Receipt" (singular) */
		$opts['labels']['view_item']					= sprintf( __( 'View %s', 'primer' ), $single );

		$opts['rewrite']['slug']						= FALSE;
		$opts['rewrite']['with_front']					= FALSE;
		$opts['rewrite']['feeds']						= FALSE;
		$opts['rewrite']['pages']						= FALSE;

		$opts = apply_filters('primer_receipt_params', $opts);

		register_post_type('primer_receipt', $opts );
	}

	/**
	 * Creates a new taxonomy for a custom post type
	 *
	 * @since 	1.0.0
	 */
	public function new_taxonomy_receipt_status() {

		$plural 	= __( 'Statuses', 'primer' );
		$single 	= __( 'Status', 'primer' );
		$tax_name 	= 'receipt_status';

		$opts['hierarchical']							= TRUE;
		$opts['public']									= TRUE;
		$opts['query_var']								= $tax_name;
		$opts['show_admin_column'] 						= TRUE;
		$opts['show_in_nav_menus']						= FALSE;
		$opts['show_tag_cloud'] 						= FALSE;
		$opts['show_ui']								= FALSE;
		$opts['sort'] 									= '';

		$opts['capabilities']['assign_terms'] 			= 'edit_posts';
		$opts['capabilities']['delete_terms'] 			= 'manage_categories';
		$opts['capabilities']['edit_terms'] 			= 'manage_categories';
		$opts['capabilities']['manage_terms'] 			= 'manage_categories';

		/* translators: %s is a placeholder for the localized word "Status" (singular) */
		$opts['labels']['add_new_item']					= sprintf( __( 'Add New %s', 'primer' ), $single );
		/* translators: %s is a placeholder for the localized word "Statuses" (plural) */
		$opts['labels']['add_or_remove_items'] 			= sprintf( __( 'Add or remove %s', 'primer' ), $plural );
		$opts['labels']['all_items']					= $plural;
		/* translators: %s is a placeholder for the localized word "Statuses" (plural) */
		$opts['labels']['choose_from_most_used'] 		= sprintf( __( 'Choose from most used %s', 'primer' ), $plural );
		/* translators: %s is a placeholder for the localized word "Status" (singular) */
		$opts['labels']['edit_item']					= sprintf( __( 'Edit %s' , 'primer' ), $single );
		$opts['labels']['menu_name']					= $plural;
		$opts['labels']['name']							= $plural;
		/* translators: %s is a placeholder for the localized word "Status" (singular) */
		$opts['labels']['new_item_name'] 				= sprintf( __( 'New %s Name', 'primer' ), $single );
		/* translators: %s is a placeholder for the localized word "Statuses" (plural) */
		$opts['labels']['not_found']					= sprintf( __( 'No %s Found', 'primer' ), $plural );
		/* translators: %s is a placeholder for the localized word "Status" (singular) */
		$opts['labels']['parent_item'] 					= sprintf( __( 'Parent %s', 'primer' ), $single );
		/* translators: %s is a placeholder for the localized word "Status" (singular) */
		$opts['labels']['parent_item_colon']			= sprintf( __( 'Parent %s:', 'primer' ), $single );
		/* translators: %s is a placeholder for the localized word "Statuses" (plural) */
		$opts['labels']['popular_items'] 				= sprintf( __( 'Popular %s', 'primer' ), $plural );
		/* translators: %s is a placeholder for the localized word "Statuses" (plural) */
		$opts['labels']['search_items']					= sprintf( __( 'Search %s', 'primer' ), $plural );
		/* translators: %s is a placeholder for the localized word "Statuses" (plural) */
		$opts['labels']['separate_items_with_commas'] 	= sprintf( __( 'Separate %s with commas', 'primer' ), $plural );
		$opts['labels']['singular_name']				= $single;
		/* translators: %s is a placeholder for the localized word "Status" (singular) */
		$opts['labels']['update_item'] 					= sprintf( __( 'Update %s', 'primer' ), $single );
		/* translators: %s is a placeholder for the localized word "Status" (singular) */
		$opts['labels']['view_item']					= sprintf( __( 'View %s', 'primer' ), $single );

		$opts['rewrite']['slug']						= __( strtolower( $tax_name ), 'primer' );

		$opts = apply_filters( 'primer_receipt_status_params', $opts );

		register_taxonomy( $tax_name, 'primer_receipt', $opts );
	}


	/**
	 * Trigger notices for any issues with settings, etc.
	 *
	 * @since 1.0.0
	 */
	public function settings_check() {
		if ( ! method_exists( 'Primer_Admin_notices', 'add_notice' ) ) {
			return;
		}

		// 1) invalid_mydata_page check
		if (is_admin()) {
			if ( isset( $_POST['object_id'] ) && $_POST['object_id'] === 'primer_mydata' && isset( $_POST['standard_vat_rates'] ) ) {
				// true if we just hit save from the primer_mydata settings page
				// CMB2 will not have saved the value in time for this message to fire, so we'll grab it from $_POST:
				$standard_vat_rates = array( 'standard_vat_rates' => intval( sanitize_text_field( $_POST['standard_vat_rates'] ) ) );
			} else {
				$standard_vat_rates = get_option( 'primer_mydata' );
			}
			/*if ($standard_vat_rates['standard_vat_rates'] != '0') {
				Primer_Admin_notices::remove_notice('invalid_mydata_page');
			} else {
				Primer_Admin_notices::add_notice('invalid_mydata_page', true);
			}*/
		}


	}


	/**
	 * Admin notices
	 *
	 * @since 	1.0.0
	 */
	public function custom_admin_notices( $post_states ) {

		global $pagenow;

		/*
		 * Options updated notice
		 */
		if ( $pagenow == 'admin.php' && ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'primer_' ) !== false ) && isset( $_POST['submit-cmb'] ) ) {
			echo '<div class="updated">
				<p>' . __( 'Settings saved successfully.', 'primer' ) . '</p>
			</div>';
		}

		/*
		 * Possible not compatible notices
		 */
		$errors = get_transient( 'primer_activation_warning' );
		if ( $errors ) {
			if ( $pagenow == 'plugins.php' && isset($errors['wp_error'] ) ) {
				echo '<div class="error">
		             <p>' . __( 'Your WordPress version may not be compatible with the Primer Receipts plugin. If you are having issues with the plugin, we recommend making a backup of your site and upgrading to the latest version of WordPress.', 'primer' ) . '</p>
		         </div>';
			}
			if ( $pagenow == 'plugins.php' && isset($errors['php_error'] ) ) {
				echo '<div class="error">
		             <p>' . __( 'Your PHP version may not be compatible with the Primer Receipts plugin. We recommend contacting your server administrator and getting them to upgrade to a newer version of PHP.', 'primer' ) . '</p>
		         </div>';
			}
			if ( $pagenow == 'plugins.php' && isset($errors['curl_error'] ) ) {
				echo '<div class="error">
		             <p>' . __( 'You do not have the cURL extension installed on your server. This extension is required for some tasks. Please contact your server administrator to have them install this on your server.', 'primer' ) . '</p>
		         </div>';
			}
		}
	}

	/**
	 * Add links to plugin page
	 *
	 * @since 	1.0.0
	 */
	public function plugin_action_links( $links ) {
		$links[] = '<a href="'. esc_url( get_admin_url( null, 'admin.php?page=primer' ) ) .'">' . __( 'Settings', 'primer' ) . '</a>';
		return $links;
	}

	/**
	 * Add a class to the admin body
	 *
	 * @since 	1.0.0
	 */
	public function add_admin_body_class( $classes ) {

		global $pagenow;
		$add_class = false;
		if ( $pagenow == 'admin.php' && isset( $_GET['page'] ) ) {
			$add_class = strpos( $_GET['page'], 'primer_' );
		}

		return $classes;
	}

}
