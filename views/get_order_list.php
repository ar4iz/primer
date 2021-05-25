<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }

class PrimerOrderList {
	public $orders_array = array();
	public $orders_customers = array();

	public $orders_date_range = array();

	public function get() {
		$order_args = array(
			'return' => 'ids',
			'limit' => 9999,
			'status' => array('wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed'),
			'order' => 'DESC',
		);

		$query_orders = new WC_Order_Query( $order_args );
		$orders       = $query_orders->get_orders();
		$order_count = 0;
		foreach ( $orders as $order_id ) {
			$order = wc_get_order( $order_id );

			$receipt_order_status = get_post_meta($order_id, 'receipt_status', true);
			if (empty($receipt_order_status)) {
				update_post_meta($order_id, 'receipt_status', 'not_issued');
			}

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

				$currency      = $order->get_currency();
				$currency_symbol = get_woocommerce_currency_symbol( $currency );
				$payment_method = $order->get_payment_method();
				$payment_title = $order->get_payment_method_title();
				$product_name = $item_data->get_name();
				$order_status = $order->get_status();

				$receipt_status_from_meta_text = 'Not Issued';
				$receipt_status_from_meta = get_post_meta($id_of_order, 'receipt_status', true);
				if (!empty($receipt_status_from_meta) && $receipt_status_from_meta == 'issued') {
					$receipt_status_from_meta_text = 'Issued';
				}

				$receipt_date = '';
				$exist_receipt_id = get_order_from_receipt($id_of_order);
				if (!empty($exist_receipt_id)) {
					$receipt_date = get_the_date('F j, Y', $exist_receipt_id[0]);
				}

				$this->orders_array[$order_count]['order_id'] = $id_of_order;
				$this->orders_array[$order_count]['order_date'] = $order_paid_date;
				$this->orders_array[$order_count]['order_hour'] = $order_paid_hour;
				$this->orders_array[$order_count]['order_client'] = $user ? $user->display_name : '';
				$this->orders_array[$order_count]['order_product'] = $product_name;
				$this->orders_array[$order_count]['order_price'] = $order_total_price . ' ' .$currency_symbol;
				$this->orders_array[$order_count]['order_status'] = $order_status;
				$this->orders_array[$order_count]['payment_status'] = $payment_title;
				$this->orders_array[$order_count]['receipt_date'] = $receipt_date;
				$this->orders_array[$order_count]['receipt_status'] = $receipt_status_from_meta_text;
				$this->orders_array[$order_count]['receipt_id'] = $exist_receipt_id ? $exist_receipt_id[0] : '';
			}
			$order_count++;
		}

