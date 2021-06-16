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

// reference the Dompdf namespace
use Dompdf\Dompdf;

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

		wp_enqueue_script('cmb2-conditional', PRIMER_URL . '/includes/vendor/conditional/cmb2-conditionals.js');

		//Bootstrap select
		$screen = get_current_screen();
		if ( $screen->id == "toplevel_page_wp_ajax_list_order" || $screen->id == "primer-receipts_page_primer_receipts" || $screen->id == "admin_page_primer_receipts_logs" ) {
			wp_register_style('primer-bootstrap-css', PRIMER_URL . '/public/css/bootstrap.min.css', array(), PRIMER_VERSION);
			wp_enqueue_style('primer-bootstrap-css');
			wp_register_style('primer-bootstrap-select-css', PRIMER_URL . '/public/css/bootstrap-select.min.css', array(), PRIMER_VERSION);
			wp_enqueue_style('primer-bootstrap-select-css');

			wp_register_style('primer-select-woo', PRIMER_URL . '/public/css/select2.css', array(), PRIMER_VERSION);
			wp_enqueue_style('primer-select-woo');

			wp_enqueue_script('primer-bootstrap-js', PRIMER_URL . '/public/js/bootstrap.bundle.min.js', array('jquery'), PRIMER_VERSION, true);
			wp_enqueue_script('primer-bootstrap-select-js', PRIMER_URL . '/public/js/bootstrap-select.min.js', array('jquery'), PRIMER_VERSION, true);

			wp_enqueue_script('primer-select-woo-js', PRIMER_URL . '/public/js/selectWoo.full.js', array('jquery'), PRIMER_VERSION, false);

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
		$opts['show_ui']								= TRUE;
		$opts['show_in_menu']							= TRUE;
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

	public function register_new_terms() {
		$taxonomy = 'receipt_status';
		$terms = array (
			'english_receipt' => array (
				'name'          => 'English Receipt',
				'slug'          => 'english_receipt',
				'description'   => '',
			),
			'greek_receipt' => array (
				'name'          => 'Greek Receipt',
				'slug'          => 'greek_receipt',
				'description'   => '',
			),
			'english_invoice' => array (
				'name'          => 'English Invoice',
				'slug'          => 'english_invoice',
				'description'   => '',
			),
			'greek_invoice' => array (
				'name'          => 'Greek Invoice',
				'slug'          => 'greek_invoice',
				'description'   => '',
			),
		);

		foreach ( $terms as $term_key=>$term) {
			wp_insert_term(
				$term['name'],
				$taxonomy,
				array(
					'description'   => $term['description'],
					'slug'          => $term['slug'],
				)
			);
			unset( $term );
		}

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
		$opts['show_in_nav_menu']       = FALSE;
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
	 * Creates a new custom post type for receipt report
	 *
	 * @since 	1.0.0
	 */
	public function new_cpt_receipt_log() {

		$translate = get_option( 'primer_translate' );

		$cap_type = 'post';
		$plural = primer_get_receipt_log_label_plural();
		$single = primer_get_receipt_log_label();
		$cpt_name = 'primer_receipt_log';

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
		$opts['show_in_nav_menu']       = FALSE;
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

		$opts = apply_filters('primer_receipt_log_params', $opts);

		register_post_type('primer_receipt_log', $opts );
	}

	/**
	 * Creates a new custom post type for receipt report automation
	 *
	 * @since 	1.0.0
	 */
	public function new_cpt_receipt_log_automation() {

		$translate = get_option( 'primer_translate' );

		$cap_type = 'post';
		$plural = primer_get_receipt_log_automation_label_plural();
		$single = primer_get_receipt_log_automation_label();
		$cpt_name = 'pr_log_automation';

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
		$opts['show_in_nav_menu']       = FALSE;
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

		$opts = apply_filters('pr_log_automation_params', $opts);

		register_post_type('pr_log_automation', $opts );
	}

	public function primer_create_tax_rates() {

		$tax_option = get_option('woocommerce_calc_taxes');

		if ($tax_option != 'yes') {
			update_option('woocommerce_calc_taxes', 'yes');
		}

		$tax_rate = array('tax_rate_country' => 'GR', 'tax_rate_state' => '*', 'tax_rate' => '24.0000', 'tax_rate_name' => 'Standard', 'tax_rate_priority' => '1', 'tax_rate_compound' => '0', 'tax_rate_shipping' => '1', 'tax_rate_order' => '1', 'tax_rate_class' => '');

		$tax_classes   = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
		if ( ! in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
			array_unshift( $tax_classes, '' );
		}

		$tax_check = 'true';

		foreach ( $tax_classes as $tax_class ) { // For each tax class, get all rates.
			if (empty($tax_class)) {
				$taxes         = WC_Tax::get_rates_for_tax_class( $tax_class );
				$count_taxes = count((array)$taxes);
				if ($count_taxes == 0) {
					$tax_check = 'false';
				}
			}
		}
		if ($tax_check == 'false') {
			WC_Tax::_insert_tax_rate($tax_rate);
		}
	}

	/**
	 * Handle a custom 'customvar' query var to get orders with the 'customvar' meta.
	 * @param array $query - Args for WP_Query.
	 * @param array $query_vars - Query vars from WC_Order_Query.
	 * @return array modified $query
	 */
	public function handle_custom_query_var( $query, $query_vars, $that ) {

		if ( ! empty($query_vars['meta_query']['1']['key']) ) {
			$query['meta_query'][] = array(
				'key' => 'receipt_status',
				'value' => esc_attr($query_vars['receipt_status'] ),
			);
		}

		return $query;
	}

	public function primer_set_shipping_country() {
		return 'GR';
	}

	/**
	 * Create Invoice/Receipt billing fields in admin
	 */
	public function primer_add_woocommerce_admin_billing_fields($billing_fields) {
		// Loop through the (complete) keys/labels array
		foreach ( primer_get_keys_labels() as $key => $label ) {
			$billing_fields[$key]['label'] = $label;
		}
		return $billing_fields;
	}

	/**
	 * Create Invoice/Receipt billing fields in checkout
	 */
	public function primer_checkout_field_process() {
		if ( $_POST['billing_invoice_type'] == 'invoice' ) {
			// Loop through the (partial) keys/labels array
			foreach ( primer_get_keys_labels(false) as $key => $label ) {
				// Check if set, if not avoid checkout displaying an error notice.
				if ( ! $_POST['billing_'.$key] && $key != 'phone_mobile' ) {
					wc_add_notice( sprintf( __('%s is a required field.', 'primer' ), $label ), 'error' );
				}
			}
		}
	}

	/**
	 * Add Invoice Type column
	 * @param $column
	 */
	public function primer_icon_to_order_notes_column( $column ) {
		global $post, $the_order;

		// Added WC 3.2+  compatibility
		if ( $column == 'order_notes' || $column == 'order_number' ) {
			// Added WC 3+  compatibility
			$order_id = method_exists( $the_order, 'get_id' ) ? $the_order->get_id() : $the_order->id;

			$primer_type = get_post_meta( $order_id, '_billing_invoice_type', true );
			if ( $primer_type == 'invoice' ) {
				$style     = $column == 'order_notes' ? 'style="margin-top:5px;" ' : 'style="margin-left:8px;padding:5px;"';
				echo '<span class="dashicons dashicons-format-aside" '.$style.'title="'. __('Invoice Type', 'primer').'"></span>';
			}
		}
	}

	public function primer_add_woocommerce_found_customer_details($customer_data, $user_id, $type_to_load) {
		if ($type_to_load == 'billing') {
			// Loop through the (partial) keys/labels array
			foreach ( primer_get_keys_labels(false) as $key => $label ) {
				$customer_data[$type_to_load.'_'.$key] = get_user_meta($user_id, $type_to_load.'_'.$key, true);
			}
		}
		return $customer_data;
	}


	public function primer_add_woocommerce_billing_fields($billing_fields) {
		$labels = primer_get_keys_labels();

		$billing_fields['billing_invoice_type'] = array(
			'priority'  => '999',
			'type'      => 'radio',
			'required'  => true,
			'class'     => array('form-row-wide', 'form-row-flex'),
			'options'   => array(
				'receipt' => __('Receipt', 'primer'),
				'invoice' => __('Invoice', 'primer'),
			),
			'default' => 'receipt',
		);

		$billing_fields['billing_company'] = array(
			'priority' => '1000',
			'class' => array('form-row-wide', 'invoice_type-hide', 'validate-required'),
			'label'         => $labels['company'],
			'placeholder'   => _x( $labels['company'], 'placeholder' ),
			'required' => false,
		);

		$billing_fields['billing_vat'] = array(
			'priority'      => '1001',
			'type'          => 'text',
			'label'         => $labels['vat'],
			'placeholder'   => _x( $labels['vat'], 'placeholder' ),
			'class'         => array('form-row-first', 'invoice_type-hide', 'validate-required' ),
			'maxlength'     => '9',
			'required'      => false
		);

		$billing_fields['billing_doy'] = array(
			'priority'      => '1002',
			'type'          => 'select',
			'options'       => primer_return_doy_args(),
			'label'         => $labels['doy'],
			'placeholder'   => _x( $labels['doy'], 'placeholder' ),
			'class'         => array('form-row-last', 'invoice_type-hide', 'validate-required' ),
			'required'      => false
		);

		$billing_fields['billing_store'] = array(
			'priority'    => '1003',
			'type' => 'text',
			'label' => $labels['store'],
			'placeholder' => _x( $labels['store'], 'placeholder' ),
			'class' => array('form-row-wide', 'invoice_type-hide', 'validate-required'  ),
			'required' => false,
			'clear' => true
		);

		$billing_fields['billing_phone_mobile'] = array(
			'priority'      => '1004',
			'type'          => 'tel',
			'label'         => $labels['phone_mobile'],
			'placeholder'   => _x( $labels['phone_mobile'], 'placeholder' ),
			'class'         => array('form-row-wide'),
			'maxlength'     => 10,
			'required'      => false,
			'clear' => true
		);

		return $billing_fields;

	}

	/*public function primer_add_woocommerce_shipping_fields($shipping_fields) {
		$labels = primer_get_keys_labels();

		$shipping_fields['shipping_company'] = array(
			'priority' => '1000',
			'class' => array('form-row-wide', 'invoice_type-hide', 'validate-required'),
			'label'         => $labels['company'],
			'placeholder'   => _x( $labels['company'], 'placeholder' ),
			'required' => false,
		);
		return $shipping_fields;
	}*/

	public function primer_remove_woocommerce_shipping_fields($fields) {
		unset( $fields['shipping']['shipping_first_name'] );
		unset( $fields['shipping']['shipping_last_name'] );
		unset( $fields['shipping']['shipping_company'] );
		return $fields;
	}

	public function primer_add_woocommerce_customer_meta_fields($billing_fields) {
		if (isset($billing_fields['billing']['fields'])) {

			// Loop through the (partial) keys/labels array
			foreach ( primer_get_keys_labels(false) as $key => $label ) {
				$billing_fields['billing']['fields']['billing_'.$key] = array(
					'label' => $label,
					'description' => ''
				);
			}
			$billing_fields['billing']['fields']['billing_doy_name'] = array(
				'label' => 'DOY Name',
				'description' => ''
			);
		}
		return $billing_fields;
	}

	public function primer_add_woocommerce_order_fields($address, $order) {
		// Added WC 3+  compatibility
		$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
		// Loop through (partial) the keys/labels array (not the first entry)
		foreach( primer_get_keys_labels(false) as $key => $label ){
			$address['billing_'.$key] = get_post_meta( $order_id, '_billing_'.$key, true );
		}
		return $address;
	}

	public function primer_editable_order_meta_general( $order ) { ?>
		<br class="clear" />
		<h4>Invoice Type <a href="#" class="edit_address">Edit</a></h4>
		<?php
			$get_invoice_type = get_post_meta( $order->get_id(), '_billing_invoice_type', true );
		?>
		<div class="address">
			<p><strong>This is an invoice type order</strong><?php echo $get_invoice_type; ?></p>
		</div>
		<div class="edit_address">
			<?php
				woocommerce_wp_radio(array(
					'id' => 'get_invoice_type',
					'label' => 'Check invoice type',
					'value' => $get_invoice_type ? $get_invoice_type : 'receipt',
					'options' => array(
						'receipt' => 'Receipt',
						'invoice' => 'Invoice'
					),
					'style' => 'width:16px', // required for checkboxes and radio buttons
					'wrapper_class' => 'form-field-wide' // always add this class
				));
			?>
		</div>
	<?php }

	public function primer_save_general_details( $order_id ) {
		update_post_meta($order_id, '_billing_invoice_type', wc_clean( $_POST['get_invoice_type'] ));
	}

	public function primer_add_woocommerce_formatted_address_replacements( $replace, $args ) {
		// The (partial) keys/labels array (not the first entry)
		$data = primer_get_keys_labels(false);

		$replace['{billing_vat}'] = !empty($args['billing_vat']) ? $data['vat'] .': '. $args['billing_vat'] : '';
		$replace['{billing_store}'] = !empty($args['billing_store']) ? $data['store'] .': '. $args['billing_store'] : '';

		return $replace;
	}

	public function primer_checkout_save_user_meta( $order_id ) {
		$order = wc_get_order( $order_id );
		$user_id = $order->get_user_id();
		if ( $order->get_billing_first_name() ) {
			update_user_meta( $user_id, 'first_name', $order->get_billing_first_name() );
		}
		if ( $order->get_billing_last_name() ) {
			update_user_meta( $user_id, 'last_name', $order->get_billing_last_name() );
		}
		$doy = get_post_meta($order_id, '_billing_doy', true);
		$doy_value = primer_return_doy_args()[$doy];
		if (!empty($doy_value)) {
			update_user_meta( $user_id, 'billing_doy_name', $doy_value );
		}
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

	/**
	 * Primer Automation Settings conversation
	 */
	public function convert_order_to_invoice() {
		global $wpdb, $woocommerce;


		$receipt_log_automation_value = '';

		$emails = array();

		$send_to_admin = '';

		$automation_duration = '';

		// Get Notification Emails
		$automation_options = get_option('primer_automation');

		$activation_automation = $automation_options['activation_automation'];

		if (!empty($automation_options) && !empty($activation_automation)) {

			$primer_conditions = $automation_options['primer_conditions'];

			$primer_start_order_date = $automation_options['calendar_date_timestamp'];

			if (!empty($primer_conditions)) {

				$automation_duration = $automation_options['automation_duration'];
				if (!empty($automation_duration)) {
					wp_schedule_event(time(), $automation_duration, 'primer_cron_process');
				}

				$condition_order_status = '';
				foreach ( $primer_conditions as $primer_condition ) {
					$condition_order_status = $primer_condition['receipt_order_states'];
					$condition_client_email_send = $primer_condition['client_email_send'];
					$order_args           = array(
						'return'      => 'ids',
						'limit'       => 9999,
						'order'       => 'DESC',
						'numberposts' => - 1,
					);

					$order_args['status'] = $condition_order_status;

					if (!empty($primer_start_order_date)) {
						$order_args['date_created'] = '>' . $primer_start_order_date;
					}

					$orders = wc_get_orders( $order_args );

					foreach ( $orders as $order_id ) {
						$order = wc_get_order( $order_id );

						$id_of_order = $order->get_id();

						$issued_order = get_post_meta($id_of_order, 'receipt_status', true);

						if (empty($issued_order) || $issued_order == 'not_issued') {

							$order_country     = $order->get_billing_country();
							$order_create_date = date( 'F j, Y', $order->get_date_created()->getOffsetTimestamp() );
							$order_paid_date   = null;
							$order_paid_hour   = null;
							if ( ! empty( $order->get_date_paid() ) ) {
								$order_paid_date = date( 'F j, Y', $order->get_date_paid()->getTimestamp() );
								$order_paid_hour = date( 'H:i:s', $order->get_date_paid()->getTimestamp() );
							} else {
								$order_paid_date = date( 'F j, Y', $order->get_date_created()->getTimestamp() );
								$order_paid_hour = date( 'H:i:s', $order->get_date_created()->getTimestamp() );
							}

							$order_total_price = $order->get_total();
							$user_id           = $order->get_user_id();
							$user              = $order->get_user();

							$user_first_name = $order->get_billing_first_name();
							$user_last_name  = $order->get_billing_last_name();

							$user_full_name = $user_first_name . ' ' . $user_last_name;

							$tax = $order->get_total_tax();

							$order_invoice_type = get_post_meta( $id_of_order, '_billing_invoice_type', true );

							$insert_taxonomy = 'receipt_status';
							$invoice_term    = '';

							if ( $order_invoice_type == 'receipt' && $order_country == 'GR' ) {
								$invoice_term = 'greek_receipt';
							}
							if ( $order_invoice_type == 'receipt' && $order_country !== 'GR' ) {
								$invoice_term = 'english_receipt';
							}
							if ( $order_invoice_type == 'invoice' && $order_country == 'GR' ) {
								$invoice_term = 'greek_invoice';
							}
							if ( $order_invoice_type == 'invoice' && $order_country !== 'GR' ) {
								$invoice_term = 'english_invoice';
							}

							$user_data = $user ? $user_full_name : $user->display_name;

							$user_order_email = $order->get_billing_email();

							$currency        = $order->get_currency();
							$currency_symbol = get_woocommerce_currency_symbol( $currency );
							$payment_method  = $order->get_payment_method();
							$payment_title   = $order->get_payment_method_title();
							$order_status    = $order->get_status();

							if ( $currency == 'EUR' ) {
								if ( $tax != '0' ) {
									$post_id = wp_insert_post( array(
										'post_type'      => 'primer_receipt',
										'post_title'     => 'Receipt for order #' . $id_of_order,
										'comment_status' => 'closed',
										'ping_status'    => 'closed',
										'post_status'    => 'publish',
									) );

									wp_set_object_terms( $post_id, $invoice_term, $insert_taxonomy, false );

									if ( $post_id ) {
										$post_issued = 'issued';
										if ( empty( $user_data ) ) {
											$post_issued                  = 'not_issued';
											$receipt_log_automation_value .= __( 'Order Client name is required!', 'primer' );
										}

										update_post_meta( $post_id, 'receipt_status', $post_issued );
										update_post_meta( $post_id, 'order_id_to_receipt', $id_of_order );
										update_post_meta( $id_of_order, 'receipt_status', $post_issued );
										add_post_meta( $post_id, 'receipt_client', $user_data );
										add_post_meta( $post_id, 'receipt_client_id', $user_id );
										add_post_meta( $post_id, 'receipt_price', $order_total_price . ' ' . $currency_symbol );
										foreach ( $order->get_items() as $item_id => $item_data ) {
											$product_name = $item_data->get_name();
											add_post_meta( $post_id, 'receipt_product', $product_name );
										}

										$post_url = get_the_permalink($post_id);
										$homepage = file_get_contents($post_url);

										// instantiate and use the dompdf class
										$dompdf = new Dompdf();
										$options= $dompdf->getOptions();
										$options->setIsHtml5ParserEnabled(true);
										$dompdf->setOptions($options);

										$dompdf->loadHtml($homepage);

										// Render the HTML as PDF
										$dompdf->render();

										$upload_dir = wp_upload_dir()['basedir'];

										if (!file_exists($upload_dir . '/email-invoices')) {
											mkdir($upload_dir . '/email-invoices');
										}
										$post_name = get_the_title($post_id);
										$post_name = str_replace(' ', '_', $post_name);
										$post_name = str_replace('#', '', $post_name);
										$post_name = strtolower($post_name);

										$output = $dompdf->output();
										file_put_contents($upload_dir . '/email-invoices/'.$post_name.'.pdf', $output);

										$attachments = $upload_dir . '/email-invoices/'.$post_name.'.pdf';

										$user_email = $user ? $user_order_email : $user->user_email;

										$primer_smtp_options = get_option('primer_emails');

										$headers = 'From: ' . $primer_smtp_options['from_email_field'] ? $primer_smtp_options['from_email_field'] : 'Primer '. get_bloginfo('admin_email');
										if (!empty($automation_options['email_subject'])) {
											$primer_smtp_subject = $automation_options['email_subject'];
										} else {
											$primer_smtp_subject = __('Test email subject', 'primer');
										}

										if (!empty($primer_smtp_options['quote_available_content'])) {
											$primer_smtp_message = $primer_smtp_options['quote_available_content'];
										} else {
											$primer_smtp_message = __('Test email message', 'primer');
										}

										$receipt_log_automation_id = wp_insert_post(array(
											'post_type' => 'pr_log_automation',
											'post_title' => 'Receipt automation report for #' . $id_of_order,
											'comment_status' => 'closed',
											'ping_status' => 'closed',
											'post_status' => 'publish',
										));

										if ($receipt_log_automation_id) {
											$invoice_date = get_the_date('F j, Y', $post_id);

											update_post_meta($receipt_log_automation_id, 'receipt_log_automation_order_id', $id_of_order);
											update_post_meta($receipt_log_automation_id, 'receipt_log_automation_order_date', $order_paid_date);
											update_post_meta($receipt_log_automation_id, 'receipt_log_automation_invoice_id', $post_id);
											update_post_meta($receipt_log_automation_id, 'receipt_log_automation_invoice_date', $invoice_date);
											update_post_meta($receipt_log_automation_id, 'receipt_log_automation_client', $user_data);
											$get_issue_status = get_post_meta($post_id, 'receipt_status', true);
											if(empty($get_issue_status)) {
												$get_issue_status = 'issued';
											}

											update_post_meta($receipt_log_automation_id, 'receipt_log_automation_status', $get_issue_status);
											update_post_meta($receipt_log_automation_id, 'receipt_log_automation_error', $receipt_log_automation_value);
										}


										$mailResult = false;
										$primer_smtp = PrimerSMTP::get_instance();

										$check_send_email = $automation_options['send_email_to_admin'];
										if (!empty($check_send_email)) {
											if ($check_send_email == 'on') {
												$automation_admin_emails = $automation_options['admin_email'];
												if (!empty($automation_admin_emails)) {
													$admin_emails = explode(',', $automation_admin_emails);
													foreach ( $admin_emails as $admin_email ) {
														$emails[] = trim( sanitize_email($admin_email) );
													}
													if (!empty($emails)) {
														foreach ( $emails as $to_admin_email ) {
															$mailResultSMTP = $primer_smtp->primer_mail_sender($to_admin_email, $primer_smtp_subject, $primer_smtp_message, $attachments);
														}
													}
												}

											}
										}

										if ( !empty($condition_client_email_send) && $condition_client_email_send == 'on' ) {

											$mailResultSMTP = $primer_smtp->primer_mail_sender($user_order_email, $primer_smtp_subject, $primer_smtp_message, $attachments);

											if (!empty($mailResultSMTP['error'])) {
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_email', 'not_sent');
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_email_error', $GLOBALS['phpmailer']->ErrorInfo);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_total_status', 'only_errors');
											} else {
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_email', 'sent');
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_total_status', 'only_issued');
											}

											update_post_meta($post_id, 'exist_error_log', 'exist_log');

										}
									}
								}
							}

						}
					}
				}
			}

		}

	}

	public function intervals($schedules) {
		$schedules['fiveminutes'] = array('interval' => 300, 'display' => __('5 minutes', 'primer'));
		$schedules['tenminutes'] = array('interval' => 600, 'display' => __('10 minutes', 'primer'));
		$schedules['thirtyminutes'] = array('interval' => 1800, 'display' => __('30 minutes', 'primer'));
		return $schedules;
	}

}


function primer_get_keys_labels( $all = true ) {
	$data = [
		'primer_invoice_type' => __('Invoice Type', 'primer'),
		'vat' => __('VAT', 'primer'),
		'store' 	=> __('Profession', 'primer'),
		'company' => __('Company Name', 'primer'),
		'phone_mobile' => __('Mobile Phone Number', 'primer'),
		'doy'   => __('DOY', 'primer')
	];
	if (! $all)
		unset($data['primer_invoice_type']);

	return $data;
}

function primer_return_doy_args() {
	$doy_args = array(
		""     => "Select...",
		"1101" => "ΑΘΗΝΩΝ Α'",
		"1104" => "ΑΘΗΝΩΝ Δ'",
		"1105" => "ΑΘΗΝΩΝ Ε'",
		"1106" => "ΑΘΗΝΩΝ ΣΤ'",
		"1110" => "ΑΘΗΝΩΝ Ι'",
		"1111" => "ΑΘΗΝΩΝ ΙΑ'",
		"1112" => "ΑΘΗΝΩΝ ΙΒ'",
		"1113" => "ΑΘΗΝΩΝ ΙΓ'",
		"1114" => "ΑΘΗΝΩΝ ΙΔ'",
		"1115" => "ΑΘΗΝΩΝ ΙΕ'",
		"1116" => "ΑΘΗΝΩΝ ΙΣΤ'",
		"1117" => "ΑΘΗΝΩΝ ΙΖ'",
		"1118" => "ΑΘΗΝΩΝ ΦΑΒΕ",
		"1124" => "ΑΘΗΝΩΝ ΙΗ'",
		"1125" => "ΚΑΤΟΙΚΩΝ ΕΞΩΤΕΡΙΚΟΥ",
		"1126" => "ΑΘΗΝΩΝ ΙΘ'",
		"1129" => "ΑΓ. ΔΗΜΗΤΡΙΟΥ",
		"1130" => "ΚΑΛΛΙΘΕΑΣ Α'",
		"1131" => "ΝΕΑΣ ΙΩΝΙΑΣ",
		"1132" => "ΝΕΑΣ ΣΜΥΡΝΗΣ",
		"1133" => "ΠΑΛΑΙΟΥ ΦΑΛΗΡΟΥ",
		"1134" => "ΧΑΛΑΝΔΡΙΟΥ",
		"1135" => "ΑΜΑΡΟΥΣΙΟΥ",
		"1136" => "ΑΓΙΩΝ ΑΝΑΡΓΥΡΩΝ",
		"1137" => "ΑΙΓΑΛΕΩ",
		"1138" => "ΠΕΡΙΣΤΕΡΙΟΥ Α'",
		"1139" => "ΓΛΥΦΑΔΑΣ",
		"1140" => "ΑΘΗΝΩΝ Κ'",
		"1141" => "ΑΘΗΝΩΝ ΚΑ'",
		"1142" => "ΑΘΗΝΩΝ ΚΒ'",
		"1143" => "ΑΘΗΝΩΝ ΚΓ'",
		"1144" => "ΔΑΦΝΗΣ",
		"1145" => "ΗΡΑΚΛΕΙΟΥ ΑΤΤΙΚΗΣ",
		"1151" => "ΑΓΙΑΣ ΠΑΡΑΣΚΕΥΗΣ",
		"1152" => "ΒΥΡΩΝΑ",
		"1153" => "ΚΗΦΙΣΙΑΣ",
		"1154" => "ΙΛΙΟΥ",
		"1155" => "ΝΕΑΣ ΦΙΛΑΔΕΛΦΕΙΑΣ",
		"1156" => "ΧΑΙΔΑΡΙΟΥ",
		"1157" => "ΠΕΡΙΣΤΕΡΙΟΥ Β'",
		"1159" => "ΑΘΗΝΩΝ ΦΑΕΕ",
		"1172" => "ΖΩΓΡΑΦΟΥ",
		"1173" => "ΗΛΙΟΥΠΟΛΗΣ",
		"1174" => "ΚΑΛΛΙΘΕΑΣ Β'",
		"1175" => "ΨΥΧΙΚΟΥ",
		"1176" => "ΧΟΛΑΡΓΟΥ",
		"1177" => "ΑΡΓΥΡΟΥΠΟΛΗΣ",
		"1178" => "ΠΕΤΡΟΥΠΟΛΗΣ",
		"1179" => "ΓΑΛΑΤΣΙΟΥ",
		"1180" => "ΑΝΩ ΛΙΟΣΙΩΝ",
		"1201" => "ΠΕΙΡΑΙΑ Α'",
		"1203" => "ΠΕΙΡΑΙΑ Γ'",
		"1204" => "ΠΕΙΡΑΙΑ Δ'",
		"1205" => "ΠΕΙΡΑΙΑ Ε'",
		"1206" => "ΠΕΙΡΑΙΑ ΦΑΕ",
		"1207" => "ΠΕΙΡΑΙΑ ΠΛΟΙΩΝ",
		"1209" => "ΠΕΙΡΑΙΑ ΣΤ'",
		"1210" => "ΚΟΡΥΔΑΛΛΟΥ",
		"1211" => "ΜΟΣΧΑΤΟΥ",
		"1220" => "ΝΙΚΑΙΑΣ",
		"1301" => "ΑΙΓΙΝΑΣ",
		"1302" => "ΑΧΑΡΝΩΝ",
		"1303" => "ΕΛΕΥΣΙΝΑΣ",
		"1304" => "ΚΟΡΩΠΙΟΥ",
		"1305" => "ΚΥΘΗΡΩΝ",
		"1306" => "ΛΑΥΡΙΟΥ",
		"1307" => "ΑΓΙΟΥ ΣΤΕΦΑΝΟΥ",
		"1308" => "ΜΕΓΑΡΩΝ",
		"1309" => "ΣΑΛΑΜΙΝΑΣ",
		"1310" => "ΠΟΡΟΥ",
		"1311" => "ΥΔΡΑΣ",
		"1312" => "ΠΑΛΛΗΝΗΣ",
		"1411" => "ΘΗΒΑΣ",
		"1421" => "ΛΕΙΒΑΔΙΑΣ",
		"1511" => "ΑΜΦΙΛΟΧΙΑΣ",
		"1521" => "ΑΣΤΑΚΟΥ",
		"1522" => "ΒΟΝΙΤΣΑΣ",
		"1531" => "ΜΕΣΟΛΟΓΓΙΟΥ",
		"1541" => "ΝΑΥΠΑΚΤΟΥ",
		"1551" => "ΘΕΡΜΟΥ",
		"1552" => "ΑΓΡΙΝΙΟΥ",
		"1611" => "ΚΑΡΠΕΝΗΣΙΟΥ",
		"1711" => "ΙΣΤΙΑΙΑΣ",
		"1721" => "ΚΑΡΥΣΤΟΥ",
		"1722" => "ΚΥΜΗΣ",
		"1731" => "ΛΙΜΝΗΣ",
		"1732" => "ΧΑΛΚΙΔΑΣ",
		"1811" => "ΔΟΜΟΚΟΥ",
		"1821" => "ΑΜΦΙΚΛΕΙΑΣ",
		"1822" => "ΑΤΑΛΑΝΤΗΣ",
		"1831" => "ΜΑΚΡΑΚΩΜΗΣ",
		"1832" => "ΛΑΜΙΑΣ",
		"1833" => "ΣΤΥΛΙΔΑΣ",
		"1911" => "ΛΙΔΟΡΙΚΙΟΥ",
		"1912" => "ΑΜΦΙΣΣΑΣ",
		"2111" => "ΑΡΓΟΥΣ",
		"2121" => "ΣΠΕΤΣΩΝ",
		"2122" => "ΚΡΑΝΙΔΙΟΥ",
		"2131" => "ΝΑΥΠΛΙΟΥ",
		"2211" => "ΔΗΜΗΤΣΑΝΑΣ",
		"2213" => "ΛΕΩΝΙΔΙΟΥ",
		"2214" => "ΤΡΟΠΑΙΩΝ",
		"2221" => "ΠΑΡΑΛΙΟΥ ΑΣΤΡΟΥΣ",
		"2231" => "ΤΡΙΠΟΛΗΣ",
		"2241" => "ΜΕΓΑΛΟΠΟΛΗΣ",
		"2311" => "ΑΙΓΙΟΥ",
		"2312" => "ΑΚΡΑΤΑΣ",
		"2321" => "ΚΑΛΑΒΡΥΤΩΝ",
		"2322" => "ΚΛΕΙΤΟΡΙΑΣ",
		"2331" => "ΠΑΤΡΩΝ Α'",
		"2332" => "ΠΑΤΡΩΝ Β'",
		"2333" => "ΚΑΤΩ ΑΧΑΙΑΣ",
		"2334" => "ΠΑΤΡΩΝ Γ'",
		"2411" => "ΑΜΑΛΙΑΔΑΣ",
		"2412" => "ΠΥΡΓΟΥ",
		"2413" => "ΓΑΣΤΟΥΝΗΣ",
		"2414" => "ΒΑΡΔΑ",
		"2421" => "ΚΡΕΣΤΕΝΩΝ",
		"2422" => "ΛΕΧΑΙΝΩΝ",
		"2423" => "ΑΝΔΡΙΤΣΑΙΝΑΣ",
		"2424" => "ΖΑΧΑΡΩΣ",
		"2511" => "ΔΕΡΒΕΝΙΟΥ",
		"2512" => "ΚΙΑΤΟΥ",
		"2513" => "ΚΟΡΙΝΘΟΥ",
		"2514" => "ΝΕΜΕΑΣ",
		"2515" => "ΞΥΛΟΚΑΣΤΡΟΥ",
		"2611" => "ΓΥΘΕΙΟΥ",
		"2621" => "ΜΟΛΑΩΝ",
		"2622" => "ΝΕΑΠΟΛΗΣ ΒΟΙΩΝ ΛΑΚΩΝΙΑΣ",
		"2630" => "ΣΚΑΛΑ ΛΑΚΩΝΙΑΣ",
		"2631" => "ΚΡΟΚΕΩΝ",
		"2632" => "ΣΠΑΡΤΗΣ",
		"2641" => "ΑΡΕΟΠΟΛΗΣ",
		"2711" => "ΚΑΛΑΜΑΤΑΣ",
		"2721" => "ΜΕΛΙΓΑΛΑ",
		"2722" => "ΜΕΣΣΗΝΗΣ",
		"2731" => "ΠΥΛΟΥ",
		"2741" => "ΓΑΡΓΑΛΙΑΝΩΝ",
		"2742" => "ΚΥΠΑΡΙΣΣΙΑΣ",
		"2743" => "ΦΙΛΙΑΤΡΩΝ ΜΕΣΣΗΝΙΑΣ",
		"3111" => "ΚΑΡΔΙΤΣΑΣ",
		"3112" => "ΜΟΥΖΑΚΙΟΥ",
		"3113" => "ΣΟΦΑΔΩΝ",
		"3114" => "ΠΑΛΑΜΑ",
		"3211" => "ΑΓΙΑΣ",
		"3221" => "ΕΛΑΣΣΟΝΑΣ",
		"3222" => "ΔΕΣΚΑΤΗΣ",
		"3231" => "ΛΑΡΙΣΑΣ Α'",
		"3232" => "ΛΑΡΙΣΑΣ Β'",
		"3233" => "ΛΑΡΙΣΑΣ Γ'",
		"3241" => "ΤΥΡΝΑΒΟΥ",
		"3251" => "ΦΑΡΣΑΛΩΝ",
		"3311" => "ΑΛΜΥΡΟΥ",
		"3321" => "ΒΟΛΟΥ Α'",
		"3322" => "ΒΟΛΟΥ Β'",
		"3323" => "ΙΩΝΙΑΣ ΜΑΓΝΗΣΙΑΣ",
		"3331" => "ΣΚΟΠΕΛΟΥ",
		"3332" => "ΣΚΙΑΘΟΥ",
		"3411" => "ΚΑΛΑΜΠΑΚΑΣ",
		"3412" => "ΤΡΙΚΑΛΩΝ",
		"3413" => "ΠΥΛΗΣ",
		"4111" => "ΑΛΕΞΑΝΔΡΕΙΑΣ",
		"4112" => "ΒΕΡΟΙΑΣ",
		"4121" => "ΝΑΟΥΣΑΣ",
		"4211" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Α'",
		"4212" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Β'",
		"4214" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Δ'",
		"4215" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Ε'",
		"4216" => "ΘΕΣΣΑΛΟΝΙΚΗΣ ΣΤ'",
		"4217" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Ζ'",
		"4221" => "ΖΑΓΚΛΙΒΕΡΙΟΥ",
		"4222" => "ΛΑΓΚΑΔΑ",
		"4223" => "ΣΩΧΟΥ",
		"4224" => "ΘΕΣΣΑΛΟΝΙΚΗΣ ΦΑΕ",
		"4225" => "ΝΕΑΠΟΛΗΣ ΘΕΣ/ΝΙΚΗΣ",
		"4226" => "ΤΟΥΜΠΑΣ",
		"4227" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Ι'",
		"4228" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Η'",
		"4229" => "ΘΕΣΣΑΛΟΝΙΚΗΣ Θ'",
		"4231" => "ΑΓ. ΑΘΑΝΑΣΙΟΥ",
		"4232" => "ΚΑΛΑΜΑΡΙΑΣ",
		"4233" => "ΑΜΠΕΛΟΚΗΠΩΝ",
		"4234" => "Ν.ΙΩΝΙΑΣ ΘΕΣ/ΚΗΣ",
		"4311" => "ΚΑΣΤΟΡΙΑΣ",
		"4312" => "ΝΕΣΤΟΡΙΟΥ",
		"4313" => "ΑΡΓΟΥΣ ΟΡΕΣΤΙΚΟΥ",
		"4411" => "ΚΙΛΚΙΣ",
		"4421" => "ΓΟΥΜΕΝΙΣΣΑΣ",
		"4521" => "ΓΡΕΒΕΝΩΝ",
		"4511" => "ΝΕΑΠΟΛΗΣ ΒΟΙΟΥ",
		"4531" => "ΠΤΟΛΕΜΑΙΔΑΣ",
		"4541" => "ΚΟΖΑΝΗ",
		"4542" => "ΣΕΡΒΙΩΝ",
		"4543" => "ΣΙΑΤΙΣΤΑΣ",
		"4611" => "ΑΡΙΔΑΙΑΣ",
		"4621" => "ΓΙΑΝΝΙΤΣΩΝ",
		"4631" => "ΕΔΕΣΣΑΣ",
		"4641" => "ΣΚΥΔΡΑΣ",
		"4711" => "ΚΑΤΕΡΙΝΗΣ Α'",
		"4712" => "ΚΑΤΕΡΙΝΗΣ Β'",
		"4714" => "ΑΙΓΙΝΙΟΥ",
		"4811" => "ΑΜΥΝΤΑΙΟΥ",
		"4812" => "ΦΛΩΡΙΝΑΣ",
		"4911" => "ΑΡΝΑΙΑΣ",
		"4921" => "ΚΑΣΣΑΝΔΡΑΣ",
		"4922" => "ΠΟΛΥΓΥΡΟΥ",
		"4923" => "ΝΕΩΝ ΜΟΥΔΑΝΙΩΝ",
		"5111" => "ΔΡΑΜΑΣ",
		"5112" => "ΝΕΥΡΟΚΟΠΙΟΥ",
		"5211" => "ΑΛΕΞΑΝΔΡΟΥΠΟΛΗΣ",
		"5221" => "ΔΙΔΥΜΟΤΕΙΧΟΥ",
		"5231" => "ΟΡΕΣΤΕΙΑΔΑΣ",
		"5241" => "ΣΟΥΦΛΙΟΥ",
		"5311" => "ΘΑΣΟΥ",
		"5321" => "ΚΑΒΑΛΑΣ Α'",
		"5322" => "ΚΑΒΑΛΑΣ Β'",
		"5331" => "ΧΡΥΣΟΥΠΟΛΗΣ",
		"5341" => "ΕΛΕΥΘΕΡΟΥΠΟΛΗΣ",
		"5411" => "ΞΑΝΘΗΣ Α'",
		"5412" => "ΞΑΝΘΗΣ Β'",
		"5511" => "ΚΟΜΟΤΗΝΗΣ",
		"5521" => "ΣΑΠΠΩΝ",
		"5611" => "ΝΙΓΡΙΤΑΣ",
		"5621" => "ΣΕΡΡΩΝ Α'",
		"5622" => "ΣΕΡΡΩΝ Β'",
		"5631" => "ΣΙΔΗΡΟΚΑΣΤΡΟΥ",
		"5632" => "ΗΡΑΚΛΕΙΑΣ",
		"5641" => "ΝΕΑΣ ΖΙΧΝΗΣ",
		"6111" => "ΑΡΤΑΣ",
		"6113" => "ΦΙΛΙΠΠΙΑΔΑΣ",
		"6211" => "ΗΓΟΥΜΕΝΙΤΣΑΣ",
		"6231" => "ΠΑΡΑΜΥΘΙΑΣ",
		"6241" => "ΦΙΛΙΑΤΩΝ",
		"6221" => "ΠΑΡΓΑΣ",
		"6222" => "ΦΑΝΑΡΙΟΥ",
		"6411" => "ΠΡΕΒΕΖΑΣ",
		"6311" => "ΙΩΑΝΝΙΝΩΝ Α'",
		"6312" => "ΙΩΑΝΝΙΝΩΝ Β'",
		"6313" => "ΔΕΛΒΙΝΑΚΙΟΥ",
		"6315" => "ΜΕΤΣΟΒΟΥ",
		"6321" => "ΚΟΝΙΤΣΑΣ",
		"7111" => "ΑΝΔΡΟΥ",
		"7121" => "ΘΗΡΑΣ",
		"7131" => "ΚΕΑΣ",
		"7141" => "ΜΗΛΟΥ",
		"7151" => "ΝΑΞΟΥ",
		"7161" => "ΠΑΡΟΥ",
		"7171" => "ΣΥΡΟΥ",
		"7172" => "ΜΥΚΟΝΟΥ",
		"7181" => "ΤΗΝΟΥ",
		"7211" => "ΛΗΜΝΟΥ",
		"7221" => "ΚΑΛΛΟΝΗΣ",
		"7222" => "ΜΗΘΥΜΝΑΣ",
		"7231" => "ΜΥΤΙΛΗΝΗΣ",
		"7241" => "ΠΛΩΜΑΡΙΟΥ",
		"7311" => "ΑΓ. ΚΗΡΥΚΟΥ ΙΚΑΡΙΑΣ",
		"7321" => "ΚΑΡΛΟΒΑΣΙΟΥ",
		"7322" => "ΣΑΜΟΥ",
		"7411" => "ΧΙΟΥ",
		"7511" => "ΚΑΛΥΜΝΟΥ",
		"7512" => "ΛΕΡΟΥ",
		"7521" => "ΚΑΡΠΑΘΟΥ",
		"7531" => "ΚΩ",
		"7542" => "ΡΟΔΟΥ",
		"8111" => "ΗΡΑΚΛΕΙΟΥ Α'",
		"8112" => "ΜΟΙΡΩΝ",
		"8113" => "ΗΡΑΚΛΕΙΟΥ Β'",
		"8114" => "ΤΥΜΠΑΚΙΟΥ",
		"8115" => "ΛΙΜΕΝΑ ΧΕΡΣΟΝΗΣΟΥ",
		"8121" => "ΚΑΣΤΕΛΙΟΥ ΠΕΔΙΑΔΟΣ",
		"8131" => "ΑΡΚΑΛΟΧΩΡΙΟΥ",
		"8211" => "ΙΕΡΑΠΕΤΡΑΣ",
		"8221" => "ΑΓΙΟΥ ΝΙΚΟΛΑΟΥ",
		"8231" => "ΝΕΑΠΟΛΗΣ ΚΡΗΤΗΣ",
		"8241" => "ΣΗΤΕΙΑΣ",
		"8341" => "ΡΕΘΥΜΝΟΥ",
		"8421" => "ΚΙΣΣΑΜΟΥ",
		"8431" => "ΧΑΝΙΩΝ Α'",
		"8432" => "ΧΑΝΙΩΝ Β'",
		"9111" => "ΖΑΚΥΝΘΟΥ",
		"9211" => "ΚΕΡΚΥΡΑΣ Α'",
		"9212" => "ΚΕΡΚΥΡΑΣ Β'",
		"9221" => "ΠΑΞΩΝ",
		"9311" => "ΑΡΓΟΣΤΟΛΙΟΥ",
		"9321" => "ΛΗΞΟΥΡΙΟΥ",
		"9411" => "ΙΘΑΚΗΣ",
		"9421" => "ΛΕΥΚΑΔΑΣ",
	);
	return $doy_args;
}
