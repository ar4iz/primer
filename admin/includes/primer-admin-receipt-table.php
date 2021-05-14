<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

require_once PRIMER_PATH . 'views/get_receipt_list.php';


class PrimerReceipt extends WP_List_Table {

	function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Receipt', 'primer' ),
				'plural' => __( 'Receipts', 'primer' ),
				'ajax' => false,
			)
		);

		$this->prepare_items();

		add_action( 'wp_print_scripts', [ __CLASS__, '_list_table_css' ] );

	}

	function get_columns() {
		return array(
			'cb'		 	=> '<input type="checkbox" />',
			'receipt_id'		=> __( 'No', 'primer' ),
			'receipt_date' 	=> __( 'Receipt Date', 'primer' ),
			'receipt_hour'	=> __( 'Hour', 'primer' ),
			'receipt_client'	=> __( 'Client', 'primer' ),
			'receipt_product' => __( 'Products', 'primer' ),
			'receipt_price'	=> __( 'Total Price', 'primer' ),
			'receipt_status'	=> __( 'Receipt Status', 'primer' ),
			'receipt_error_status' => __( 'Errors', 'primer' ),
		);
	}

	function get_sortable_columns() {
		return array();
	}

	function column_default( $item, $column_name ) {
//		return $item[ $column_name ];

		echo '<a href="' . esc_url( get_permalink($item['receipt_id']) ) . '" target="_blank" class="order-view"><strong>' . esc_attr( $item[ $column_name ] ) . '</strong></a>';
	}

	/**
	 * @var array
	 *
	 * Array contains slug columns that you want hidden
	 *
	 */

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="receipts[]" id="receipt_'.$item['receipt_id'].'" value="%s" />',
			$item['receipt_id']
		);
	}

	protected function get_bulk_actions() {
		return array();
	}

	function extra_tablenav( $which ){
		if ( $which !== 'bottom' ) {
			$primer_receipts = new PrimerReceiptList();
			$primer_receipts_customers = $primer_receipts->get_users_from_receipts();
			?>
			<div class="alignleft actions">
				<h2><?php _e('Filters', 'primer'); ?></h2>
				<h3><?php _e('Date Range:', 'primer'); ?></h3>
				<div class="filter_blocks_wrapper">
					<div class="left_wrap">
						<div class="filter_block">
							<label for="primer_receipt_year" style="float: left;"><?php _e('Year: ', 'primer'); ?></label>
							<select name="primer_receipt_year" id="primer_receipt_year">
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
							<label for="receipt_date_from">
								<?php _e('From: ', 'primer'); ?></label>
							<input type="text" id="receipt_date_from" name="receipt_date_from" placeholder="Date From" value="" />
							<label for="receipt_date_to">
								<?php _e('To: ', 'primer'); ?></label>
							<input type="text" id="receipt_date_to" name="receipt_date_to" placeholder="Date To" value="" />
						</div>
						<div class="filter_block">
						<label for="primer_receipt_client" style="float: left;"><?php _e('Client: ', 'primer'); ?></label>
							<select name="primer_receipt_client" id="primer_receipt_client">
								<option value=""><?php _e('Select clients', 'primer'); ?></option>
								<?php
								$primer_receipts_customers = array_unique($primer_receipts_customers, SORT_REGULAR);
								$get_customer = isset($_GET['primer_receipt_client']) ? $_GET['primer_receipt_client'] : '';
								foreach ( $primer_receipts_customers as $receipt_customer ) {
									if ( $receipt_customer['receipt_client_id'] ) { ?>
										<option value="<?php echo $receipt_customer['receipt_client']; ?>" <?php selected($get_customer, $receipt_customer['receipt_client']); ?>><?php echo $receipt_customer['receipt_client']; ?></option>
									<?php } else { ?>
										<option value="<?php echo $receipt_customer['receipt_client']; ?>" <?php selected($get_customer, $receipt_customer['receipt_client']); ?>><?php _e( 'Guest client', 'primer' ); ?></option>
									<?php }
								} ?>
							</select>
					</div>
					</div>
					<div class="right_wrap">
						<div class="filter_block">
							<label for="primer_receipt_status" style="float: left;"><?php _e('Receipt Status: ', 'primer'); ?></label>
							<select name="primer_receipt_status" title="<?php _e('Select receipt status', 'primer'); ?>" id="primer_receipt_status">
								<?php
								//								$status_of_orders = wc_get_order_statuses();
								$get_status = isset($_GET['primer_receipt_status']) ? $_GET['primer_receipt_status'] : '';
								$status_of_receipts = array(
									'issued' => 'Issued',
									'issued_with_errors' => 'Issued with errors',
									'failed_to_issue' => 'Failed to issue'
								);

								foreach ( $status_of_receipts as $status_k => $status_value ) { ?>
									<option value="<?php echo $status_k; ?>" <?php selected($status_k, $get_status); ?>><?php echo $status_value; ?></option>
								<?php }
								?>
							</select>
						</div>

					</div>
					<div><input type="submit" class="button" name="filter_action" value="<?php _e('Filter', 'primer'); ?>" /></div>
				</div>
			</div>

			<div class="loadingio-spinner-spinner-chyosfc7wi6"><div class="ldio-drsjmtezgls"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>


			<?php
			$receipts_dates = $primer_receipts->get_dates_from_receipts();
			$min_receipt_date = min($receipts_dates);
			$max_receipt_date = max($receipts_dates);
			$formatted_min_receipt_date = date('m/d/Y', $min_receipt_date);
			$formatted_max_receipt_date = date('m/d/Y', $max_receipt_date);
			?>
			<script>
                jQuery(document).ready(function ($) {

                    $.fn.selectpicker.Constructor.BootstrapVersion = '4';
                    $('.selectpicker').selectpicker();

                    $('#tables-filter .tablenav.bottom').remove();

                    var select_year = $('select[name="primer_receipt_year"]').val();

                    var max_year = 2035;
                    var diff_year = 0;
                    var min_receipt_date = "<?php echo $formatted_min_receipt_date; ?>";
                    var max_receipt_date = "<?php echo $formatted_max_receipt_date; ?>";

                    var date_from = $('input[name="receipt_date_from"]'),
                        date_to = $('input[name="receipt_date_to"]');
                    $('input[name="receipt_date_from"], input[name="receipt_date_to"]').datepicker({
                        changeMonth: true,
                        changeYear: true,
                        dateFormat: "yy-mm-dd",
                        yearRange: "2021:2035",
                    });
                    $('input[name="receipt_date_from"]').datepicker("option", "minDate", new Date(min_receipt_date));
                    $('input[name="receipt_date_to"]').datepicker("option", "minDate", new Date(max_receipt_date));

                    <?php if (isset($_GET['receipt_date_from'])) { ?>
                    $('input[name="receipt_date_from"]').datepicker("setDate", new Date(<?php echo $_GET['receipt_date_from']; ?>));
					<?php } ?>

	                <?php if (isset($_GET['receipt_date_to'])) { ?>
                    $('input[name="receipt_date_to"]').datepicker("setDate", new Date(<?php echo $_GET['receipt_date_to']; ?>));
	                <?php } ?>


                    $('select[name="primer_receipt_year"]').on('change', function () {
                        select_year = $(this).val();
                        $('input[name="receipt_date_from"], input[name="receipt_date_to"]').datepicker("destroy");
                        $('input[name="receipt_date_from"], input[name="receipt_date_to"]').datepicker({
                            changeMonth: true,
                            changeYear: true,
                            dateFormat: "yy-mm-dd",
                            yearRange: `${select_year}:${max_year}`,
                        });
                        var currentDate = new Date();
                        var currentDay = currentDate.getDate();
                        var currentMonth = currentDate.getMonth()+1;
                        var set_current_date = currentMonth + '/' + currentDay + '/' + select_year;
                        $('input[name="receipt_date_from"]').datepicker("setDate", new Date(set_current_date))
                    });

                    // the rest part of the script prevents from choosing incorrect date interval
                    date_from.on( 'change', function() {
                        date_to.datepicker( 'option', 'minDate', date_from.val() );
                    });

                    date_to.on('change', function () {
                        date_to.datepicker('option', 'maxDate', date_to.val());
                    });

                    var atLeastOneIsChecked = $('input[name="receipts[]"]:checked').length > 0;
                    if (atLeastOneIsChecked) {
                        $('.convert_receipts input[type="submit"]').removeAttr('disabled');
                    }
                    function checker() {
                        var length_inputs = $('input[name="receipts[]"]').length;
                        var trues = new Array();
                        $('input[name="receipts[]"]').each(function (i, el) {

                            if ($(el).prop('checked') == true || $(el).is(':checked') == true) {
                                $('.convert_receipts input[type="submit"]').removeAttr('disabled');
                                trues.push($(el));
                            }
                        })
                        if (trues.length <= 0) {
                            $('.convert_receipts input[type="submit"]').attr('disabled', true);
                        }
                    }

                    $('.wp-list-table #cb input:checkbox').on('click', function () {
                        checker();
                        if ($(this).is(':checked')) {
                            $('.convert_receipts input[type="submit"]').removeAttr('disabled');
                        } else {
                            $('.convert_receipts input[type="submit"]').attr('disabled', true);
                        }
                    });
                    $('.wp-list-table input[name="receipts[]"]').on('click', function () {
                        checker();
                    });


                    $('#tables-receipt-filter #zip_load').on('click', function (e) {
                        e.preventDefault();
                        dataObj = new Array();
                        var dat = $('#tables-receipt-filter').serializeArray();
                        $(dat).each(function (i, el) {
                            if (el.name == 'receipts[]') {
                                dataObj.push(el.value);
							}
						});

                        var datas = {
                            'action': 'primer_export_receipt_to_html',
							'page_id': dataObj.join(', '),
						}

                        $('.download-btn').addClass('hide');

                        $.ajax({
							url: primer.ajax_url,
							data: datas,
							type: 'post',
							dataType: 'json',
                            beforeSend: function(){},
							success: function (r) {
							    if (r.success == 'true') {
                                    if (r.response) {
                                        setTimeout(function () {
                                            var datas = {
                                                'action': 'create_primer_the_zip_file',
												'page_id': dataObj.join(', '),
											};

                                            $.ajax({
                                                url: primer.ajax_url,
                                                data: datas,
                                                type: 'post',
                                                dataType: 'json',
                                                beforeSend: function(){
                                                    $('table.table-view-list.receipts').css({'opacity': '0.5'});
                                                    $('.loadingio-spinner-spinner-chyosfc7wi6').show();
												},
												success: function (r) {
                                                    if(r.success == 'true' && r.response !== false ) {
                                                       setTimeout(function () {
                                                           setTimeout(function () {
                                                               $('#zip_load').hide();
                                                               $('.download-btn').attr('href', r.response).removeClass('hide');
                                                               $('.loadingio-spinner-spinner-chyosfc7wi6').hide();
                                                               $('table.table-view-list.receipts').css({'opacity': '1'});
                                                               $('.download-btn').get(0).click();
														   }, 1000);
													   }, 1500);
													}
												},
											});
										}, 1000);
									}
								}
							},
						})
                    })

                });
			</script>
		<?php } ?>
	<?php
	}

	function prepare_items() {

		$per_page = 20;

		$get_total_receipts = new PrimerReceiptList();

		if (isset($_GET['primer_receipt_status']) || isset($_GET['primer_receipt_client']) || isset($_GET['receipt_date_from']) || isset($_GET['receipt_date_to'])) {
			$get_receipts_list = $get_total_receipts->get_with_params($_GET['receipt_date_from'], $_GET['receipt_date_to'], $_GET['primer_receipt_client'], $_GET['primer_receipt_status']);
		} else {
			$get_receipts_list = $get_total_receipts->get();
		}

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );


		$this->items = $get_receipts_list;

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


	function no_items() {
		_e( 'No receipts found.', 'primer' );
	}

	function process_bulk_action() {
		//Detect when a bulk action is being triggered... then perform the action.

		$receipts = isset( $_REQUEST['receipts'] ) ? $_REQUEST['receipts'] : array();
		$receipts = array_map( 'sanitize_text_field', $receipts );

		$current_action = $this->current_action();
		if ( ! empty( $current_action ) ) {
			//Bulk operation action. Lets make sure multiple records were selected before going ahead.
			if ( empty( $receipts ) ) {
				echo '<div id="message" class="error"><p>Error! You need to select multiple records to perform a bulk action!</p></div>';
				return;
			}
		} else {
			// No bulk operation.
			return;
		}

	}


	function show_all_receipts() {
		ob_start();
		$status = filter_input( INPUT_GET, 'status' );
		include_once PRIMER_PATH . 'views/admin_receipt_list.php';
		$output = ob_get_clean();
		return $output;
	}

	function handle_main_primer_receipt_admin_menu() {
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
		 <?php } elseif ($_GET['page'] === 'primer_receipts') {
		 	do_action( 'primer_menu_body_' . $action );

			//Allows an addon to completely override the body section of the members admin menu for a given action.
			$output = apply_filters( 'primer_menu_body_override', '', $action );
			if ( ! empty( $output ) ) {
				//An addon has overriden the body of this page for the given action. So no need to do anything in core.
				echo $output;
				echo '</div>'; //<!-- end of wrap -->
				return;
			}
		 } ?>

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

