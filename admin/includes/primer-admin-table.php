<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

require_once PRIMER_PATH . 'views/get_order_list.php';

// reference the Dompdf namespace
use Dompdf\Dompdf;


class PrimerReceipts extends WP_List_Table {

	function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Order', 'primer' ),
				'plural' => __( 'Orders', 'primer' ),
				'ajax' => true,
			)
		);

		$this->prepare_items();

		add_action( 'wp_print_scripts', [ __CLASS__, '_list_table_css' ] );

	}

	function get_columns() {
		return array(
			'cb'		 	=> '<input type="checkbox" />',
			'order_id'		=> __( 'No', 'primer' ),
			'order_date' 	=> __( 'Order Date', 'primer' ),
			'order_hour'	=> __( 'Hour', 'primer' ),
			'order_client'	=> __( 'Client', 'primer' ),
			'order_product' => __( 'Products', 'primer' ),
			'order_price'	=> __( 'Total Price', 'primer' ),
			'order_status'	=> __( 'Order Status', 'primer' ),
			'payment_status' => __( 'Payment Status', 'primer' ),
			'receipt_date'	=> __( 'Receipt date', 'primer' ),
			'receipt_status'	=> __( 'Receipt status', 'primer' ),
			'receipt_id'	=> __( 'Receipt ID', 'primer' ),
		);
	}

	function get_sortable_columns() {
		return array();
	}

	function column_default( $item, $column_name ) {

		if ($column_name !== 'receipt_date' && $column_name !== 'receipt_status' && $column_name !== 'receipt_id') {
			echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $item['order_id'] ) ) . '&action=edit' ) . '" target="_blank" class="order-view"><strong>' . esc_attr( $item[ $column_name ] ) . '</strong></a>';
		} else {
			if ($column_name == 'receipt_date') {
				$receipt_id = $item['receipt_id'];
				if (!empty($receipt_id)) {
					echo '<a href="' . esc_url( get_permalink($receipt_id) ) . '" target="_blank" class="order-view"><strong>' . esc_attr( $item[ $column_name ] ) . '</strong></a>';
				} else {
					return $item[ $column_name ];
				}
			} else {
				return $item[ $column_name ];
			}
		}
	}

	/**
	 * @var array
	 *
	 * Array contains slug columns that you want hidden
	 *
	 */

	private $hidden_columns = array(
		'receipt_status', 'receipt_id', 'payment_status'
	);

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="orders[]" id="order_'.$item['order_id'].'" value="%s" />',
			$item['order_id']
		);
	}

	protected function get_bulk_actions() {
		return array();
	}

	function extra_tablenav( $which ){
		if ( $which !== 'bottom' ) {
		$primer_orders = new PrimerOrderList();
		$primer_orders_customers = $primer_orders->get_users_from_orders();
		$unique_customers = [];
			foreach ( $primer_orders_customers as $primer_orders_customer ) {
				$hash = $primer_orders_customer['order_client_id'];
				$unique_customers[$hash] = $primer_orders_customer;
			}
			$order_customers = array_values($unique_customers);
		?>
		<div class="alignleft actions">
			<h2><?php _e('Filters', 'primer'); ?></h2>
			<h3><?php _e('Date Range:', 'primer'); ?></h3>
			<div class="filter_blocks_wrapper">
				<div class="left_wrap">
					<div class="filter_block">
					<label for="primer_order_year" style="float: left;"><?php _e('Year: ', 'primer'); ?></label>
						<select name="primer_order_year" id="primer_order_year">
							<?php
							$current_year = date('Y');
							$year_to = 2035;
							$range_years = range($current_year, $year_to);
							foreach ( $range_years as $range_year ) { ?>
								<option value="<?php echo $range_year; ?>"><?php echo $range_year; ?></option>
							<?php }
							?>
						</select>
				</div>
					<div class="filter_block">
						<label for="order_date_from">
							<?php _e('From: ', 'primer'); ?></label>
							<input type="text" id="order_date_from" name="order_date_from" placeholder="Date From" value="" />
						<label for="order_date_to">
							<?php _e('To: ', 'primer'); ?></label>
							<input type="text" id="order_date_to" name="order_date_to" placeholder="Date To" value="" />
					</div>
					<div class="filter_block">
						<label for="primer_order_client" style="float: left;"><?php _e('Client: ', 'primer'); ?></label>
							<select name="primer_order_client" id="primer_order_client" data-placeholder="<?php _e('Select clients', 'primer'); ?>">
								<option value=""></option>
								<?php
								$get_customer = isset($_GET['primer_order_client']) ? $_GET['primer_order_client'] : '';
								foreach ( $order_customers as $primer_orders_customer => $order_customer ) {
									if ($order_customer['order_client_id']) { ?>
										<option value="<?php echo $order_customer['order_client_id']; ?>" <?php selected($get_customer, $order_customer['order_client_id']); ?>><?php echo $order_customer['order_client']; ?></option>
									<?php } else { ?>
										<option value="<?php echo $order_customer['order_client_id']; ?>" <?php selected($get_customer, $order_customer['order_client_id']); ?>><?php _e('Guest client', 'primer'); ?></option>
									<?php }
								} ?>
							</select>
					</div>
				</div>
				<div class="right_wrap">
					<div class="filter_block">
						<label for="primer_order_status" style="float: left;"><?php _e('Order Status: ', 'primer'); ?></label>
							<select class="selectpicker" multiple name="primer_order_status[]" title="<?php _e('Select order status', 'primer'); ?>" id="primer_order_status">
								<?php
								$status_of_orders = wc_get_order_statuses();

								$get_order_status = isset($_GET['primer_order_status']) ? $_GET['primer_order_status'] : '';

								foreach ( $status_of_orders as $status_k => $status_value ) { ?>
									<option value="<?php echo $status_k; ?>" <?php if (is_array($get_order_status)) {
										if (in_array($status_k, $get_order_status)) echo 'selected';
									}?>><?php echo $status_value; ?></option>
								<?php }
								?>
							</select>
					</div>

					<div class="filter_block">
						<label for="primer_receipt_status" style="float: left;"><?php _e('Receipt Status: ', 'primer'); ?></label>
							<select name="primer_receipt_status" id="primer_receipt_status">
							<?php
							$get_status = isset($_GET['primer_receipt_status']) ? $_GET['primer_receipt_status'] : '';
							$status_of_receipts = array(
									'' => 'All',
									'issued' => 'Issued',
									'not_issued' => 'Not Issued',
								);

							foreach ( $status_of_receipts as $status_k => $status_value ) { ?>
									<option value="<?php echo $status_k; ?>" <?php selected($status_k, $get_status); ?>><?php echo $status_value; ?></option>
								<?php }
							?>
							</select>
					</div>

					<div class="apply_btn"><input type="submit" class="button" name="filter_action" value="<?php _e('Apply filter', 'primer'); ?>" /></div>
				</div>
			</div>

		</div>

		<div class="loadingio-spinner-spinner-chyosfc7wi6"><div class="ldio-drsjmtezgls"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>
		<?php
		$orders_dates = $primer_orders->get_dates_from_orders();
		if (!empty($orders_dates) && is_array($orders_dates)) {
		    $min_order_date = min($orders_dates);
		    $max_order_date = max($orders_dates);
		} else {
		    $min_order_date = date('m/d/Y');
		    $max_order_date = date('m/d/Y');
		}

		 $formatted_min_order_date = date('m/d/Y', $min_order_date);
		 $formatted_max_order_date = date('m/d/Y', $max_order_date);
		 ?>
		<script>
			jQuery(document).ready(function ($) {

			    $.fn.selectpicker.Constructor.BootstrapVersion = '4';
			    $('.selectpicker').selectpicker();

			    $('#primer_order_client').selectWoo({
					allowClear:  true,
					placeholder: $( this ).data( 'placeholder' )
				});

			    var select_year = $('select[name="primer_order_year"]').val();

                var max_year = 2035;
                var diff_year = 0;
                var min_order_date = "<?php echo $formatted_min_order_date; ?>";
                var max_order_date = "<?php echo $formatted_max_order_date; ?>";


                var date_from = $('input[name="order_date_from"]'),
                    date_to = $('input[name="order_date_to"]');
                $('input[name="order_date_from"], input[name="order_date_to"]').datepicker({
                    changeMonth: true,
                    changeYear: true,
                    dateFormat: "yy-mm-dd",
                    yearRange: "2021:2035",
                });



                <?php if (isset($_GET['order_date_from'])) { ?>
                    $('input[name="order_date_from"]').datepicker("setDate", new Date("<?php echo $_GET['order_date_from']; ?>"));
					<?php } else { ?>
                	$('input[name="order_date_from"]').datepicker("option", "minDate", new Date(min_order_date));
					<?php } ?>

					<?php if (isset($_GET['order_date_to']) && !empty($_GET['order_date_to'])) { ?>
                    $('input[name="order_date_to"]').datepicker("setDate", new Date("<?php echo $_GET['order_date_to']; ?>"));
	                <?php } else { ?>
						$('input[name="order_date_to"]').datepicker("option", "minDate", new Date(max_order_date));
	                <?php } ?>


                $('select[name="primer_order_year"]').on('change', function () {
                    select_year = $(this).val();
                    $('input[name="order_date_from"], input[name="order_date_to"]').datepicker("destroy");
                    $('input[name="order_date_from"], input[name="order_date_to"]').datepicker({
                        changeMonth: true,
                        changeYear: true,
                        dateFormat: "yy-mm-dd",
                        yearRange: `${select_year}:${max_year}`,
                    });
                    var currentDate = new Date();
                    var currentDay = currentDate.getDate();
                    var currentMonth = currentDate.getMonth()+1;
                    var set_current_date = currentMonth + '/' + currentDay + '/' + select_year;
                    $('input[name="order_date_from"]').datepicker("setDate", new Date(set_current_date))
                });

                // the rest part of the script prevents from choosing incorrect date interval
                date_from.on( 'change', function() {
                    date_to.datepicker( 'option', 'minDate', date_from.val() );
                });

                date_to.on('change', function () {
                    date_to.datepicker('option', 'maxDate', date_to.val());
				});

                var atLeastOneIsChecked = $('input[name="orders[]"]:checked').length > 0;
                if (atLeastOneIsChecked) {
                    $('.convert_orders input[type="submit"]').removeAttr('disabled');
                }
                function checker() {
                    var length_inputs = $('input[name="orders[]"]').length;
                    var trues = new Array();
                    $('input[name="orders[]"]').each(function (i, el) {

                        if ($(el).prop('checked') == true || $(el).is(':checked') == true) {
                            $('.convert_orders input[type="submit"]').removeAttr('disabled');
                            trues.push($(el));
                        }
                    })
                    if (trues.length <= 0) {
                        $('.convert_orders input[type="submit"]').attr('disabled', true);
                    }
                }

                $('.wp-list-table #cb input:checkbox').on('click', function () {
                    checker();
                    if ($(this).is(':checked')) {
                        $('.convert_orders input[type="submit"]').removeAttr('disabled');
                    } else {
                        $('.convert_orders input[type="submit"]').attr('disabled', true);
                    }
                });
                $('.wp-list-table input[name="orders[]"]').on('click', function () {
                    checker();
                });

			});
		</script>
	<?php
		}
	}

	function prepare_items() {

		$per_page = 20;

		$get_total_orders = new PrimerOrderList();

		if ((isset($_GET['primer_order_status'])) || (isset($_GET['primer_order_client']) && !empty($_GET['primer_order_client'])) || (isset($_GET['order_date_from']) && !empty($_GET['order_date_from'])) || (isset($_GET['order_date_to']) && !empty($_GET['order_date_to'])) || (isset($_GET['primer_receipt_status']) && !empty($_GET['primer_receipt_status']) )) {
			$get_orders_list = $get_total_orders->get_with_params($_REQUEST['order_date_from'], $_REQUEST['order_date_to'], $_GET['primer_order_client'], $_REQUEST['primer_order_status'], $_GET['primer_receipt_status']);
		} else {
			$get_orders_list = $get_total_orders->get();
		}

		$columns  = $this->get_columns();
		$hidden   = $this->hidden_columns;
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );


		$this->items = $get_orders_list;

		$data = $this->items;

		/**
		 * Get current page calling get_pagenum method
		 */
		$current_page = $this->get_pagenum();
		$total_items = count($data);

		$data = array_slice($data,(($current_page-1)*$per_page),$per_page);

		$this->items = $data;


		/**
		 * Call to _set_pagination_args method for informations about
		 * total items, items for page, total pages and ordering
		 */
		$this->set_pagination_args(
			array(
				'total_items'	=> $total_items,
				'per_page'	    => $per_page,
				'total_pages'	=> ceil( $total_items / $per_page ),
			)
		);
	}

	/*function display() {
		wp_nonce_field( 'ajax-order-list-nonce', '_ajax_order_list_nonce' );

		parent::display();
	}*/

	function no_items() {
		_e( 'No orders found.', 'primer' );
	}

	function process_bulk_action() {
		//Detect when a bulk action is being triggered... then perform the action.

		$orders = isset( $_REQUEST['wp_ajax_list_order'] ) ? $_REQUEST['wp_ajax_list_order'] : array();
		$orders = array_map( 'sanitize_text_field', $orders );

		$current_action = $this->current_action();
		if ( ! empty( $current_action ) ) {
			//Bulk operation action. Lets make sure multiple records were selected before going ahead.
			if ( empty( $orders ) ) {
				echo '<div id="message" class="error"><p>Error! You need to select multiple records to perform a bulk action!</p></div>';
				return;
			}
		} else {
			// No bulk operation.
			return;
		}

	}

	function show_all_orders() {
		ob_start();
		$status = filter_input( INPUT_GET, 'status' );
		include_once PRIMER_PATH . 'views/admin_order_list.php';
		$output = ob_get_clean();
		return $output;
	}

	function show_all_receipts() {
		ob_start();
		$status = filter_input( INPUT_GET, 'status' );
//		include_once PRIMER_PATH . 'views/admin_order_list.php';
		$output = ob_get_clean();
		return $output;
	}

	function handle_main_primer_admin_menu() {
		do_action('primer_orders_menu_start');

		$action = filter_input(INPUT_GET, 'primer_action');
		$action = empty($action) ? filter_input(INPUT_POST, 'action') : $action;
		if (empty($action)) {
			$action = $_GET['page'];
		}
		$selected = $action;
		?>
		<div class="wrap primer-admin-menu-wrap">
		<?php
		 if ($_GET['page'] === 'wp_ajax_list_order') { ?>
		 	<h2><?php _e('Orders', 'primer'); ?>
			<?php //Trigger hooks that allows an extension to add extra nav tabs in the members menu.
			do_action( 'primer_menu_nav_tabs', $selected ); ?>
			</h2>
			<?php
			//Trigger hook so anyone listening for this particular action can handle the output.
			do_action( 'primer_menu_body_' . $action );

			//Allows an addon to completely override the body section of the members admin menu for a given action.
			$output = apply_filters( 'primer_menu_body_override', '', $action );
			if ( ! empty( $output ) ) {
				//An addon has overriden the body of this page for the given action. So no need to do anything in core.
				echo $output;
				echo '</div>'; //<!-- end of wrap -->
				return;
			} ?>
		 <?php } ?>

			<?php

			//Switch case for the various different actions handled by the core plugin.
			switch ( $action ) {
				case 'orders_list':
					// Show the orders listing
					echo $this->show_all_orders();
					break;
				case 'primer_receipts':
					echo $this->show_all_receipts();
					break;
				default:
					// Show the orders listing by default
					echo $this->show_all_orders();
					break;
			}

			echo '</div>';
	}
}


