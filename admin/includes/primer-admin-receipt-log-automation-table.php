<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

require_once PRIMER_PATH . 'views/get_receipt_log_automation_list.php';


class PrimerReceiptLogAutomation extends WP_List_Table {

	function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Receipt Log', 'primer' ),
				'plural' => __( 'Receipts Logs', 'primer' ),
				'ajax' => false,
			)
		);

		$this->prepare_items();

		add_action( 'wp_print_scripts', [ __CLASS__, '_list_table_css' ] );

	}

	function get_columns() {
		return array(
			'receipt_log_automation_order_id'		=> __( 'Order No', 'primer' ),
			'receipt_log_automation_order_date' 	=> __( 'Order Date', 'primer' ),
			'receipt_log_automation_invoice_id'		=> __( 'Invoice No', 'primer' ),
			'receipt_log_automation_invoice_date' 	=> __( 'Invoice Date', 'primer' ),
			'receipt_log_automation_client'	=> __( 'Client', 'primer' ),
			'receipt_log_automation_status' => __( 'Issued receipt', 'primer' ),
			'receipt_log_automation_email'	=> __( 'Email Send', 'primer' ),
			'receipt_log_automation_error'	=> __( 'Receipt Error', 'primer' ),
			'receipt_log_automation_email_error' => __( 'Email error', 'primer' ),
		);
	}

	function get_sortable_columns() {
		return array();
	}

	function column_default( $item, $column_name ) {
		return $item[ $column_name ];

//		echo '<a href="' . esc_url( get_permalink($item['receipt_id']) ) . '" target="_blank" class="order-view"><strong>' . esc_attr( $item[ $column_name ] ) . '</strong></a>';
	}


	/**
	 * @var array
	 *
	 * Array contains slug columns that you want hidden
	 *
	 */
	private $hidden_columns = array( 'cb' );

	protected function get_bulk_actions() {
		return array();
	}

	function extra_tablenav( $which ){
		if ( $which !== 'bottom' ) {
			$primer_receipts = new PrimerReceiptLogListAutomation();
			?>
			<div class="alignleft actions">
				<h2><?php _e('Issue Receipts Report', 'primer'); ?></h2>
				<div class="filter_blocks_wrapper">
					<?php $check_errors = isset($_GET['only_errors']) ? $_GET['only_errors'] : ''; ?>
					<?php $check_issued = isset($_GET['only_issued']) ? $_GET['only_issued'] : ''; ?>
					<div class="filter_block">
						<label for="only_errors" style="float: left;">
							<input type="checkbox" name="only_errors"
								<?php if (!empty($check_errors)) {
									checked($_GET['only_errors'], 'on');
								} elseif (empty($check_errors) && empty($check_issued)) { echo 'checked';} ?> id="only_errors">
							<?php _e('Show only errors', 'primer'); ?>
						</label>
					</div>
					<div class="filter_block">
						<label for="only_issued" style="float: left;">
							<input type="checkbox" name="only_issued"
								<?php if (!empty($check_issued)) {
									checked($_GET['only_issued'], 'on');
								} elseif (empty($check_errors) && empty($check_issued)) { echo 'checked';} ?> id="only_issued">
							<?php _e('Show only issued', 'primer'); ?>
						</label>
					</div>
					<div class="apply_btn"><input type="submit" class="button" name="filter_action" value="<?php _e('Apply filter', 'primer'); ?>" /></div>

				</div>
			</div>

			<script>
                jQuery(document).ready(function ($) {

                    var atLeastOneIsChecked = $('input[name="receipts[]"]:checked').length > 0;
                    if (atLeastOneIsChecked) {
                        $('.convert_receipts input[type="submit"]').removeAttr('disabled');
                        $('.resend_receipt_to_customer').removeAttr('disabled');
                    }
                    function checker() {
                        var length_inputs = $('input[name="receipts[]"]').length;
                        var trues = new Array();
                        $('input[name="receipts[]"]').each(function (i, el) {

                            if ($(el).prop('checked') == true || $(el).is(':checked') == true) {
                                $('.convert_receipts input[type="submit"]').removeAttr('disabled');
                                $('.resend_receipt_to_customer').removeAttr('disabled');
                                trues.push($(el));
                            }
                        })
                        if (trues.length <= 0) {
                            $('.convert_receipts input[type="submit"]').attr('disabled', true);
                            $('.resend_receipt_to_customer').attr('disabled', true);
                        }
                    }

                    $('.wp-list-table #cb input:checkbox').on('click', function () {
                        checker();
                        if ($(this).is(':checked')) {
                            $('.convert_receipts input[type="submit"]').removeAttr('disabled');
                            $('.resend_receipt_to_customer').removeAttr('disabled');
                        } else {
                            $('.convert_receipts input[type="submit"]').attr('disabled', true);
                            $('.resend_receipt_to_customer').attr('disabled', true);
                        }
                    });
                    $('.wp-list-table input[name="receipts[]"]').on('click', function () {
                        checker();
                    });

                    function popupOpenClose(popup) {
                        if ($('.popup_wrapper').length == 0) {
                            $(popup).wrapInner("<div class='popup_wrapper'></div>")
                        }
                        $(popup).show();

                        $(popup).click(function (e) {
                            if (e.target == this) {
                                if ($(popup).is(':visible')) {
                                    $(popup).hide();
								}
							}
						})

                    }

                    $('#tables-receipt-filter .resend_receipt_to_customer').on('click', function (e) {
                        e.preventDefault();
                        $('.resend_receipt_to_customer').attr('disabled', true);
                        var checked_receipts_data = $('#tables-receipt-filter input[name="receipts[]"]').serialize();

                        $.ajax({
                            url: primer.ajax_url,
                            data: 'action=primer_resend_receipt_to_customer&'+checked_receipts_data,
                            type: 'post',
                            dataType: 'json',
                            beforeSend: function(){
                                $('table.table-view-list.receipts').css({'opacity': '0.5'});
                                $('.loadingio-spinner-spinner-chyosfc7wi6').show();
                            },
                            success: function (response) {
                                if (response.success == 'true' && response.response !== false) {
                                    // console.log(response.response_wrap);
                                    setTimeout(function () {
                                        $('.loadingio-spinner-spinner-chyosfc7wi6').hide();
                                        $('table.table-view-list.receipts').css({'opacity': '1'});
                                        $('table.table-view-list.receipts').append(response.response_wrap);
                                        popupOpenClose('.primer_popup');
									}, 1000);
                                    setTimeout(function () {
                                        document.location.reload();
									}, 1700)
								}
                            }
                        })

                    })

                });
			</script>
		<?php } ?>
	<?php
	}

	function prepare_items() {

		$per_page = 20;

		$get_total_receipts_logs = new PrimerReceiptLogListAutomation();


		if ((isset($_GET['only_errors']) || isset($_GET['only_issued']))) {
			$log_errors = isset($_GET['only_errors']) ? $_GET['only_errors'] : '';
			$log_issued = isset($_GET['only_issued']) ? $_GET['only_issued'] : '';
			$get_receipts_logs_list = $get_total_receipts_logs->get_with_params($log_errors, $log_issued);
		} else {
			$get_receipts_logs_list = $get_total_receipts_logs->get();
		}

		$columns  = $this->get_columns();
		$hidden   = $this->hidden_columns;
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );


		$this->items = $get_receipts_logs_list;

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
		_e( 'No receipts report found.', 'primer' );
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

	function show_all_receipts_logs_automation() {
		ob_start();
		$status = filter_input( INPUT_GET, 'status' );
		include_once PRIMER_PATH . 'views/admin_receipt_log_automation_list.php';
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
				case 'primer_receipts_logs':
					echo $this->show_all_receipts_logs();
					break;
				case 'primer_receipts_logs_automation':
					echo $this->show_all_receipts_logs_automation();
					break;
				default:
					// Show the orders listing by default
					echo $this->show_all_orders();
					break;
			}

			echo '</div>';
	}

}

