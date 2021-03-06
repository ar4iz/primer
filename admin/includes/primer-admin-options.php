<?php

// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }

require_once PRIMER_PATH . 'admin/includes/primer-admin-table.php';

require_once PRIMER_PATH . 'includes/class-primer-smtp.php';

// reference the Dompdf namespace
use Dompdf\Dompdf;

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
		add_action('wp_ajax_primer_resend_receipt_to_customer', array(&$this, 'primer_resend_receipt_to_customer'));

		add_action('wp_ajax_primer_smtp_settings', array(&$this, 'primer_smtp_settings'));

		add_action('wp_ajax_primer_user_picture_upload', array(&$this, 'primer_user_picture_upload'));

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
		add_submenu_page(null, __('Receipts Logs', 'primer'), __('Receipts Logs', 'primer'), 'manage_options', 'primer_receipts_logs', array(&$this, "admin_page_receipt_log_display"));
		add_submenu_page(null, __('Automation Logs', 'primer'), __('Automation Logs', 'primer'), 'manage_options', 'primer_receipts_logs_automation', array(&$this, "admin_page_receipt_log_automation_display"));
		add_submenu_page('wp_ajax_list_order', __('Settings', 'primer'), __('Settings', 'primer'), 'manage_options', 'primer_settings', array(&$this, "admin_settings_page_display"));

		add_submenu_page('wp_ajax_list_order', __('Export', 'primer'), __('Export', 'primer'), 'manage_options', 'primer_export', array(&$this, "admin_settings_page_display"));

		$this->options_pages[] = add_submenu_page('wp_ajax_list_order', __('License and General Settings', 'primer'), __('License and General Settings', 'primer'), 'manage_options', 'primer_licenses', array(&$this, "admin_settings_page_display"));

		$this->options_pages[] = add_submenu_page(null, $this->menu_title, 'General Settings', 'manage_options', 'primer_generals', array( $this, 'admin_settings_page_display' ));

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

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'primer_licenses' ) {
			$current_tab = 'licenses';
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
					if ( $tab_slug === 'primer_licenses' ) {
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

		$this->option_metabox[] = apply_filters( 'primer_generals_option_fields', array(
			'id'			=> $prefix . 'generals',
			'title'			=> __( 'General Settings', 'primer' ),
			'menu_title'	=> __( 'General Settings', 'primer' ),
			'desc'			=> '',
			'show_on'    	=> array( 'key' => 'options-page', 'value' => array( 'generals' ), ),
			'show_names' 	=> true,
			'fields'		=> array(
				array(
					'name'		=> __( 'General Settings', 'primer' ),
					'desc'		=> '',
					'default'	=> '',
					'id'		=> 'title_generals_settings',
					'type'		=> 'title',
				),
				array(
					'name'		=> __( 'Enable receipt/invoice option in checkout', 'primer' ),
					'desc'      => __( 'Enables the receipt/invoice option on checkout', 'primer' ),
					'id'		=> 'primer_enable_invoice_in_checkout',
					'type'		=> 'radio_inline',
					'show_option_none' => false,
					'options' 	=> array(
						'off'	=> __('Hide', 'primer'),
						'on'	=> __('Show', 'primer'),
					),
					'default' => 'off'
				),
				array(
					'name'		=> __( 'Create/update new user on checkout', 'primer' ),
					'desc'      => __( 'Enables the receipt/invoice option on checkout', 'primer' ),
					'id'		=> 'primer_create_user_on_checkout',
					'type'		=> 'radio_inline',
					'options' 	=> array(
						'off'	=> __('Off', 'primer'),
						'on'	=> __('On', 'primer'),
					),
					'default' 	=> 'off',
				),
			)
		) );

		$mydata_options = get_option('primer_mydata');
		$company_logo_id = '';
		$company_logo_src = '';
		$disable_upload = '';
		if (!empty($mydata_options)) {
			if (!empty($mydata_options['upload_img_id'])) {
				$company_logo_id = $mydata_options['upload_img_id'];
			}

			if (!empty($company_logo_id)) {
				$company_logo_src .= '<img src="'.wp_get_attachment_image_url($company_logo_id, array(350, 100)).'" alt="Company logo">';
			} else {
				$company_logo_src .= '';
			}
		}

		if (!empty($mydata_options['count_logo_change'])) {
			if ($mydata_options['count_logo_change'] >= 3) {
				$disable_upload = 'disabled hidden';
			} else {
				$disable_upload = '';
			}
		}

		$this->option_metabox[] = apply_filters( 'primer_mydata_option_fields', array(
			'id'			=> $prefix . 'mydata',
			'title'			=> __( 'MyData Settings', 'primer' ),
			'menu_title'	=> __( 'MyData Settings', 'primer' ),
			'desc'			=> __( '', 'primer' ),
			'show_on'    	=> array( 'key' => 'options-page', 'value' => array( 'mydata' ), ),
			'show_names' 	=> true,
			'fields'		=> array(

				array(
					'name'		=> __( 'MyData Invoices', 'primer' ),
					'desc'		=> '',
					'default'	=> '',
					'id'		=> 'title_invoice_settings',
					'type'		=> 'title',
				),

				array(
					'name'      => __( 'MyData Package:', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'default'   => '',
					'id'        => 'mydata_package',
					'type'      => 'text_small',
				),

				array(
					'name'      => __( 'Remaining MyData Invoices for this month:', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'default'   => '',
					'id'        => 'remaining_mydata_invoices',
					'type'      => 'text_small',
					'after'		=> '<a href="#" class="button">'.__('Get remaining', 'primer').'</a>'
				),

				array(
					'name'      => __( 'Contract ends on:', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'default'   => '',
					'id'        => 'mydata_contract_ends_on',
					'type'      => 'text_small',
				),

				array(
					'name' 		=> __( 'MyData API:', 'primer' ),
					'desc'		=> '',
					'id'		=> 'mydata_api',
					'type'		=> 'select',
					'options'	=> array(
						'test_api' => __( 'Test API', 'primer' ),
						'live_api' => __( 'Live API', 'primer' ),
					),
				),

				array(
					'name'      => __( 'Resend last HTML document', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'default'   => '',
					'id'        => 'resend_last_html_doc',
					'type'      => 'title',
					'after_field'		=> '<a href="#" class="button">'.__('Resend', 'primer').'</a>',
				),

				array(
					'name'		=> __('Add Company Logo', 'primer'),
					'desc'		=> __('Jpg,Png files only. File must be 256x256px and up to 75 kb. Up to 3 logo changes (after pressing save) are supported.', 'primer'),
					'id'		=> 'company_logo',
					'type'		=> 'title',
					'after_field' => '
						<div class="company_img">
							<div id="profile_photo">
								<div class="thumb">
									<div class="avatar-wrapper">'
										.$company_logo_src.
									'</div>
								</div>
							</div>
							<div id="upload_errors"></div>
							<div class="profile-img-controls">
								<div id="plupload-container"></div>
							</div>
							<div id="profile_upload_container" class="get__photo">
								<a id="select_user_profile_photo" class="upload-button button '.$disable_upload.'" href="javascript:;">'.__('Upload File', 'primer').'</a>
							</div>
						</div>
					',
				),

				array(
					'name'		=> __('Count of upload logo', 'primer'),
					'desc'		=> '',
					'id'		=> 'count_logo_change',
					'type'		=> 'hidden',
					'default'	=> '0',
				),

				array(
					'name'		=> __('Image API ID', 'primer'),
					'desc'		=> '',
					'id'		=> 'image_api_id',
					'type'		=> 'hidden',
					'default'	=> '',
				),

				array(
					'name' 		=> __( 'Select Greek invoice template', 'primer' ),
					'desc'		=> '',
					'id'		=> 'greek_template',
					'type'		=> 'select',
					'options'	=> array('greek_template1' => __( 'Greek invoice template', 'primer' )),
					'after_field' => '
						<a href="'.plugins_url('/primer/public/partials/gr_invoicetemplate_defaultA4.php').'" target="_blank" class="button preview">'.__('Preview template', 'primer').'</a>
					',
				),

				array(
					'name' 		=> __( 'Select English invoice template', 'primer' ),
					'desc'		=> '',
					'id'		=> 'english_template',
					'type'		=> 'select',
					'options'	=> array('english_template1' => __( 'English invoice template', 'primer' )),
					'after_field' => '
						<a href="'.plugins_url('/primer/public/partials/invoicetemplate_defaultA4.php').'" target="_blank" class="button preview">'.__('Preview template', 'primer').'</a>
					',
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
					'desc'		=> '',
					'default'	=> '',
					'id'		=> 'title_email_smtp_settings',
					'type'		=> 'title',
				),
				array(
					'name'		=> __( 'Send email from account', 'primer' ),
					'desc'		=> __( '', 'primer' ),
					'default'	=> __( '', 'primer' ),
					'type'	=> 'text_email',
					'id'	=> 'primer_from_email',
				),

				array(
					'name'      => __( 'Email username', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'default'   => '',
					'type'      => 'text',
					'id'        => 'primer_smtp_username',
				),
				array(
					'name'      => __( 'Email password', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'default'   => '',
					'type'      => 'text',
					'id'        => 'primer_smtp_password',
					'attributes' => array(
						'type' => 'password',
					),
				),

				array(
					'name'		=> __( 'Encrypt Password', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'id'		=> 'primer_encrypt_pass',
					'type'		=> 'checkbox'
				),

				array(
					'name'		=> __( 'Type of Encryption', 'primer' ),
					'desc'      => __( 'For most servers SSL/TLS is the recommended option', 'primer' ),
					'id'		=> 'primer_smtp_type_encryption',
					'type'    	=> 'radio_inline',
					'options'	=> array(
						'none' => __( 'None', 'primer' ),
						'ssl' => __( ' SSL/TLS', 'primer' ),
						'tls' => __( ' STARTTLS', 'primer' ),
					),
					'default'	=> 'none'
				),

				array(
					'name'		=> __( 'SMTP Authentication', 'primer' ),
					'desc'		=> __("This options should always be checked 'Yes'", 'primer'),
					'id'		=> 'primer_smtp_authentication',
					'type'		=> 'radio_inline',
					'options'	=> array(
						'yes'	=> __('Yes', 'primer'),
						'no'	=> __('No', 'primer'),
					),
					'default'	=> 'no'
				),

				array(
					'name'      => __( 'SMTP server', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'default'   => 'smtp.example.com',
					'type'      => 'text',
					'id'        => 'primer_smtp_host',
				),
				array(
					'name'      => __( 'Port', 'primer' ),
					'desc'      => __( '', 'primer' ),
					'default'   => '25',
					'type'      => 'text',
					'id'        => 'primer_smtp_port',
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

				array(
					'name'		=> __('Send email automatically on order conversion', 'primer'),
					'desc'		=> '',
					'default'	=> 'yes',
					'id'		=> 'automatically_send_on_conversation',
					'type'		=> 'radio_inline',
					'options'	=> array(
						'yes' => __('Yes', 'primer'),
						'no' => __('No', 'primer')
					)
				)
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
				$checkbox,
				array(
					'id'          => $prefix . 'conditions',
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
							'name'       => __( 'Issue receipt if order state is: ', 'primer' ),
							'id'         => 'receipt_order_states',
							'type'       => 'select',
							'options'	=> $this->get_status_of_orders(),
						),
						/*array(
							'name'       => __( 'And payment state is ', 'primer' ),
							'id'         => 'receipt_payment_states',
							'type'       => 'select',
							'options'	=> array(
								'' => __('Get payment states from', 'primer')
							)
						),*/
						array(
							'name' 	=> __('Send email to client', 'primer'),
							'id'	=> 'client_email_send',
							'type'	=> 'checkbox',
						)
					)
				),

				array(
					'name' => __('Run Automation every ', 'primer'),
					'id'	=> 'automation_duration',
					'type' => 'select',
					'options' => array(
							'fiveminutes' => __('5 minutes', 'primer'),
							'tenminutes' => __('10 minutes', 'primer'),
							'thirtyminutes' => __('30 minutes', 'primer'),
							'hourly' => __('60 minutes', 'primer'),
							'daily' => __('Once per day', 'primer'),
					),
					'attributes' => array(
						'data-conditional-id' => 'activation_automation',
						'data-conditional-value' => 'on',
					),
					'after_field' => __('In order for automation to work, your server needs to support cron. ', 'primer') . $this->check_wp_cron_enabled(),
				),

				array(
					'name' => __('Run Automation on orders issued after: ', 'primer'),
					'id' => 'calendar_date_timestamp',
					'type' => 'text_date',
					'date_format' => 'Y-m-d',
					'after_field' => __('Warning, the older the date, the heavier is the load for your server. If you get frequent timeouts, try using a more recent date.', 'primer'),
					'attributes' => array(
						'data-conditional-id' => 'activation_automation',
						'data-conditional-value' => 'on',
					),
				),

				array(
					'name'	=> __( 'Send email to admin', 'primer' ),
					'desc' => '',
					'type'	=> 'checkbox',
					'id'	=> 'send_email_to_admin',
					'attributes' => array(
						'data-conditional-id' => 'activation_automation',
						'data-conditional-value' => 'on',
					),
				),

				array(
					'name'	=> __( 'Admin email', 'primer' ),
					'desc' => __('(use comma (,) without spaces for multiple emails)', 'primer'),
					'type'	=> 'text',
					'id'	=> 'admin_email',
					'attributes' => array(
						'data-conditional-id' => 'activation_automation',
						'data-conditional-value' => 'on',
					),
				),

				array(
					'name'	=> __( 'Send successful receipts log', 'primer' ),
					'desc' => '',
					'type'	=> 'checkbox',
					'id'	=> 'send_successful_log',
					'attributes' => array(
						'data-conditional-id' => 'activation_automation',
						'data-conditional-value' => 'on',
					),
				),

				array(
					'name'	=> __( 'Send failed receipts log', 'primer' ),
					'desc' => '',
					'type'	=> 'checkbox',
					'id'	=> 'send_failed_log',
					'attributes' => array(
						'data-conditional-id' => 'activation_automation',
						'data-conditional-value' => 'on',
					),
				),

				array(
					'name'	=> __( 'Email Subject: ', 'primer' ),
					'desc' => '',
					'type'	=> 'text',
					'id'	=> 'email_subject',
					'attributes' => array(
						'data-conditional-id' => 'activation_automation',
						'data-conditional-value' => 'on',
					),
				),

				array(
					'name'	=> '',
					'desc' => '',
					'type'	=> 'button',
					'id'	=> 'log_button',
					'after' => '<a href="' . admin_url('admin.php?page=primer_receipts_logs_automation') . '" target="_blank" class="button order-view">'.__('Log', 'primer').'</a>'
				),

				array(
					'name'	=> '',
					'desc' => '',
					'type'	=> 'button',
					'id'	=> 'run_now_button',
					'after' => '<a href="#" class="button" id="cron-execute-cron-task-now">'.__('Run Now', 'primer').'</a>'
				),


			)
		) );

		$this->option_metabox[] = apply_filters( 'primer_licenses_option_fields', array(
			'id'		 => $prefix . 'licenses',
			'title'		 => __( 'Licence and General Settings', 'primer' ),
			'menu_title' => __( 'Licence credentials', 'primer' ),
			'desc'			=> __( '', 'primer' ),
			'show_on'    => array( 'key' => 'options-page', 'value' => array( 'licenses' ), ),
			'show_names' => true,
			'fields' => array(
				array(
					'name' => __('Licence credentials', 'primer'),
					'id' => 'licence_credentials',
					'type' => 'title',
					'desc' => __( '', 'primer' ),
				)
			)
		) );

		return $this->option_metabox;

	}

	public function check_wp_cron_enabled() {
		$cron_status = '';
		if (defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON === true ) {
			$cron_status = __('DISABLED', 'primer');
		} else {
			$cron_status = __( 'ENABLED - OK', 'primer' );
		}
		return $cron_status;
	}

	/**
	 * Get the list of Woocommerce Order Statuses
	 *
	 * @since   1.0.0
	 */
	public function get_status_of_orders() {
		$order_all_statuses = array( '' => __('Select order status', 'primer') );
		$status_of_orders = wc_get_order_statuses();
		foreach ( $status_of_orders as $status_k => $status_value ) {
			$order_all_statuses[$status_k] = $status_value;
		}
		return $order_all_statuses;
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

	public function admin_page_receipt_log_display() {
		include_once(PRIMER_PATH . 'admin/includes/primer-admin-receipt-log-table.php');
		$primer_receipt = new PrimerReceiptLog();
		$primer_receipt->handle_main_primer_receipt_admin_menu();
	}

	public function admin_page_receipt_log_automation_display() {
		include_once(PRIMER_PATH . 'admin/includes/primer-admin-receipt-log-automation-table.php');
		$primer_receipt = new PrimerReceiptLogAutomation();
		$primer_receipt->handle_main_primer_receipt_admin_menu();
	}

	public function primer_resend_receipt_to_customer() {
		$receipt_ids = isset($_POST["receipts"]) ? $_POST["receipts"] : "";

		$response = '';

		if (!empty($receipt_ids) && is_array($receipt_ids)) {
			foreach ( $receipt_ids as $receipt_id ) {
                $receipt_id = (int)$receipt_id;

                $order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
                $user_id = get_post_meta($receipt_id, 'receipt_client_id', true);

                $order = wc_get_order($order_id);

                $order_email = $order->get_billing_email();

                $user_data = get_user_by('ID', $user_id);

                if (empty($order_email)) {
	                $user_email = $user_data->user_email;
                } else {
	                $user_email = $order_email;
                }

                $user_email = sanitize_email($user_email);

				$upload_dir = wp_upload_dir()['basedir'];

				if (!file_exists($upload_dir . '/email-invoices')) {
					mkdir($upload_dir . '/email-invoices');
				}

				$post_name = get_the_title($receipt_id);
				$post_name = str_replace(' ', '_', $post_name);
				$post_name = str_replace('#', '', $post_name);
				$post_name = strtolower($post_name);

				$attachments = $upload_dir . '/email-invoices/'.$post_name.'.pdf';

				if (!file_exists($attachments)) {
					$post_url = get_the_permalink($receipt_id);

					$homepage = file_get_contents($post_url);

					$dompdf = new Dompdf();
					$options= $dompdf->getOptions();
					$options->setIsHtml5ParserEnabled(true);
					$dompdf->setOptions($options);

					$dompdf->loadHtml($homepage);

					$dompdf->render();

					$output = $dompdf->output();
					file_put_contents($upload_dir . '/email-invoices/'.$post_name.'.pdf', $output);

					$attachments = $upload_dir . '/email-invoices/'.$post_name.'.pdf';
				}

				$primer_smtp_options = get_option('primer_emails');

				$headers = 'From: ' . $primer_smtp_options['from_email_field'] ? $primer_smtp_options['from_email_field'] : 'Primer '. get_bloginfo('admin_email');
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

				$mailResult = false;
				$primer_smtp = PrimerSMTP::get_instance();

				$mailResult = wp_mail( $user_email, $primer_smtp_subject, $primer_smtp_message, $headers, $attachments );

				if (!$mailResult) {
//					$response =  '<div class="notice notice-error"><p>'.__('Email settings are not correct.', 'primer').'</p></div>';
					$response = false;
					$response_wrap = '<div class="primer_popup popup_error"><h3>'.__('Message not sent!', 'primer').'</h3></div>';

				} else {
					$response = 'success';
					$response_wrap = '<div class="primer_popup popup_success"><h3>'.__('Message sent successfully!', 'primer').'</h3></div>';
				}
				echo json_encode(array('success' => 'true', 'status' => 'success', 'response' => $response, 'response_wrap' => $response_wrap));

			}
		}

		wp_die();
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
		$post_ids = '';

		$receipt_id_arr = explode(', ', $receipt_id);

		global $wpdb;

		$post_name = get_the_title($receipt_id);
		$post_name = str_replace(' ', '_', $post_name);
		$post_name = str_replace('#', '', $post_name);
		$post_name = strtolower($post_name);

		$upload_dir = wp_upload_dir()['basedir'];

		$upload_url = wp_upload_dir()['baseurl'] . '/exported_html_files';

		$all_files = $upload_dir . '/exported_html_files/tmp_files';
		$files = $this->get_all_files_as_array($all_files);

		$zip_file_name = $upload_dir . '/exported_html_files/'.$post_name.'-html.zip';

		ob_start();
		echo $this->create_zip($files, $zip_file_name, $all_files . '/');
		$create_zip = ob_get_clean();

		if ($create_zip == 'created') {
			$this->rmdir_recursive($upload_dir . '/exported_html_files/tmp_files');
		}

		$response = ($create_zip == 'created') ? $upload_url . '/'.$post_name.'-html.zip' : false;


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

			if( isset( $_POST['primer_from_email'] ) ) {
				if ( is_email($_POST['primer_from_email'] ) ) {
					$primer_smtp_options['from_email_field'] = sanitize_email( $_POST['primer_from_email'] );
				} else {
					$error .= ' ' . __( "Please enter a valid email address in the 'Send email from account' field.", 'primer' );
				}
			}

			$primer_smtp_options['reply_to_email'] = sanitize_email( get_bloginfo('admin_email') );

			$primer_smtp_options['smtp_settings']['smtp_server']            = stripslashes( $_POST['primer_smtp_host'] );
			$primer_smtp_options['smtp_settings']['type_encryption'] = ( isset( $_POST['primer_smtp_type_encryption'] ) ) ? sanitize_text_field( $_POST['primer_smtp_type_encryption'] ) : 'none';
			$primer_smtp_options['smtp_settings']['authentication'] = ( isset( $_POST['primer_smtp_authentication'] ) ) ? sanitize_text_field( $_POST['primer_smtp_authentication'] ) : 'yes';
			$primer_smtp_options['smtp_settings']['username'] = stripslashes( $_POST['primer_smtp_username'] );

			$primer_smtp_options['smtp_settings']['encrypt_pass'] = isset( $_POST['primer_encrypt_pass'] ) ? 1 : false;

			$primer_smtp_password = $_POST['primer_smtp_password'];
			if ($primer_smtp_password !== $gag_password) {
				$primer_smtp_options['smtp_settings']['password'] = $primer_smtp->encrypt_password( $primer_smtp_password );
			}

			if ( $primer_smtp_options['smtp_settings']['encrypt_pass'] && ! get_option( 'primer_pass_encrypted', false ) ) {
				update_option( 'primer_emails', $primer_smtp_options );
				$pass = $primer_smtp->get_password();
				$primer_smtp_options['smtp_settings']['password'] = $primer_smtp->encrypt_password( $pass );
				update_option('primer_emails', $primer_smtp_options);
			}


			/* Check value from "SMTP port" option */
			if ( isset( $_POST['primer_smtp_port'] ) ) {
				if ( empty( $_POST['primer_smtp_port'] ) || 1 > intval( $_POST['primer_smtp_port'] ) || ( ! preg_match( '/^\d+$/', $_POST['primer_smtp_port'] ) ) ) {
					$primer_smtp_options['smtp_settings']['port'] = '25';
					$error .= ' ' . __( "Please enter a valid port in the 'SMTP Port' field.", 'primer' );
				} else {
					$primer_smtp_options['smtp_settings']['port'] = sanitize_text_field( $_POST['primer_smtp_port'] );
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
				if ( isset($_POST['primer_from_email']) ) {
					$to_email = sanitize_text_field( $_POST['primer_from_email'] );
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


				if(!empty($error)) {
					$error_arr = explode('.', $error);
					foreach ($error_arr as $e) {
						if ($e) {
							$response_wrap = '<div class="primer_popup popup_error"><h3>'.$e.'</h3></div>';
							echo $response_wrap;
						}
					}
				}

				if ( !empty( $primer_smtp_to ) ) {
					$test_res = $primer_smtp->test_mail($primer_smtp_to, $primer_smtp_subject, $primer_smtp_message);
				}
//			}

//		}
		wp_die();
	}

	public function primer_user_picture_upload() {

		$mydata_options = get_option('primer_mydata');

		if (!empty($mydata_options['count_logo_change'])) {
			$count_logo_change = $mydata_options['count_logo_change'];
		} else {
			$count_logo_change = '0';
		}

		$store_url = 'https://wp-mydataapi.ddns.net/v2/invoice/photo';
		$auth = base64_encode( 'user' . ':' . 'qwerty' );

		$curl_args = array(
			CURLOPT_URL => $store_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_HTTPHEADER => array(
				"Authorization: Basic $auth",
				'Content-Type: application/json'
			)
		);

		$curl = curl_init();


		// Verify if Nonce is valid
		$verify_nonce = $_REQUEST['verify_nonce'];
		if ( !wp_verify_nonce($verify_nonce, 'upload_nonce') ) {
			echo json_encode(array('success' => false, 'reason' => 'Invalid request'));
			die();
		}

		$user_image = $_FILES['file_data_name'];
		$wp_handle_upload = wp_handle_upload($user_image, array('test_form' => false));

		if (isset($wp_handle_upload['file'])) {
			$file_name = basename($user_image['name']);
			$file_type = wp_check_filetype($wp_handle_upload['file']);

			$uploaded_image_details = array(
				'guid'           => $wp_handle_upload['url'],
				'post_mime_type' => $file_type['type'],
				'post_title'     => preg_replace('/\.[^.]+$/', '', basename($file_name)),
				'post_content'   => '',
				'post_status'    => 'inherit'
			);

			$attach_url = $wp_handle_upload['url'];

			$type = pathinfo($attach_url, PATHINFO_EXTENSION);
			$data = file_get_contents($attach_url);
			$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

			$curl_args['CURLOPT_POSTFIELDS'] = $base64;

			curl_setopt_array($curl, $curl_args);

			$response = curl_exec($curl);
			curl_close($curl);

			$response_image = array();

			if (!empty($response)) {
				$mydata_options['image_api_id'] = $response;
				$response_args = explode(':', $response);
				if (count($response_args) > 1) {
					$response_key = $response_args[0];
					$response_value = $response_args[1];
					$response_key = str_replace('"', '', $response_key);
					$response_value = str_replace('"', '', $response_value);
					$response_image[$response_key] = $response_value;
				}
			}

			if (array_key_exists('photoId', $response_image)) {
				if (!empty($response_image['photoId'])) {
					$mydata_options['count_logo_change'] = $count_logo_change + 1;
					$profile_attach_id = wp_insert_attachment($uploaded_image_details, $wp_handle_upload['file']);

					$profile_attach_data = wp_generate_attachment_metadata($profile_attach_id, $wp_handle_upload['file']);
					wp_update_attachment_metadata($profile_attach_id, $profile_attach_data);

					$mydata_options['upload_img_id'] = $profile_attach_id;

					// Get uploaded image url
//			$photo_url = ogedge_uploaded_image_url( $profile_attach_data );
					$thumbnail_url = wp_get_attachment_image_src($profile_attach_id, array(256,256));

					update_option('primer_mydata', $mydata_options);

					echo json_encode(array(
						'success' => true,
						'url' => $thumbnail_url[0],
						'attachment_id' => $profile_attach_id
					));
					die;
				}
			}

		} else {
			echo json_encode(array('success' => false, 'reason' => 'Profile Avatar upload failed'));
			die;
		}
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