/**
 * Action wp_ajax for fetching ajax_response
 */
function _ajax_fetch_primer_order_callback() {
	$primer_order_list_table = new PrimerReceipts();
	$primer_order_list_table->ajax_response();
}
//add_action( 'wp_ajax__ajax_fetch_primer_order', '_ajax_fetch_primer_order_callback' );

/**
 * Action wp_ajax for fetching the first time table structure
 */

function ajax_primer_display_callback() {
	check_ajax_referer( 'ajax-order-list-nonce', '_ajax_order_list_nonce', true );

	$primer_order_list_table = new PrimerReceipts();
	$primer_order_list_table->prepare_items();

	ob_start();
	$primer_order_list_table->display();
	$display = ob_get_clean();


	die( wp_json_encode( array( "display" => $display )) );

}

//add_action('wp_ajax_ajax_primer_display', 'ajax_primer_display_callback');

add_action('wp_ajax_convert_select_orders', 'convert_select_orders');
function convert_select_orders() {

	$url = 'https://wp-mydataapi.ddns.net/v2/invoices/send';

	$auth = base64_encode( 'user' . ':' . 'qwerty' );

		$curl_args = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_HTTPHEADER => array(
				"Authorization: Basic $auth",
				'Content-Type: application/json'
			)
		);

		$curl = curl_init();

		$invoice_data = array(
			"invoice" => array(),
		);

	$invoiceType = '';

	$post_ids = array();
	$order_ids = array();

	$orders = isset($_GET['orders']) ? $_GET['orders'] : '';

	$response_data = '';

	$receipt_log_value = '';

	if (!empty($orders)) {
		foreach ( $orders as $order_id ) {
			$order = wc_get_order( $order_id );

			$id_of_order = $order->get_id();

			$order_country = $order->get_billing_country();
			$order_create_date = date( 'F j, Y', $order->get_date_created()->getOffsetTimestamp());
			$order_paid_date = null;
			$order_paid_hour = null;
			if (!empty($order->get_date_paid())) {
				$order_paid_date = date( 'F j, Y', $order->get_date_paid()->getTimestamp());
				$order_paid_hour = date( 'H:i:s', $order->get_date_paid()->getTimestamp());
			} else {
				$order_paid_date = date( 'F j, Y', $order->get_date_created()->getTimestamp());
				$order_paid_hour = date( 'H:i:s', $order->get_date_created()->getTimestamp());
			}

			$order_total_price = $order->get_total();
			$user_id   = $order->get_user_id();
			$user      = $order->get_user();

			$user_first_name = $order->get_billing_first_name();
			$user_last_name = $order->get_billing_last_name();

			$user_full_name = $user_first_name . ' ' . $user_last_name;

			$tax = $order->get_total_tax();


			$order_invoice_type = get_post_meta($id_of_order, '_billing_invoice_type', true);

			$vat_number = get_post_meta($id_of_order, '_billing_vat', true);

			$insert_taxonomy = 'receipt_status';
			$invoice_term = '';

			$invoice_data['invoice'][0]['issuer']['country'] = $order_country;

			if ($order_invoice_type == 'receipt' && $order_country == 'GR') {
				$invoice_term = 'greek_receipt';
				$invoiceType = '11.1';
			}
			if ($order_invoice_type == 'receipt' && $order_country !== 'GR') {
				$invoice_term = 'english_receipt';
				$invoiceType = '11.1';
			}
			if ($order_invoice_type == 'invoice' && $order_country == 'GR') {
				$invoice_term = 'greek_invoice';
				$invoiceType = '1.1';
			}
			if ($order_invoice_type == 'invoice' && $order_country !== 'GR') {
				$invoice_term = 'english_invoice';
				$invoiceType = '1.2';
			}

			$invoice_data['invoice'][0]['issuer']['vatNumber'] = "800434990";

			$user_data = $user_full_name ? $user_full_name : '';

			$user_order_email = $order->get_billing_email();

			$currency      = $order->get_currency();
			$currency_symbol = get_woocommerce_currency_symbol( $currency );
			$payment_method = $order->get_payment_method();
			$payment_title = $order->get_payment_method_title();
			$order_status = $order->get_status();

			$primer_smtp = PrimerSMTP::get_instance();

			if ($currency == 'EUR') {
				if ($tax != '0') {
					$post_id = wp_insert_post(array(
						'post_type' => 'primer_receipt',
						'post_title' => 'Receipt for order #' . $id_of_order,
						'comment_status' => 'closed',
						'ping_status' => 'closed',
						'post_status' => 'draft',
					));
					wp_set_object_terms($post_id, $invoice_term, $insert_taxonomy, false);

					if ($post_id) {

						$invoice_data['invoice'][0]['invoiceHeader']['series'] = 'A';
						$invoice_data['invoice'][0]['invoiceHeader']['aa'] = $post_id;
						$invoice_data['invoice'][0]['invoiceHeader']['invoiceType'] = $invoiceType;
						$invoice_data['invoice'][0]['invoiceHeader']['currency'] = $currency;
						$invoice_data['invoice'][0]['invoiceHeader']['issueDate'] = get_the_date('Y-m-d', $post_id);

					$sum = 0;
					$item_count = 0;

					$tax_classes   = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
					if ( ! in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
						array_unshift( $tax_classes, '' );
					}
					$inside_tax_rate = '';

					foreach ( $order->get_items() as $item_id => $item_data ) {
						$product_name = $item_data->get_name();
						add_post_meta($post_id, 'receipt_product', $product_name);
						$quantity = $item_data->get_quantity();
						$sum += $quantity;

						$product_id = $item_data->get_product_id();
						$product_instance = wc_get_product($product_id);

						$subtotal_order_payment = $item_data->get_subtotal();

						$product_tax_class = $product_instance->get_tax_class();

						$taxes = WC_Tax::get_rates_for_tax_class( $product_tax_class );

						$tax_arr = json_decode(json_encode($taxes), true);
						foreach ( $tax_arr as $tax ) {
							if ($product_tax_class == $tax['tax_rate_class']) {
								$inside_tax_rate = $tax['tax_rate'];
							}
						}
						$inside_tax_rate = round($inside_tax_rate);

						$vatCategory = '';

						switch ($inside_tax_rate) {
							case "24":
								$vatCategory = 1;
							break;
							case "17":
								$vatCategory = 4;
							break;
							case "13":
								$vatCategory = 2;
							break;
							case "9":
								$vatCategory = 5;
							break;
							case "6":
								$vatCategory = 3;
							break;
							case "4":
								$vatCategory = 6;
							break;
							case "0":
								$vatCategory = 7;
							break;
						}

						$subtotal_item_tax = $item_data->get_subtotal_tax();

						$invoice_data['invoice'][0]['invoiceDetails'][$item_count]['lineNumber'] = $item_count + 1;
						$invoice_data['invoice'][0]['invoiceDetails'][$item_count]['netValue'] = $subtotal_order_payment;
						$invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatCategory'] = (int)$vatCategory;
						$invoice_data['invoice'][0]['invoiceDetails'][$item_count]['vatAmount'] = "$subtotal_item_tax";
						$invoice_data['invoice'][0]['invoiceDetails'][$item_count]['quantity'] = $quantity;
						$invoice_data['invoice'][0]['invoiceDetails'][$item_count]['measurementUnit'] = 1;
						$item_count++;
					}

					$subtotal = $order->get_subtotal();
					$total_tax = $order->get_total_tax();
					$total_fees = $order->get_fees();
					$total = $order->get_total();

					$invoice_data['invoice'][0]['paymentMethods']['paymentMethodDetails'][0]['type'] = 3;
					$invoice_data['invoice'][0]['paymentMethods']['paymentMethodDetails'][0]['amount'] = 0;

					$invoice_data['invoice'][0]['invoiceSummary']['totalNetValue'] = "$subtotal";
					$invoice_data['invoice'][0]['invoiceSummary']['totalVatAmount'] = $total_tax;
					$invoice_data['invoice'][0]['invoiceSummary']['totalWithheldAmount'] = '0';
					$invoice_data['invoice'][0]['invoiceSummary']['totalFeesAmount'] = '0';
					$invoice_data['invoice'][0]['invoiceSummary']['totalStampDutyAmount'] = '0';
					$invoice_data['invoice'][0]['invoiceSummary']['totalDeductionsAmount'] = '0';
					$invoice_data['invoice'][0]['invoiceSummary']['totalOtherTaxesAmount'] = '0';
					$invoice_data['invoice'][0]['invoiceSummary']['totalGrossValue'] = $total;

					$post_ids[] = $post_id;
				}

				} else {
					$response_data = '<div class="notice notice-error"><p>'.__('VAT% is required.', 'primer').'</p></div>';
				}
				} else {
					$response_data = '<div class="notice notice-error"><p>'.__('Only euro is accepted.', 'primer').'</p></div>';
				}
		}

		$curl_args[CURLOPT_POSTFIELDS] = json_encode($invoice_data);

		curl_setopt_array($curl, $curl_args);

		$response = curl_exec($curl);
		curl_close($curl);

		$response_to_array = json_decode($response);
		$response_from_array = $response_to_array->response;
		if (!empty($response_to_array)) {
			if (!empty($response_from_array)) {
				if ($response_from_array[0]->statusCode == 'Success') {
					if (!empty($post_ids)) {
						foreach ($post_ids as $post_id) {
						    $update_post_data = array(
								'ID' => $post_id,
								'post_status' => 'publish',
							);
							wp_update_post( wp_slash($update_post_data) );

							$invoice_uid = $response_from_array[0]->invoiceUid;

							if (!empty($invoice_uid)) {
								update_post_meta($post_id, 'response_invoice_uid', $invoice_uid);
							}

							$invoice_mark = $response_from_array[0]->invoiceMark;
							if (!empty($invoice_mark)) {
								update_post_meta($post_id, 'response_invoice_mark', $invoice_mark);
							}

							$invoice_authcode = $response_from_array[0]->authenticationCode;
							if (!empty($invoice_authcode)) {
								update_post_meta($post_id, 'response_invoice_authcode', $invoice_authcode);
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

							foreach ( $orders as $order_id ) {
								$order = wc_get_order( $order_id );
								$id_of_order = $order->get_id();
								$user_id   = $order->get_user_id();
								$user      = $order->get_user();

								$order_country = $order->get_billing_country();
								$order_create_date = date( 'F j, Y', $order->get_date_created()->getOffsetTimestamp());
								$order_paid_date = null;
								$order_paid_hour = null;
								if (!empty($order->get_date_paid())) {
									$order_paid_date = date( 'F j, Y', $order->get_date_paid()->getTimestamp());
									$order_paid_hour = date( 'H:i:s', $order->get_date_paid()->getTimestamp());
								} else {
									$order_paid_date = date( 'F j, Y', $order->get_date_created()->getTimestamp());
									$order_paid_hour = date( 'H:i:s', $order->get_date_created()->getTimestamp());
								}

								$currency      = $order->get_currency();
								$currency_symbol = get_woocommerce_currency_symbol( $currency );

								$order_total_price = $order->get_total();

								$user_first_name = $order->get_billing_first_name();
								$user_last_name = $order->get_billing_last_name();

								$user_full_name = $user_first_name . ' ' . $user_last_name;
								$user_data = $user_full_name ? $user_full_name : '';

								$user_order_email = $order->get_billing_email();

								$user_email = $user ? $user_order_email : $user->user_email;

								$post_issued = 'issued';
								if (empty($user_data)) {
									$post_issued = 'not_issued';
									$receipt_log_value .= __('Order Client name is required!', 'primer');
								}

								update_post_meta($post_id, 'receipt_status', $post_issued);
								update_post_meta($post_id, 'order_id_to_receipt', $id_of_order);
								update_post_meta($id_of_order, 'receipt_status', $post_issued);
								add_post_meta($post_id, 'receipt_client', $user_data);
								add_post_meta($post_id, 'receipt_client_id', $user_id);
								add_post_meta($post_id, 'receipt_price', $order_total_price . ' ' .$currency_symbol);

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

								$primer_automatically_send_file = $primer_smtp_options['automatically_send_on_conversation'];

								if (empty($primer_automatically_send_file)) {
									$primer_automatically_send_file = 'yes';
								}

								$receipt_log_id = wp_insert_post(array(
									'post_type' => 'primer_receipt_log',
									'post_title' => 'Receipt report for #' . $id_of_order,
									'comment_status' => 'closed',
									'ping_status' => 'closed',
									'post_status' => 'publish',
								));
								if ($receipt_log_id) {

									$invoice_date = get_the_date('F j, Y', $post_id);


									update_post_meta($receipt_log_id, 'receipt_log_order_id', $id_of_order);
									update_post_meta($receipt_log_id, 'receipt_log_order_date', $order_paid_date);
									update_post_meta($receipt_log_id, 'receipt_log_invoice_id', $post_id);
									update_post_meta($receipt_log_id, 'receipt_log_invoice_date', $invoice_date);
									update_post_meta($receipt_log_id, 'receipt_log_client', $user_data);
									$get_issue_status = get_post_meta($post_id, 'receipt_status', true);
									if(empty($get_issue_status)) {
										$get_issue_status = 'issued';
									}

									update_post_meta($receipt_log_id, 'receipt_log_status', $get_issue_status);
									update_post_meta($receipt_log_id, 'receipt_log_error', $receipt_log_value);
								}

								$email_logs = '';

								if (!empty($primer_automatically_send_file) && $primer_automatically_send_file === 'yes') {

									$mailResult = false;
									$primer_smtp = PrimerSMTP::get_instance();

									$mailResultSMTP = $primer_smtp->primer_mail_sender($user_order_email, $primer_smtp_subject, $primer_smtp_message, $attachments);

									if (! $primer_smtp->credentials_configured()) {
										$email_logs .= __('Configure your SMTP credentials', 'primer') ."\n";
									}

									if (!empty($mailResultSMTP['error']) && ! $primer_smtp->credentials_configured()) {
										$response_data = '<div class="notice notice-error"><p>'.$GLOBALS['phpmailer']->ErrorInfo.'</p></div>';
										update_post_meta($receipt_log_id, 'receipt_log_email', 'not_sent');
										$email_logs .= $GLOBALS['phpmailer']->ErrorInfo ."\n";
										update_post_meta($receipt_log_id, 'receipt_log_email_error', $email_logs);
										update_post_meta($receipt_log_id, 'receipt_log_total_status', 'only_errors');
									} else {
										update_post_meta($receipt_log_id, 'receipt_log_email', 'sent');
										update_post_meta($receipt_log_id, 'receipt_log_total_status', 'only_issued');
									}

									update_post_meta($post_id, 'exist_error_log', 'exist_log');
								} else {
									if (! $primer_smtp->credentials_configured()) {
										$email_logs .= __('Configure your SMTP credentials', 'primer') ."\n";
									}
										$email_logs .= __('Send email automatically on order conversion disabled', 'primer') ."\n";
										update_post_meta($receipt_log_id, 'receipt_log_email', 'not_sent');
										update_post_meta($receipt_log_id, 'receipt_log_email_error', $email_logs);
										update_post_meta($receipt_log_id, 'receipt_log_total_status', 'only_issued');
									}
							}

						}
					}

					$response_data = '<div class="notice notice-success"><p>Orders converted</p></div>';

				}
			}
		}

	}

	echo $response_data;


	wp_die();
}

