<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }

class PrimerReceiptList {
	public $receipt_array = array();
	public $receipt_params_array = array();
	public $receipt_customers = array();

	public $receipt_date_range = array();

	public function get() {
		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();
				$receipt_status_text = '';
				$receipt_status = get_post_meta(get_the_ID(), 'receipt_status', true);
				switch ($receipt_status) {
					case 'issued':
						$receipt_status_text = 'Issued';
						break;
					case 'not_issued':
						$receipt_status_text = 'Not Issued';
						break;
				}

				$receipt_in_log = '';
				$receipt_log_status = get_post_meta(get_the_ID(), 'exist_error_log', true);
				/*if (!empty($receipt_log_status)) {
					$receipt_in_log = __('Log', 'primer');
				}*/

				$receipt_in_log = __('Log', 'primer');

				$order_from_invoice = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);

				$invoice_client = get_post_meta(get_the_ID(), 'receipt_client', true);

				$total_order = wc_get_order( $order_from_invoice );

				$user_first_name = $total_order->get_billing_first_name();
				$user_last_name = $total_order->get_billing_last_name();

				$user_full_name = $user_first_name . ' ' . $user_last_name;

				if (empty($invoice_client)) {
					$invoice_client = $user_full_name;
				}

				$this->receipt_array[$receipt_count]['receipt_id'] = get_the_ID();
				$this->receipt_array[$receipt_count]['receipt_date'] = get_the_date();
				$this->receipt_array[$receipt_count]['receipt_hour'] = get_the_time();
				$this->receipt_array[$receipt_count]['receipt_client'] = $invoice_client;
				$this->receipt_array[$receipt_count]['receipt_product'] = get_post_meta(get_the_ID(), 'receipt_product', true);
				$this->receipt_array[$receipt_count]['receipt_price'] = get_post_meta(get_the_ID(), 'receipt_price', true);
				$this->receipt_array[$receipt_count]['receipt_status'] = $receipt_status_text;
				$this->receipt_array[$receipt_count]['receipt_error_status'] = $receipt_in_log;
				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_array;
	}

	public function get_users_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
		);

		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();

				$user_display_name = get_post_meta(get_the_ID(), 'receipt_client_id', true);

				$order_id = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);
				$order = wc_get_order( $order_id );
				$order_user_first_name = $order->get_billing_first_name();
				$order_user_last_name = $order->get_billing_last_name();


				$customer_full_name = get_post_meta(get_the_ID(), 'receipt_client', true);
				if (empty($customer_full_name)) {
					$customer_full_name = $order_user_first_name . ' ' . $order_user_last_name;
				}

				$user_data = get_user_by('ID', $user_display_name);

				$user_id = get_post_meta(get_the_ID(), 'receipt_client_id', true);
				if (empty($user_id)) {
					$user_id = 0;
				}

				$this->receipt_customers[$receipt_count]['receipt_client'] = $customer_full_name;
				$this->receipt_customers[$receipt_count]['receipt_client_id'] = $user_id;
				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_customers;
	}

	public function get_dates_from_receipts() {

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
		);

		$receipt_query = new WP_Query( $receipt_args );

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();
				$this->receipt_date_range[] = strtotime(get_the_date('F j, Y H:i:s', get_the_ID()));
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_date_range;
	}

	public function get_with_params($receipt_date_from, $receipt_date_to, $receipt_customer, $receipt_status) {
		$receipt_status = isset($_GET['primer_receipt_status']) ? $_GET['primer_receipt_status'] : '';
		$receipt_customer = isset($_GET['primer_receipt_client']) ? $_GET['primer_receipt_client'] : '';
		$receipt_date_from = isset($_GET['receipt_date_from']) ? $_GET['receipt_date_from'] : '';
		$receipt_date_to = isset($_GET['receipt_date_to']) ? $_GET['receipt_date_to'] : '';

		$meta_values = array();

		$receipt_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt',
		);

		if (!empty($receipt_status)) {
			$meta_values['receipt_status'][] = $receipt_status;
		}

		if (!empty($receipt_customer)) {
			$meta_values['receipt_client'][] = $receipt_customer;
		}

		if (!empty($meta_values)) {
			$receipt_args['meta_query']['relation'] = 'AND';
			$i = 0;
			foreach ( $meta_values as $key => $meta_value ) {
				$i++;
				$receipt_args['meta_query'][$i]['key'] = $key;
				$receipt_args['meta_query'][$i]['value'] = $meta_value;
				$receipt_args['meta_query'][$i]['compare'] = 'IN';
			}
		}

		if (!empty($receipt_date_from) || !empty($receipt_date_to)) {
			$receipt_args['date_query']['relation'] = 'AND';
			$receipt_args['date_query'][] = array(
				'after' => $receipt_date_from,
				'before' => $receipt_date_to,
				'compare' => 'BETWEEN',
				'inclusive' => true,
			);
		}


		$receipt_query = new WP_Query( $receipt_args );
		$receipt_count = 0;

		if ($receipt_query->have_posts()):
			while ($receipt_query->have_posts()):
				$receipt_query->the_post();
				$receipt_status_text = '';
				$receipt_status = get_post_meta(get_the_ID(), 'receipt_status', true);
				switch ($receipt_status) {
					case 'issued':
						$receipt_status_text = 'Issued';
						break;
					case 'not_issued':
						$receipt_status_text = 'Not Issued';
						break;
				}

				$order_from_invoice = get_post_meta(get_the_ID(), 'order_id_to_receipt', true);

				$invoice_client = get_post_meta(get_the_ID(), 'receipt_client', true);

				$total_order = wc_get_order( $order_from_invoice );

				$user_first_name = $total_order->get_billing_first_name();
				$user_last_name = $total_order->get_billing_last_name();

				$user_full_name = $user_first_name . ' ' . $user_last_name;

				if (empty($invoice_client)) {
					$invoice_client = $user_full_name;
				}


				$this->receipt_params_array[$receipt_count]['receipt_id'] = get_the_ID();
				$this->receipt_params_array[$receipt_count]['receipt_date'] = get_the_date();
				$this->receipt_params_array[$receipt_count]['receipt_hour'] = get_the_time();
				$this->receipt_params_array[$receipt_count]['receipt_client'] = $invoice_client;
				$this->receipt_params_array[$receipt_count]['receipt_product'] = get_post_meta(get_the_ID(), 'receipt_product', true);
				$this->receipt_params_array[$receipt_count]['receipt_price'] = get_post_meta(get_the_ID(), 'receipt_price', true);
				$this->receipt_params_array[$receipt_count]['receipt_status'] = $receipt_status_text;
				$this->receipt_params_array[$receipt_count]['receipt_error_status'] = '';
				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_params_array;
	}
}
