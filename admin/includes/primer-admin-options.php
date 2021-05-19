<?php

// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }

require_once PRIMER_PATH . 'admin/includes/primer-admin-table.php';

require_once PRIMER_PATH . 'includes/class-primer-smtp.php';

class Primer_Options {

	/**
	 * Default Option key
	 * @var string
	 */
	private $key = 'primer_options';

	/**
	 * Array of metaboxes/fields
	 * @var array
	 */
	public $option_metabox = array();

	/**
	 * Options Tab Pages
	 * @var array
	 */
	public $options_pages = array();

	/**
	 * Options Page title
	 * @var string
	 */
	protected $menu_title = '';

	public function __construct() {

		add_action('admin_menu', array(&$this, 'menu'));
		add_filter('show_admin_bar', array(&$this, 'hide_adminbar'));

//		add_action('save_post', array(&$this, 'save_postdata'));

//		add_action( 'admin_init', array( $this, 'init' ), 999 );

		//init is too early for settings api.
		add_action('plugins_loaded', array(&$this, "plugins_loaded"));

		add_action('wp_print_scripts', array(&$this, 'data_include_script'));
		add_action('wp_ajax_create_primer_the_zip_file', array(&$this, 'create_primer_the_zip_file'));
		add_action('wp_ajax_primer_export_receipt_to_html', array(&$this, 'primer_export_receipt_to_html'));

		add_action('wp_ajax_primer_smtp_settings', array(&$this, 'primer_smtp_settings'));

		$this->menu_title = __( 'Primer Receipts', 'primer' );
	}

	/**
	 * Register our setting tabs to WP
	 * @since  0.1.0
	 */
	public function init() {
		$option_tabs = self::primer_option_fields();
		foreach ($option_tabs as $index => $option_tab) {
			register_setting( $option_tab['id'], $option_tab['id'] );
		}
	}

	public function hide_adminbar() {

		//Never show admin toolbar if the user is not even logged in
		if (!is_user_logged_in()) {
			return false;
		}

		if (current_user_can('administrator')) {
			//This is an admin user so show the tooldbar
			return true;
		}

		return false;
	}


