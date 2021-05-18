<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

require_once PRIMER_PATH . 'views/get_order_list.php';


class PrimerReceipts extends WP_List_Table {

	function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Order', 'primer' ),
				'plural' => __( 'Orders', 'primer' ),
				'ajax' => true,
			)
		);


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
		);
	}

	function get_sortable_columns() {
		return array();
	}

	function column_default( $item, $column_name ) {

		if ($column_name !== 'receipt_date') {
			echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $item['order_id'] ) ) . '&action=edit' ) . '" target="_blank" class="order-view"><strong>' . esc_attr( $item[ $column_name ] ) . '</strong></a>';
		} else {
			return $item[ $column_name ];
		}
	}

	/**
	 * @var array
	 *
	 * Array contains slug columns that you want hidden
	 *
	 */

	private $hidden_columns = array(
		'receipt_status'
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
		$primer_orders = new PrimerOrderList();
		$primer_orders_customers = $primer_orders->get_users_from_orders();
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
							<select name="primer_order_client" id="primer_order_client">
								<option value=""><?php _e('Select clients', 'primer'); ?></option>
								<?php
								foreach ( $primer_orders_customers as $primer_orders_customer => $order_customer ) {
									if ($order_customer['order_client_id']) { ?>
										<option value="<?php echo $order_customer['order_client_id']; ?>"><?php echo $order_customer['order_client']; ?></option>
									<?php } else { ?>
										<option value="<?php echo $order_customer['order_client_id']; ?>"><?php _e('Guest client', 'primer'); ?></option>
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

								foreach ( $status_of_orders as $status_k => $status_value ) { ?>
									<option value="<?php echo $status_k; ?>"><?php echo $status_value; ?></option>
								<?php }
								?>
							</select>
					</div>

					<div class="filter_block">
						<label for="primer_receipt_status" style="float: left;"><?php _e('Receipt Status: ', 'primer'); ?></label>
							<select name="primer_receipt_status" id="primer_receipt_status">
								<option value=""><?php _e('All', 'primer'); ?></option>
								<option value="issued"><?php _e('Issued', 'primer'); ?></option>
								<option value="not_issued"><?php _e('Not Issued', 'primer'); ?></option>
							</select>
					</div>
				</div>
			</div>

		</div>
		<?php
		 $orders_dates = $primer_orders->get_dates_from_orders();
		 $min_order_date = min($orders_dates);
		 $max_order_date = max($orders_dates);
		 $formatted_min_order_date = date('m/d/Y', $min_order_date);
		 $formatted_max_order_date = date('m/d/Y', $max_order_date);
		 ?>
		<script>
			jQuery(document).ready(function ($) {

			    $.fn.selectpicker.Constructor.BootstrapVersion = '4';
			    $('.selectpicker').selectpicker();

			    var select_year = $('select[name="primer_order_year"]').val();

                var max_year = 2035;
                var diff_year = 0;
                var min_order_date = "<?php echo $formatted_min_order_date; ?>";
                var max_order_date = "<?php echo $formatted_max_order_date; ?>";

                $('input[name="order_date_from"]').datepicker("option", "minDate", new Date(min_order_date));
                $('input[name="order_date_to"]').datepicker("option", "minDate", new Date(max_order_date));
                var date_from = $('input[name="order_date_from"]'),
                    date_to = $('input[name="order_date_to"]');
                $('input[name="order_date_from"], input[name="order_date_to"]').datepicker({
                    changeMonth: true,
                    changeYear: true,
                    dateFormat: "yy-mm-dd",
                    yearRange: "2021:2035",
                });


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

	function prepare_items() {

		$per_page = 20;

		$columns  = $this->get_columns();
		$hidden   = $this->hidden_columns;
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$get_total_orders = new PrimerOrderList();

		if (isset($_GET['order_status']) || isset($_GET['order_customer']) || isset($_GET['order_date_from']) || isset($_GET['order_date_to'])|| isset($_GET['order_receipt_status'])) {
			$get_orders_list = $get_total_orders->get_with_params($_REQUEST['order_date_from'], $_REQUEST['order_date_to'], $_GET['order_customer'], $_REQUEST['order_status'], $_GET['order_receipt_status']);
		} else {
			$get_orders_list = $get_total_orders->get();
		}


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

	function display() {
		/**
		 * Adds a nonce field
		 */
		wp_nonce_field( 'ajax-order-list-nonce', '_ajax_order_list_nonce' );

		parent::display();
	}

	/**
	 * @Override ajax_response method
	 */
	function ajax_response() {
		check_ajax_referer( 'ajax-order-list-nonce', '_ajax_order_list_nonce' );

		$this->prepare_items();


		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );


		ob_start();
		if ( ! empty( $_REQUEST['no_placeholder'] ) )
			$this->display_rows();
		else
			$this->display_rows_or_placeholder();
		$rows = ob_get_clean();

		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();

		ob_start();
		$this->pagination('top');
		$pagination_top = ob_get_clean();

		ob_start();
		$this->pagination('bottom');
		$pagination_bottom = ob_get_clean();

		$response = array( 'rows' => $rows );
		$response['pagination']['top'] = $pagination_top;
		$response['pagination']['bottom'] = $pagination_bottom;

		$response['column_headers'] = $headers;

		if ( isset( $total_items ) )
			$response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );

		if ( isset( $total_pages ) ) {
			$response['total_pages'] = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}

		die( wp_json_encode( $response ) );

	}

	function no_items() {
		_e( 'No orders found.', 'primer' );
	}

	function process_bulk_action() {
		//Detect when a bulk action is being triggered... then perform the action.

		$orders = isset( $_REQUEST['orders'] ) ? $_REQUEST['orders'] : array();
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
add_action( 'wp_ajax__ajax_fetch_primer_order', '_ajax_fetch_primer_order_callback' );

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

add_action('wp_ajax_ajax_primer_display', 'ajax_primer_display_callback');

add_action('wp_ajax_convert_select_orders', 'convert_select_orders');
function convert_select_orders() {

	$orders = isset($_GET['orders']) ? $_GET['orders'] : '';

	$response_data = '';

	if (!empty($orders)) {
		foreach ( $orders as $order_id ) {
			$order = wc_get_order( $order_id );
			foreach ( $order->get_items() as $item_id => $item_data ) {
				$id_of_order = $item_data->get_order_id();
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

				$order_country = $order->get_billing_country();

				$order_invoice_type = get_post_meta($id_of_order, '_billing_invoice_type', true);

				$insert_taxonomy = 'receipt_status';
				$invoice_term = '';

				if ($order_invoice_type == 'receipt' && $order_country == 'GR') {
					$invoice_term = 'greek_receipt';
				}
				if ($order_invoice_type == 'receipt' && $order_country !== 'GR') {
					$invoice_term = 'english_receipt';
				}
				if ($order_invoice_type == 'invoice' && $order_country == 'GR') {
					$invoice_term = 'greek_invoice';
				}
				if ($order_invoice_type == 'invoice' && $order_country !== 'GR') {
					$invoice_term = 'english_invoice';
				}

				$user_data = $user ? $user->display_name : '';

				$currency      = $order->get_currency();
				$currency_symbol = get_woocommerce_currency_symbol( $currency );

				$payment_method = $order->get_payment_method();
				$payment_title = $order->get_payment_method_title();
				$product_name = $item_data->get_name();
				$order_status = $order->get_status();

				if ($currency == 'EUR') {
					$post_id = wp_insert_post(array(
					'post_type' => 'primer_receipt',
					'post_title' => 'Receipt for order #' . $id_of_order,
					'comment_status' => 'closed',
					'ping_status' => 'closed',
					'post_status' => 'publish',
				));
				wp_set_object_terms($post_id, $invoice_term, $insert_taxonomy, false);

				if ($post_id) {
					update_post_meta($post_id, 'receipt_status', 'issued');
					update_post_meta($post_id, 'order_id_to_receipt', $id_of_order);
					update_post_meta($id_of_order, 'receipt_status', 'issued');
					add_post_meta($post_id, 'receipt_client', $user_data);
					add_post_meta($post_id, 'receipt_client_id', $user_id);
					add_post_meta($post_id, 'receipt_product', $product_name);
					add_post_meta($post_id, 'receipt_price', $order_total_price . ' ' .$currency_symbol);
					$response_data = '<div class="notice notice-success"><p>Orders converted</p></div>';
				}
				} else {
					$response_data = '<div class="notice notice-error"><p>'.__('Only euro is accepted.', 'primer').'</p></div>';
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

            list = {
                /** added method display
                 * for getting first sets of data
                 **/
                display: function () {

                    $.ajax({

						url: ajaxurl,
						dataType: 'json',
						data: {
						    _ajax_order_list_nonce: $('#_ajax_order_list_nonce').val(),
							action: 'ajax_primer_display'
						},
						success: function (response) {
						    $('#primer_order_table').html(response.display);

						    list.init();
						},
						error: function(xhr, status, error) {
  							var err = eval("(" + xhr.responseText + ")");
  							console.log(error);
						}
					});

				},

				init: function () {
                    var timer;
                    var delay = 500;

                    $('.tablenav-pages a').on('click', function (e) {
                        e.preventDefault();
                        var query = this.search.substring(1);

                        var data = {
                            paged: list.__query( query, 'paged' ) || '1',
						};
                        list.update(data);
					});

                    $('select[name="primer_order_year"]').on('change', function () {
                        var refer = $('input[name="_wp_http_referer"]').val();
                        refer = refer.substring(refer.indexOf("?") + 1);

                        var query = refer.substring(1);

                        setTimeout(function () {
                            var data = {
                            order_date_from: list.__query( query, 'order_date_from' ) || $('input[name="order_date_from"]').val(),
                            order_date_to: list.__query( query, 'order_date_to' ) || $('input[name="order_date_to"]').val(),
                            order_customer: list.__query( query, 'order_customer' ) || $('select[name="primer_order_client"]').val(),
                            order_status: list.__query( query, 'order_status' ) || $('select[name="primer_order_status[]"]').val(),
                            order_receipt_status: list.__query( query, 'order_receipt_status' ) || $('select#primer_receipt_status').val(),
						};
                        list.update(data);
                        }, 500)

                    });


                    $('input[name="order_date_from"]').on('change', function () {
                        var refer = $('input[name="_wp_http_referer"]').val();
                        refer = refer.substring(refer.indexOf("?") + 1);

                        var query = refer.substring(1);

                         var data = {
                            order_date_from: list.__query( query, 'order_date_from' ) || $(this).val(),
                            order_date_to: list.__query( query, 'order_date_to' ) || $('input[name="order_date_to"]').val(),
                            order_customer: list.__query( query, 'order_customer' ) || $('select[name="primer_order_client"]').val(),
                            order_status: list.__query( query, 'order_status' ) || $('select[name="primer_order_status[]"]').val(),
                            order_receipt_status: list.__query( query, 'order_receipt_status' ) || $('select#primer_receipt_status').val(),
						};
                        list.update(data);
                    });

                    $('input[name="order_date_to"]').on('change', function () {
                        var refer = $('input[name="_wp_http_referer"]').val();
                        refer = refer.substring(refer.indexOf("?") + 1);

                        var query = refer.substring(1);

                         var data = {
                            order_date_from: list.__query( query, 'order_date_from' ) || $('input[name="order_date_from"]').val(),
                            order_date_to: list.__query( query, 'order_date_to' ) || $(this).val(),
                            order_customer: list.__query( query, 'order_customer' ) || $('select[name="primer_order_client"]').val(),
                            order_status: list.__query( query, 'order_status' ) || $('select[name="primer_order_status[]"]').val(),
                            order_receipt_status: list.__query( query, 'order_receipt_status' ) || $('select#primer_receipt_status').val(),
						};
                        list.update(data);

                    });

                    $('select[name="primer_order_client"]').on('change', function () {
                        var refer = $('input[name="_wp_http_referer"]').val();
                        refer = refer.substring(refer.indexOf("?") + 1);

                        var query = refer.substring(1);

                         var data = {
                            order_date_from: list.__query( query, 'order_date_from' ) || $('input[name="order_date_from"]').val(),
                            order_date_to: list.__query( query, 'order_date_to' ) || $('input[name="order_date_to"]').val(),
                            order_customer: list.__query( query, 'order_customer' ) || $(this).val(),
                            order_status: list.__query( query, 'order_status' ) || $('select[name="primer_order_status[]"]').val(),
                            order_receipt_status: list.__query( query, 'order_receipt_status' ) || $('select#primer_receipt_status').val(),
						};
                        list.update(data);
                    });

                     $('select[name="primer_order_status[]"]').on('change', function () {
                        var refer = $('input[name="_wp_http_referer"]').val();
                        refer = refer.substring(refer.indexOf("?") + 1);

                        var query = refer.substring(1);

                         var data = {
                            order_date_from: list.__query( query, 'order_date_from' ) || $('input[name="order_date_from"]').val(),
                            order_date_to: list.__query( query, 'order_date_to' ) || $('input[name="order_date_to"]').val(),
                            order_customer: list.__query( query, 'order_customer' ) || $('select[name="primer_order_client"]').val(),
                            order_status: list.__query( query, 'order_status' ) || $(this).val(),
                            order_receipt_status: list.__query( query, 'order_receipt_status' ) || $('select#primer_receipt_status').val(),
						};
                        list.update(data);
                    });

                     $('select#primer_receipt_status').on('change', function () {
                         var refer = $('input[name="_wp_http_referer"]').val();
                        refer = refer.substring(refer.indexOf("?") + 1);

                        var query = refer.substring(1);
                        var data = {
                            order_date_from: list.__query( query, 'order_date_from' ) || $('input[name="order_date_from"]').val(),
                            order_date_to: list.__query( query, 'order_date_to' ) || $('input[name="order_date_to"]').val(),
                            order_customer: list.__query( query, 'order_customer' ) || $('select[name="primer_order_client"]').val(),
                            order_status: list.__query( query, 'order_status' ) || ($('select[name="primer_order_status[]"]').val().length ? $('select[name="primer_order_status[]"]').val() : ''),
                            order_receipt_status: list.__query( query, 'order_receipt_status' ) || $(this).val(),
						};
                        list.update(data);
                     });

                    $('input[name=paged]').on('keyup', function (e) {

                        if (13 == e.which)
                            e.preventDefault();

                        var data = {
                            paged: parseInt($('input[name=paged]').val()) || '1',
						};

                        window.clearTimeout(timer);
                        timer = window.setTimeout(function () {
                            list.update(data);
						}, delay)
					});

				},

                /** AJAX call
                 *
                 * Send the call and replace table parts with updated version!
                 *
                 * @param    object    data The data to pass through AJAX
                 */
				update: function (data) {

				    $.ajax({

						url: ajaxurl,
						data: $.extend(
							{
								_ajax_order_list_nonce: $('#_ajax_order_list_nonce').val(),
								action: '_ajax_fetch_primer_order',
							},
							data
						),
						success: function (response) {

						    var response = $.parseJSON(response);

						    if (response.rows.length)
						        $('#the-list').html(response.rows);
                            if (response.column_headers.length)
                                $('thead tr, tfoot tr').html(response.column_headers);
                            if (response.pagination.bottom.length)
                                $('.tablenav.top .tablenav-pages').html($(response.pagination.top).html());
                            if (response.pagination.top.length)
                                $('.tablenav.bottom .tablenav-pages').html($(response.pagination.bottom).html());

                            list.init();
                        }
					});
				},

                /**
                 * Filter the URL Query to extract variables
                 *
                 * @see http://css-tricks.com/snippets/javascript/get-url-variables/
                 *
                 * @param    string    query The URL query part containing the variables
                 * @param    string    variable Name of the variable we want to get
                 *
                 * @return   string|boolean The variable value if available, false else.
                 */
                __query: function (query, variable) {

                    var vars = query.split("&");
                    for (var i = 0; i < vars.length; i++) {
                        var pair = vars[i].split("=");
                        if (pair[0] == variable)
                            return pair[1];
					}
                    return false;
				},
			}

			list.display();


            function check_exist_receipts(orders) {
                var order_arr = new Array();
                $(orders).each(function (i, el) {
                    var tr_parent = $(el).parents('tr');
                    var sibling_td = tr_parent.find('td.receipt_status a');
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


            $('#tables-filter').on('submit', function (e) {
                e.preventDefault();

                check_exist_receipts($('input[name="orders[]"]:checked'));
                var count_orders = $('input[name="orders[]"]:checked').length;
                var receipt_word = count_orders == 1 ? 'receipt' : 'receipts';
                var confirmation = confirm('You are about to issue '+count_orders + ' ' + receipt_word+ '. Are you sure?');

                if (confirmation == true) {
                    var data = $(this).serialize();

                $.ajax({
                	url: ajaxurl,
                	data: data,
                	success: function (data) {
                	    if (data) {
                	        $('#wpbody-content').prepend(data);
                	        setTimeout(function (){
                	            document.location.reload();
                	        }, 1000);
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