		return $this->orders_array;
	}

	public function get_users_from_orders() {
		$order_args = array(
			'return' => 'ids',
			'limit' => 9999,
			'status' => array('wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed'),
			'order' => 'DESC',
		);
		$query_orders = new WC_Order_Query( $order_args );
		$orders       = $query_orders->get_orders();
		$order_count = 0;
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
				}

				$order_total_price = $order->get_total();
				$user_id   = $order->get_user_id();
				$user      = $order->get_user();

				$currency      = $order->get_currency();
				$currency_symbol = get_woocommerce_currency_symbol( $currency );
				$payment_method = $order->get_payment_method();
				$payment_title = $order->get_payment_method_title();
				$product_name = $item_data->get_name();
				$order_status = $order->get_status();

				$receipt_status_from_meta_text = 'Not Issued';
				$receipt_status_from_meta = get_post_meta($id_of_order, 'receipt_status', true);
				if (!empty($receipt_status_from_meta) && $receipt_status_from_meta == 'issued') {
					$receipt_status_from_meta_text = 'Issued';
				}

				$receipt_date = '';
				$exist_receipt_id = get_order_from_receipt($id_of_order);
				if (!empty($exist_receipt_id)) {
					$receipt_date = get_the_date('F j, Y', $exist_receipt_id[0]);
				}

				$this->orders_customers[$order_count]['order_id'] = $id_of_order;
				$this->orders_customers[$order_count]['order_date'] = $order_paid_date;
				$this->orders_customers[$order_count]['order_hour'] = $order_paid_hour;
				$this->orders_customers[$order_count]['order_client'] = $user ? $user->display_name : '';
				$this->orders_customers[$order_count]['order_client_id'] = $user_id ? $user_id : '0';
				$this->orders_customers[$order_count]['order_product'] = $product_name;
				$this->orders_customers[$order_count]['order_price'] = $order_total_price . ' ' .$currency_symbol;
				$this->orders_customers[$order_count]['order_status'] = $order_status;
				$this->orders_customers[$order_count]['payment_status'] = $payment_title;
				$this->orders_customers[$order_count]['receipt_date'] = $receipt_date;
				$this->orders_customers[$order_count]['receipt_status'] = $receipt_status_from_meta_text;
				$this->orders_customers[$order_count]['receipt_id'] = $exist_receipt_id ? $exist_receipt_id[0] : '';
			}
			$order_count++;
		}
		return $this->orders_customers;
	}

	public function get_dates_from_orders() {

		$order_args = array(
			'return' => 'ids',
			'limit' => 9999,
			'status' => array('wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed'),
			'order' => 'DESC',
		);
		$query_orders = new WC_Order_Query( $order_args );
		$orders       = $query_orders->get_orders();
		$order_count = 0;
		foreach ( $orders as $order_id ) {
			$order = wc_get_order( $order_id );
			foreach ( $order->get_items() as $item_id => $item_data ) {
				$id_of_order = $item_data->get_order_id();
				$order_create_date = date( 'F j, Y', $order->get_date_created()->getTimestamp());
				$order_paid_date = null;
				$order_paid_hour = null;
				if (!empty($order->get_date_paid())) {
					$order_paid_date = date( 'F j, Y', $order->get_date_paid()->getTimestamp());
					$order_paid_hour = date( 'H:i:s', $order->get_date_paid()->getTimestamp());
				}

				$order_total_price = $order->get_total();
				$user_id   = $order->get_user_id();
				$user      = $order->get_user();

				$currency      = $order->get_currency();
				$currency_symbol = get_woocommerce_currency_symbol( $currency );
				$payment_method = $order->get_payment_method();
				$payment_title = $order->get_payment_method_title();
				$product_name = $item_data->get_name();
				$order_status = $order->get_status();

				$this->orders_date_range[] = strtotime($order_create_date);

			}
			$order_count++;
		}
		return $this->orders_date_range;
	}

	public function get_with_params($order_date_from, $order_date_to, $order_customer, $order_status, $order_receipt_status) {
		global $woocommerce;

		$order_status = isset($_GET['primer_order_status']) ? $_GET['primer_order_status'] : array('wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed');

		$order_args = array(
			'return' => 'ids',
			'limit' => 9999,
			'status' => $order_status,
			'order' => 'DESC',
		);
		$order_args['numberposts'] = -1;
		$order_args2 = $order_args;

		// receipt_status
		$order_args['meta_key'] = '_customer_user';
		$order_args['meta_value'] = $_GET['primer_order_client'];

		if (!empty($_GET['primer_receipt_status'])) {
			$order_args2['meta_key'] = 'receipt_status';
			$order_args2['meta_value'] = $_GET['primer_receipt_status'];
		}

		$order_date_from = $_GET['order_date_from'];
		$order_date_to = $_GET['order_date_to'];

		if (empty($order_date_from) && !empty($order_date_to)) {
			$order_args['date_created'] = $order_date_to;
		}

		if (empty($order_date_to) && !empty($order_date_from)) {
			$order_args['date_created'] = '>='.$order_date_from;
		}

		if (!empty($order_date_from) && !empty($order_date_to)) {
			$order_args['date_created'] = $order_date_from.'...'.$order_date_to;
		}


		$query_orders = wc_get_orders($order_args);
		$query_orders2 = wc_get_orders($order_args2);
		if (!empty($_GET['primer_order_client']) && !empty($_GET['primer_receipt_status'])) {
			$unique_query_orders = array_intersect($query_orders, $query_orders2);
			$query_orders = $unique_query_orders;
		}

//		$query_orders = new WC_Order_Query( $order_args );
//		$orders       = $query_orders->get_orders();
		$orders       = $query_orders;
		$order_count = 0;
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
				}

				$order_total_price = $order->get_total();
				$user_id   = $order->get_user_id();
				$user      = $order->get_user();

				$currency      = $order->get_currency();
				$currency_symbol = get_woocommerce_currency_symbol( $currency );
				$payment_method = $order->get_payment_method();
				$payment_title = $order->get_payment_method_title();
				$product_name = $item_data->get_name();
				$order_status = $order->get_status();

				$receipt_status_from_meta_text = 'Not Issued';
				$receipt_status_from_meta = get_post_meta($id_of_order, 'receipt_status', true);
				if (!empty($receipt_status_from_meta) && $receipt_status_from_meta == 'issued') {
					$receipt_status_from_meta_text = 'Issued';
				}

				$receipt_date = '';
				$exist_receipt_id = get_order_from_receipt($id_of_order);
				if (!empty($exist_receipt_id)) {
					$receipt_date = get_the_date('F j, Y', $exist_receipt_id[0]);
				}

				$this->orders_array[$order_count]['order_id'] = $id_of_order;
				$this->orders_array[$order_count]['order_date'] = $order_paid_date;
				$this->orders_array[$order_count]['order_hour'] = $order_paid_hour;
				$this->orders_array[$order_count]['order_client'] = $user ? $user->display_name : '';
				$this->orders_array[$order_count]['order_product'] = $product_name;
				$this->orders_array[$order_count]['order_price'] = $order_total_price . ' ' .$currency_symbol;
				$this->orders_array[$order_count]['order_status'] = $order_status;
				$this->orders_array[$order_count]['payment_status'] = $payment_title;
				$this->orders_array[$order_count]['receipt_date'] = $receipt_date;
				$this->orders_array[$order_count]['receipt_status'] = $receipt_status_from_meta_text;
				$this->orders_array[$order_count]['receipt_id'] = $exist_receipt_id ? $exist_receipt_id[0] : '';
			}
			$order_count++;
		}

		return $this->orders_array;
	}
}



/**
 * Handle a custom 'customvar' query var to get orders with the 'customvar' meta.
 * @param array $query - Args for WP_Query.
 * @param array $query_vars - Query vars from WC_Order_Query.
 * @return array modified $query
 */
function handle_custom_query_var( $query, $query_vars ) {
	if ( ! empty($query_vars['receipt_status']) ) {
		$query['meta_query'][] = array(
			'key' => 'receipt_status',
			'value' => esc_attr($query_vars['receipt_status'] ),
		);
	}

	return $query;
}
add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'handle_custom_query_var', 10, 2 );


function get_order_from_receipt($order_id) {
	$invoice_id = array();
	$post_args = array(
		'posts_per_page' => -1,
		'post_type' => 'primer_receipt',
	);

	$receipt_query = new WP_Query( $post_args );

	if ($receipt_query->have_posts()):
		while ($receipt_query->have_posts()):
			$receipt_query->the_post();
			$receipt_status_text = '';
			$receipt_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
			if (!empty($receipt_id)) {
				if ($receipt_id == $order_id) {
					$invoice_id[] = get_the_ID();
				}
			}
		endwhile;
	endif;
	wp_reset_postdata();

	return $invoice_id;
}