	public function menu() {
		$menu_parent_slug = 'primer_receipts';

		$option_tabs = self::primer_option_fields();

		add_menu_page(__('Primer Receipts', 'primer'), __('Primer Receipts', 'primer'), 'manage_options', 'wp_ajax_list_order', array(&$this, "admin_page_display"), 'dashicons-printer');
		add_submenu_page('wp_ajax_list_order', __('Orders', 'primer'), __('Orders', 'primer'), 'manage_options', 'wp_ajax_list_order', array(&$this, "admin_page_display"));
		add_submenu_page('wp_ajax_list_order', __('Receipts', 'primer'), __('Receipts', 'primer'), 'manage_options', 'primer_receipts', array(&$this, "admin_page_receipt_display"));
		add_submenu_page('wp_ajax_list_order', __('Settings', 'primer'), __('Settings', 'primer'), 'manage_options', 'primer_settings', array(&$this, "admin_settings_page_display"));

		add_submenu_page('wp_ajax_list_order', __('Export', 'primer'), __('Export', 'primer'), 'manage_options', 'primer_export', array(&$this, "admin_settings_page_display"));

		add_submenu_page('wp_ajax_list_order', __('License and General Settings', 'primer'), __('License and General Settings', 'primer'), 'manage_options', 'primer_license', array(&$this, "admin_settings_page_display"));

		$this->options_pages[] = add_submenu_page( null,  $this->menu_title, 'MyData Settings', 'manage_options', 'primer_mydata', array( $this, 'admin_settings_page_display' ));
		$this->options_pages[] = add_submenu_page( null,  $this->menu_title, 'Automation Settings', 'manage_options', 'primer_automation', array( $this, 'admin_settings_page_display' ));
		$this->options_pages[] = add_submenu_page( null,  $this->menu_title, 'Email Settings', 'manage_options', 'primer_email', array( $this, 'admin_settings_page_display' ));

		do_action('primer_after_main_admin_menu', $menu_parent_slug);

		// Include CMB CSS in the head to avoid FOUC
		foreach ( $this->options_pages as $page ) {
			add_action( "admin_print_styles-{$page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
		}

	}

	/**
	 * Admin page markup. Mostly handled by CMB
	 * @since  0.1.0
	 */
	public function admin_settings_page_display() {
		global $pagenow;

		// check we are on the network settings page
		if( $pagenow != 'admin.php' ) {
			return;
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'primer_license' ) {
			$current_tab = 'license';
		} else {
			$current_tab = empty( $_GET['tab'] ) ? 'mydata' : sanitize_title( $_GET['tab'] );
		}

		$option_tabs = self::primer_option_fields(); //get all option tabs
		$tab_forms = array();

		?>

		<div class="wrap cmb2_priemr_options_page <?php echo esc_attr($this->key); ?>">

			<h2><?php esc_html_e( $this->menu_title, 'primer' ) ?></h2>

			<!-- Options Page Nav Tabs -->
			<h2 class="nav-tab-wrapper">
				<?php foreach ($option_tabs as $option_tab) :
					$tab_slug = $option_tab['id'];
					$nav_class = 'i18n-multilingual-display nav-tab';
					if ( $tab_slug === 'primer_'.$current_tab ) {
						$nav_class .= ' nav-tab-active'; //add active class to current tab
						$tab_forms[] = $option_tab; //add current tab to forms to be rendered
					}
					if ( $tab_slug === 'primer_license' ) {
						$admin_url = admin_url( 'admin.php?page='.$tab_slug );
					} else {
						$admin_url = admin_url( 'admin.php?page=primer_settings&tab=' . str_replace( 'primer_', '', $tab_slug ) );
					}
					?>
					<a class="<?php echo esc_attr( $nav_class ); ?>" href="<?php echo $admin_url; ?>"><?php esc_attr_e( $option_tab['title'], 'primer' ); ?></a>
				<?php endforeach; ?>
			</h2>

			<!-- End of Nav Tabs -->
			<?php foreach ($tab_forms as $tab_form) : //render all tab forms (normaly just 1 form) ?>
				<div id="<?php esc_attr_e($tab_form['id']); ?>" class="cmb-form group">
					<div class="metabox-holder">
						<div class="postbox">
							<h3 class="title"><?php esc_html_e($tab_form['title'], 'primer'); ?></h3>
							<div class="desc"><?php echo $tab_form['desc'] ?></div>
							<?php cmb2_metabox_form( $tab_form, $tab_form['id'] ); ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php }



	/**
	 * Defines the theme option metabox and field configuration
	 * @since  0.1.0
	 * @return array
	 */
	public function primer_option_fields() {

		// Only need to initiate the array once per page-load
		if ( !empty( $this->option_metabox ) ) {
			return $this->option_metabox;
		}

		$prefix = 'primer_';
		$current_user = wp_get_current_user();

		$this->option_metabox[] = apply_filters( 'primer_mydata_option_fields', array(
			'id'			=> $prefix . 'mydata',
			'title'			=> __( 'MyData Settings', 'primer' ),
			'menu_title'	=> __( 'MyData Settings', 'primer' ),
			'desc'			=> __( '', 'primer' ),
			'show_on'    	=> array( 'key' => 'options-page', 'value' => array( 'mydata' ), ),
			'show_names' 	=> true,
			'fields'		=> array(

				array(
					'name'		=> __( 'Invoice Settings', 'primer' ),
					'desc'		=> __('', 'primer'),
					'default'	=> '',
					'id'		=> 'title_invoice_settings',
					'type'		=> 'title',
				),

				array(
					'name'      => __( 'Invoice name', 'primer' ),
					'desc'      => __( 'Use this name to corresponding invoice field', 'primer' ),
					'default'   => '',
					'id'        => 'invoice_name',
					'type'      => 'text',
				),

				array(
					'name'      => __( 'Invoice Type', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'default'   => '',
					'id'        => 'invoice_type',
					'type'      => 'select',
					'options'	=> array('' => __( 'Select invoice type', 'primer' )),
				),


				array(
					'name'		=> __('Add Company Logo', 'primer'),
					'desc'		=> __('Jpg,Png files only. File must be 256x256px and up to 75 kb. Up to 3 logo changes (after pressing save) are supported.', 'primer'),
					'id'		=> 'logo',
					'type'		=> 'file',
					'options' => array(
						'url' => false, // Hide the text input for the url
					),
					'text'    => array(
						'add_upload_file_text' => 'Upload File' // Change upload button text. Default: "Add or Upload File"
					),
					'allow'		=> array('url', 'attachment'),
					'query_args' => array(
						'type' => array(
							'image/jpeg',
							'image/png',
						)
					),
				),

				array(
					'name' 		=> __( 'Select Greek invoice template', 'primer' ),
					'desc'		=> '',
					'id'		=> 'greek_template',
					'type'		=> 'select',
					'options'	=> array('' => __( 'Select Greek invoice template', 'primer' ), 'greek_template1' => __( 'Greek receipt template 1', 'primer' )),
					'after_field' => '
						<a href="#" class="preview">'.__('Preview template', 'primer').'</a>
					',
				),

				array(
					'name' 		=> __( 'Select English invoice template', 'primer' ),
					'desc'		=> '',
					'id'		=> 'english_template',
					'type'		=> 'select',
					'options'	=> array('' => __( 'Select English invoice template', 'primer' ), 'english_template1' => __( 'English receipt template 1', 'primer' )),
					'after_field' => '
						<a href="'.plugins_url('/primer/public/partials/invoicetemplate_defaultA4.php').'" target="_blank" class="preview">'.__('Preview template', 'primer').'</a>
					',
				),

				array(
					'name'		=> __( 'VAT Settings:', 'primer' ),
					'desc'		=> __('', 'primer'),
					'default'	=> '',
					'id'		=> 'title_vat_settings',
					'type'		=> 'title',
				),

				array(
					'name'		=> __( 'VAT %', 'primer' ),
					'desc'		=> __('', 'primer'),
					'default'	=> __( 'Woocommerce VAT category', 'primer' ),
					'type'		=> 'text',
					'id'		=> 'vat_percents',
					'attributes' => array(
						'name'	=> '',
						'readonly' => 'readonly',
						'class' => 'regular-text input_title'
					)

				),

				array(
					'name'		=> __( '24%', 'primer' ),
					'desc'		=> __('', 'primer'),
					'default'	=> '',
					'type'		=> 'select',
					'id'		=> 'standard_vat_rates',
					'options'	=> $this->get_standard_rates(),
				),

				array(
					'name'		=> __( '17%', 'primer' ),
					'desc'		=> __('', 'primer'),
					'default'	=> '',
					'type'		=> 'select',
					'id'		=> 'seventeen_vat_rates',
					'options'	=> $this->get_standard_rates(),
				),

				array(
					'name'		=> __( '13%', 'primer' ),
					'desc'		=> __('', 'primer'),
					'default'	=> '',
					'type'		=> 'select',
					'id'		=> 'thirteen_vat_rates',
					'options'	=> $this->get_standard_rates(),
				),

				array(
					'name'		=> __( '9%', 'primer' ),
					'desc'		=> __('', 'primer'),
					'default'	=> '',
					'type'		=> 'select',
					'id'		=> 'nine_vat_rates',
					'options'	=> $this->get_standard_rates(),
				),

				array(
					'name'		=> __( '6%', 'primer' ),
					'desc'		=> __('', 'primer'),
					'default'	=> '',
					'type'		=> 'select',
					'id'		=> 'six_vat_rates',
					'options'	=> $this->get_standard_rates(),
				),

				array(
					'name'		=> __( '4%', 'primer' ),
					'desc'		=> __('', 'primer'),
					'default'	=> '',
					'type'		=> 'select',
					'id'		=> 'four_vat_rates',
					'options'	=> $this->get_standard_rates(),
				),
			)
		) );

		$this->option_metabox[] = apply_filters( 'primer_email_option_fields', array(
			'id'		=> $prefix . 'emails',
			'title'		=> __( 'Email Settings', 'primer' ),
			'menu_title'		=> __( 'Email Settings', 'primer' ),
			'desc'				=> __( '', 'primer' ),
			'show_on'    => array( 'key' => 'options-page', 'value' => array( 'emails' ), ),
			'show_names' => true,
			'fields'		=> array(
				array(
					'name'		=> __( 'Email SMTP settings', 'primer' ),
					'desc'		=> __('', 'primer'),
					'default'	=> '',
					'id'		=> 'title_email_smtp_settings',
					'type'		=> 'title',
				),
				array(
					'name'		=> __( 'Send email from account', 'primer' ),
					'desc'		=> __( '', 'primer' ),
					'default'	=> __( '', 'primer' ),
					'type'	=> 'text_email',
					'id'	=> 'send_from',
				),
				array(
					'name'      => __( 'Email username', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'default'   => '',
					'type'      => 'text',
					'id'        => 'name',
				),
				array(
					'name'      => __( 'Email password', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'default'   => '',
					'type'      => 'text',
					'id'        => 'password',
					'attributes' => array(
						'type' => 'password',
					),
				),
				array(
					'name'      => __( 'SMTP server', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'default'   => '',
					'type'      => 'text',
					'id'        => 'smtp_server',
				),
				array(
					'name'      => __( 'Port', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'default'   => '',
					'type'      => 'text',
					'id'        => 'port',
					'after_row'		=> '<button type="button" name="primer_smtp_form_submit" class="button badge-danger send_tested_email">'.__('Test Email settings', 'primer').'</button>',
				),


				array(
					'name'		=> __( 'Email settings', 'primer' ),
					'desc'		=> __('Settings for the emails send to your clients', 'primer'),
					'default'	=> '',
					'id'		=> 'title_email_settings',
					'type'		=> 'title',
				),
				array(
					'name'      => __( 'Email subject', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'default'   => '',
					'type'      => 'text',
					'id'        => 'email_subject',
				),
				array(
					'name'      => __( 'Email body', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'type'      => 'wysiwyg',
					'default'   => '',
					'id'        => 'quote_available_content',
					'sanitization_cb' => false,
					'options' => array(
						'media_buttons' => false,
						'textarea_rows' => get_option('default_post_edit_rows', 7),
						'teeny' => true,
						'tinymce' => true,
						'quicktags' => true
					),
				),
			)
		) );

		$checkbox = array(
			'name'	=> __( 'Activate Automation', 'primer' ),
			'desc' => __( 'Activate Automation', 'primer' ),
			'type'	=> 'checkbox',
			'id'	=> 'activation_automation',
		);

		$this->option_metabox[] = apply_filters( 'primer_automation_option_fields', array(
			'id'			=> $prefix . 'automation',
			'title'			=> __( 'Automation Settings', 'primer' ),
			'menu_title'	=> __( 'Automation Settings', 'primer' ),
			'desc'			=> __( '', 'primer' ),
			'show_on'    	=> array( 'key' => 'options-page', 'value' => array( 'automation' ), ),
			'show_names' 	=> true,
			'fields'		=> array(
				array(
					'id'          => $prefix . 'demo',
					'type'        => 'group',
					'description' => '',
					'options'     => array(
						'group_title'   => __( 'Condition {#}', 'primer' ), // {#} gets replaced by row number
						'add_button'    => __( '+Add condition', 'primer' ),
						'remove_button' => __( 'Delete condition', 'primer' ),
						'sortable'      => false,
					),
					'fields' => array(
						array(
							'name'       => __( 'Entry Title', 'cmb2' ),
							'id'         => 'title',
							'type'       => 'text',
						)
					)
				)
			)
		) );

		return $this->option_metabox;

	}


	/**
	 * Get the list of Woocommerce Standard rates to add to dropdowns in the settings.
	 *
	 * @since   1.0.0
	 */
	public function get_standard_rates() {
		$all_standard_tax_rates = array( '0' => __('Select Standard VAT rates', 'primer') );
		$tax_classes   = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
		if ( ! in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
			array_unshift( $tax_classes, '' );
		}
		foreach ( $tax_classes as $tax_class ) { // For each tax class, get all rates.
			$taxes         = WC_Tax::get_rates_for_tax_class( $tax_class );
			foreach ( $taxes as $tax ) {
				$tax_rate_class = $tax->tax_rate_class;
				if (empty($tax_rate_class)) {
					$tax_rate_id = $tax->tax_rate_id;
					$tax_rate_name = $tax->tax_rate_name;
					$all_standard_tax_rates[$tax_rate_id] = $tax_rate_name;
				}
			}
		}

		return $all_standard_tax_rates;
	}

	/**
	 * Get the list of Woocommerce Reduced rates to add to dropdowns in the settings.
	 *
	 * @since   1.0.0
	 */
	public function get_reduced_rates() {
		$all_reduced_tax_rates = array( '0' => __('Select Reduced VAT rates', 'primer') );

		$tax_classes   = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
		if ( ! in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
			array_unshift( $tax_classes, '' );
		}
		foreach ( $tax_classes as $tax_class ) { // For each tax class, get all rates.
			$taxes         = WC_Tax::get_rates_for_tax_class( $tax_class );
			foreach ( $taxes as $tax ) {
				$tax_rate_class = $tax->tax_rate_class;
				if (!empty($tax_rate_class) && $tax_rate_class == 'reduced-rate') {
					$tax_rate_id = $tax->tax_rate_id;
					$tax_rate_name = $tax->tax_rate_name;
					$all_reduced_tax_rates[$tax_rate_id] = $tax_rate_name;
				}
			}
		}

		return $all_reduced_tax_rates;
	}

	/**
	 * Get the list of Woocommerce Zero rates to add to dropdowns in the settings.
	 *
	 * @since   1.0.0
	 */
	public function get_zero_rates() {
		$all_zero_tax_rates = array( '0' => __('Select Zero VAT rates', 'primer') );

		$tax_classes   = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
		if ( ! in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
			array_unshift( $tax_classes, '' );
		}
		foreach ( $tax_classes as $tax_class ) { // For each tax class, get all rates.
			$taxes         = WC_Tax::get_rates_for_tax_class( $tax_class );
			foreach ( $taxes as $tax ) {
				$tax_rate_class = $tax->tax_rate_class;
				if (!empty($tax_rate_class) && $tax_rate_class == 'zero-rate') {
					$tax_rate_id = $tax->tax_rate_id;
					$tax_rate_name = $tax->tax_rate_name;
					$all_zero_tax_rates[$tax_rate_id] = $tax_rate_name;
				}
			}
		}

		return $all_zero_tax_rates;
	}


	/**
	 * Returns the option key for a given field id
	 * @since  0.1.0
	 * @return array
	 */
	public function primer_get_option_key($field_id) {
		$option_tabs = $this->primer_option_fields();
		foreach ( $option_tabs as $option_tab ) { //search all tabs
			foreach ( $option_tab['fields'] as $field ) { //search all fields
				if ($field['id'] == $field_id) {
					return $option_tab['id'];
				}
			}
		}
		return $this->key; //return default key if field id not found
	}

	/**
	 * Public getter method for retrieving protected/private variables
	 * @since  0.1.0
	 * @param  string  $field Field to retrieve
	 * @return mixed          Field value or exception is thrown
	 */
	public function __get( $field ) {

		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'fields', 'menu_title', 'options_pages' ), true ) ) {
			return $this->{$field};
		}
		if ( 'option_metabox' === $field ) {
			return $this->primer_option_fields();
		}

		throw new Exception( 'Invalid property: ' . $field );
	}



	/* Render the Primer menu in admin dashboard */
	public function admin_page_display() {
		$primer = new PrimerReceipts();
		$primer->handle_main_primer_admin_menu();
	}

	public function admin_page_receipt_display() {
		include_once(PRIMER_PATH . 'admin/includes/primer-admin-receipt-table.php');
		$primer_receipt = new PrimerReceipt();
		$primer_receipt->handle_main_primer_receipt_admin_menu();
	}

	public function primer_export_receipt_to_html() {
		$receipt_ids = isset($_POST['page_id']) ? $_POST['page_id'] : "";

		$receipt_ids = explode(', ', $receipt_ids);

		$response = $this->export_receipt_as_static_html_by_page_id($receipt_ids);
		echo json_encode(array('success' => 'true', 'status' => 'success', 'response' => $response));

		die();
	}

	public function rmdir_recursive($dir) {
		foreach(scandir($dir) as $file) {
			if ('.' === $file || '..' === $file) continue;
			if (is_dir("$dir/$file")) $this->rmdir_recursive("$dir/$file");
			else unlink("$dir/$file");
		}
		rmdir($dir);
	}

	public function export_receipt_as_static_html_by_page_id($page_ids) {
		if (!empty($page_ids)) {

			$upload_dir = wp_upload_dir()['basedir'];

			if (!file_exists($upload_dir . '/exported_html_files')) {
				mkdir($upload_dir . '/exported_html_files');
			}

			if (!file_exists($upload_dir . '/exported_html_files/tmp_files')) {
				mkdir($upload_dir . '/exported_html_files/tmp_files');
			} else {
				$this->rmdir_recursive($upload_dir . '/exported_html_files/tmp_files');
				mkdir($upload_dir . '/exported_html_files/tmp_files');
			}

			foreach ( $page_ids as $page_id ) {
				$main_url = get_permalink($page_id);

				$parse_url = parse_url($main_url);
				$scheme = $parse_url['scheme'];
				$host = $scheme . '://' . $parse_url['host'];

				$post_name = get_the_title($page_id);
				$post_name = str_replace(' ', '_', $post_name);
				$post_name = str_replace('#', '', $post_name);
				$post_name = strtolower($post_name);

				$src = $this->get_site_data_by_url($main_url);


				file_put_contents($upload_dir . '/exported_html_files/tmp_files/'.$post_name.'.html', $src);
			}

		}

		return true;
	}

	public function data_include_script() {
		?>
		<script>
            /* <![CDATA[ */
            var primer = {
                "ajax_url":"<?php echo admin_url('admin-ajax.php'); ?>",
            }
            /* ]]\> */
		</script>
	<?php
	}

	public function create_zip($files = array(), $destination = '', $replace_path = "", $overwrite = true) {
		//if the zip file already exists and overwrite is false, return false
		if(file_exists($destination) && !$overwrite) { return false; }
		//vars
		$valid_files = array();
		//if files were passed in...
		if(is_array($files)) {
			//cycle through each file
			foreach($files as $file) {
				//make sure the file exists
				if(file_exists($file)) {
					if (is_file($file)) {
						$valid_files[] = $file;
					}

				}
			}
		}
		//if we have good files...
		if(count($valid_files)) {

			//create the archive
			$overwrite = file_exists($destination) ? true : false ;
			$zip = new ZipArchive();
			if($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
				return false;
			}

			//add the files
			foreach($valid_files as $file) {
				$filename = str_replace( $replace_path, '', $file);
				$zip->addFile($file, $filename);
			}
			//debug
			//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

			//close the zip -- done!
			$zip->close();

			//check to make sure the file exists
			return file_exists($destination) ? 'created' : 'not' ;
		}
		else
		{
			return false;
		}
	}

	public function create_primer_the_zip_file(){
		$receipt_id = isset($_POST['page_id']) ? $_POST['page_id'] : "";

		global $wpdb;

		$post_name = get_the_title($receipt_id);
		$post_name = str_replace(' ', '_', $post_name);
		$post_name = str_replace('#', '', $post_name);
		$post_name = strtolower($post_name);

		$upload_dir = wp_upload_dir()['basedir'];

		$upload_url = wp_upload_dir()['baseurl'] . '/exported_html_files';

		$all_files = $upload_dir . '/exported_html_files/tmp_files';
		$files = $this->get_all_files_as_array($all_files);

		$zip_file_name = $upload_dir . '/exported_html_files/receipts-html.zip';

		ob_start();
		echo $this->create_zip($files, $zip_file_name, $all_files . '/');
		$create_zip = ob_get_clean();

		if ($create_zip == 'created') {
			$this->rmdir_recursive($upload_dir . '/exported_html_files/tmp_files');
		}

		$response = ($create_zip == 'created') ? $upload_url . '/receipts'.$post_name.'-html.zip' : false;


		echo json_encode(array('success' => 'true', 'status' => 'success', 'response' => $response));

		die();
	}

	public function get_all_files_as_array($all_files){

		function rc_get_sub_dir($dir) {
			foreach(scandir($dir) as $file) {
				if ('.' === $file || '..' === $file) continue;
				if (is_dir("$dir/$file")) rc_get_sub_dir("$dir/$file");
				echo "$dir/$file" . ',';
			}
		}
		ob_start();
		rc_get_sub_dir($all_files);
		$files = ob_get_clean();
		$files = rtrim($files, ',');
		$files = explode(',', $files);


		return $files;
	}

	public function xcurl($url,$print=false,$ref=null,$post=array(),$ua="Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:66.0) Gecko/20100101 Firefox/66.0") {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		if(!empty($ref)) {
			curl_setopt($ch, CURLOPT_REFERER, $ref);
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if(!empty($ua)) {
			curl_setopt($ch, CURLOPT_USERAGENT, $ua);
		}
		if(!empty($post)){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$output = curl_exec($ch);
		curl_close($ch);
		if($print) {
			print($output);
		} else {
			return $output;
		}
	}

	public function get_site_data_by_url($url='')
	{

		$html = file_get_contents($url);

		if (!$html) {
			$html = $this->xcurl($url);
		}

		return $html;
	}

	public function primer_smtp_settings() {
		$primer_smtp = PrimerSMTP::get_instance();
		$enc_req_met  = true;
		$enc_req_err  = '';
		//check if OpenSSL PHP extension is loaded and display warning if it's not
		if ( ! extension_loaded( 'openssl' ) ) {
			$class   = 'notice notice-warning';
			$message = __( "PHP OpenSSL extension is not installed on the server. It's required by Primer SMTP to operate properly. Please contact your server administrator or hosting provider and ask them to install it.", 'primer' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			//also show encryption error message
			$enc_req_err .= __( 'PHP OpenSSL extension is not installed on the server. It is required for encryption to work properly. Please contact your server administrator or hosting provider and ask them to install it.', 'primer' ) . '<br />';
			$enc_req_met  = false;
		}

		//check if server meets encryption requirements
		if ( version_compare( PHP_VERSION, '5.6.0' ) < 0 ) {
			$enc_req_err = ! empty( $enc_req_err ) ? $enc_req_err   .= '<br />' : '';
			// translators: %s is PHP version
			$enc_req_err .= sprintf( __( 'Your PHP version is %s, encryption function requires PHP version 5.6.0 or higher.', 'primer' ), PHP_VERSION );
			$enc_req_met  = false;
		}

		$message = '';
		$error   = '';

		$primer_smtp_options = get_option('primer_emails');
		$smtp_test_mail  = get_option( 'primer_smtp_test_mail' );
		$gag_password    = '#primersmtpgagpass#';
		if ( empty( $smtp_test_mail ) ) {
			$smtp_test_mail = array(
				'primer_smtp_to'      => '',
				'primer_smtp_subject' => '',
				'primer_smtp_message' => '',
			);
		}

//		if ( isset( $_POST['primer_smtp_form_submit'] ) ) {
			/* Update settings */

			if( isset( $_POST['send_from'] ) ) {
				if ( is_email($_POST['send_from'] ) ) {
					$primer_smtp_options['send_from'] = sanitize_email( $_POST['send_from'] );
				} else {
					$error .= ' ' . __( "Please enter a valid email address in the 'Send email from account' field.", 'primer' );
				}
			}

			$primer_smtp_options['reply_to_email'] = sanitize_email( 'test@example.com' );

			$primer_smtp_options['smtp_server']            = stripslashes( $_POST['smtp_server'] );

		$primer_smtp_options['name'] = stripslashes( $_POST['name'] );

		$primer_smtp_password = $_POST['password'];
		if ($primer_smtp_password !== $gag_password) {
			$primer_smtp_options['password'] = $primer_smtp->encrypt_password( $primer_smtp_password );
		}


			/* Check value from "SMTP port" option */
			if ( isset( $_POST['port'] ) ) {
				if ( empty( $_POST['port'] ) || 1 > intval( $_POST['port'] ) || ( ! preg_match( '/^\d+$/', $_POST['port'] ) ) ) {
					$primer_smtp_options['port'] = '25';
					$error .= ' ' . __( "Please enter a valid port in the 'SMTP Port' field.", 'primer' );
				} else {
					$primer_smtp_options['port'] = sanitize_text_field( $_POST['port'] );
				}
			}


			/* Update settings in the database */
			if ( empty( $error ) ) {
				update_option( 'primer_emails', $primer_smtp_options );
				$message .= __( 'Settings saved.', 'primer' );
			} else {
				$error .= ' ' . __( 'Settings are not saved.', 'primer' );
			}

			/* Send test letter */
			$primer_smtp_to = '';
//			if ( isset( $_POST['primer_smtp_form_submit'] ) ) {
				if ( isset($_POST['send_from']) ) {
					$to_email = sanitize_text_field( $_POST['send_from'] );
					if (is_email( $to_email )) {
						$primer_smtp_to = $to_email;
					} else {
						$error .= __( 'Please enter a valid email address in the recipient email field.', 'primer' );
					}
				}
				if (!empty($primer_smtp_options['email_subject'])) {
					$primer_smtp_subject = $primer_smtp_options['email_subject'];
				} else {
					$primer_smtp_subject = __('Test email subject', 'primer');
				}

				if (!empty($primer_smtp_options['quote_available_content'])) {
					$primer_smtp_message = $primer_smtp_options['quote_available_content'];
				} else {
					$primer_smtp_message = __('Test email message', 'primer');
				}

				//Save the test mail details so it doesn't need to be filled in everytime.
				$smtp_test_mail['primer_smtp_to']      = $primer_smtp_to;
				$smtp_test_mail['primer_smtp_subject'] = $primer_smtp_subject;
				$smtp_test_mail['primer_smtp_message'] = $primer_smtp_message;
				update_option( 'primer_smtp_test_mail', $smtp_test_mail );

				if ( !empty( $primer_smtp_to ) ) {
					$test_res = $primer_smtp->test_mail($primer_smtp_to, $primer_smtp_subject, $primer_smtp_message);
				}
//			}

//		}
		wp_die();
	}
}

// Get it started
$Primer_Options = new Primer_Options();


/**
 * Wrapper function around cmb_get_option
 * @since  0.1.0
 * @param  string  $key Options array key
 * @return mixed        Option value
 */
function primer_admin_option( $key = '' ) {
	global $Primer_Options;
	return cmb2_get_option( $Primer_Options->primer_get_option_key($key), $key );
}


function primer_register_repeatable_group_field_metabox() {

	// Start with an underscore to hide fields from custom fields list
	$prefix = 'primer_';

	/**
	 * Repeatable Field Groups
	 */
	$cmb_group = new_cmb2_box( array(
		'id'           => $prefix . 'metabox',
		'title'        => __( 'Repeating Field Group', 'cmb2' ),
		'show_on'    	=> array( 'key' => 'options-page', 'value' => array( 'automation' ), ),
	) );

	// $group_field_id is the field id string, so in this case: $prefix . 'demo'
	$group_field_id = $cmb_group->add_field( array(
		'id'          => $prefix . 'demo',
		'type'        => 'group',
		'description' => __( 'Generates reusable form entries', 'cmb2' ),
		'options'     => array(
			'group_title'   => __( 'Entry {#}', 'cmb2' ), // {#} gets replaced by row number
			'add_button'    => __( 'Add Another Entry', 'cmb2' ),
			'remove_button' => __( 'Remove Entry', 'cmb2' ),
			'sortable'      => true, // beta
		),
	) );

	/**
	 * Group fields works the same, except ids only need
	 * to be unique to the group. Prefix is not needed.
	 *
	 * The parent field's id needs to be passed as the first argument.
	 */
	$cmb_group->add_group_field( $group_field_id, array(
		'name'       => __( 'Entry Title', 'cmb2' ),
		'id'         => 'title',
		'type'       => 'text',
		// 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
	) );

	$cmb_group->add_group_field( $group_field_id, array(
		'name'        => __( 'Description', 'cmb2' ),
		'description' => __( 'Write a short description for this entry', 'cmb2' ),
		'id'          => 'description',
		'type'        => 'textarea_small',
	) );

	$cmb_group->add_group_field( $group_field_id, array(
		'name' => __( 'Entry Image', 'cmb2' ),
		'id'   => 'image',
		'type' => 'file',
	) );

	$cmb_group->add_group_field( $group_field_id, array(
		'name' => __( 'Image Caption', 'cmb2' ),
		'id'   => 'image_caption',
		'type' => 'text',
	) );

	echo '<pre>'; print_r($cmb_group); exit();
	return $cmb_group;

}