/**
 * fetch_primer_script function
 */
function fetch_primer_script() {
	$screen = get_current_screen();

	?>

	<?php
	 if ( $screen->id != "toplevel_page_wp_ajax_list_order" ) {
		return;
	}
	 ?>

	<script>
        (function ($) {
            function check_exist_receipts(orders) {
                var order_arr = new Array();
                $(orders).each(function (i, el) {
                    var tr_parent = $(el).parents('tr');
                    var sibling_td = tr_parent.find('td.receipt_status');
                    if (sibling_td) {
                        var td_status = sibling_td.text();
                    }
                    if (td_status == 'Issued') {
                        $(el).prop('checked', false);
                    }
                    var order_id = $(el).val();
                    if (order_id) {
                        order_arr.push(order_id);
                    }
                })
            }

            $('.submit_convert_orders').on('click', function (e) {
                $('.submit_convert_orders').attr('disabled', true);
                e.preventDefault();

                check_exist_receipts($('input[name="orders[]"]:checked'));
                var count_orders = $('input[name="orders[]"]:checked').length;
                var receipt_word = count_orders == 1 ? 'receipt' : 'receipts';
                var confirmation = confirm('You are about to issue '+count_orders + ' ' + receipt_word+ '. Are you sure?');

                if (confirmation == true && count_orders > 0) {
                    var data = $('#tables-filter').serialize();

                $.ajax({
                	url: ajaxurl,
                	data: data,
                	beforeSend: function(){
                                $('table.table-view-list.orders').css({'opacity': '0.5'});
                                $('.loadingio-spinner-spinner-chyosfc7wi6').show();
                            },
                	success: function (data) {
                	    if (data) {
                	        $('#wpbody-content').prepend(data);
                	        setTimeout(function () {
                                        $('.loadingio-spinner-spinner-chyosfc7wi6').hide();
                                        $('table.table-view-list.orders').css({'opacity': '1'});
									}, 1500);
                	        setTimeout(function (){
                	            // document.location.reload();
                	        }, 2000);
                	    }
                	}
                })
                } else {
                    return false;
                }
            })

		})(jQuery)
	</script>
<?php }

add_action('admin_footer', 'fetch_primer_script');
