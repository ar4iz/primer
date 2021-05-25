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
				$this->receipt_array[$receipt_count]['receipt_id'] = get_the_ID();
				$this->receipt_array[$receipt_count]['receipt_date'] = get_the_date();
				$this->receipt_array[$receipt_count]['receipt_hour'] = get_the_time();
				$this->receipt_array[$receipt_count]['receipt_client'] = get_post_meta(get_the_ID(), 'receipt_client', true);
				$this->receipt_array[$receipt_count]['receipt_product'] = get_post_meta(get_the_ID(), 'receipt_product', true);
				$this->receipt_array[$receipt_count]['receipt_price'] = get_post_meta(get_the_ID(), 'receipt_price', true);
				$this->receipt_array[$receipt_count]['receipt_status'] = $receipt_status_text;
				$this->receipt_array[$receipt_count]['receipt_error_status'] = '';
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
				$user_data = get_user_by('ID', $user_display_name);

				$this->receipt_customers[$receipt_count]['receipt_client'] = get_post_meta(get_the_ID(), 'receipt_client', true);
				$this->receipt_customers[$receipt_count]['receipt_client_id'] = $user_data->ID;
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
				$this->receipt_params_array[$receipt_count]['receipt_id'] = get_the_ID();
				$this->receipt_params_array[$receipt_count]['receipt_date'] = get_the_date();
				$this->receipt_params_array[$receipt_count]['receipt_hour'] = get_the_time();
				$this->receipt_params_array[$receipt_count]['receipt_client'] = get_post_meta(get_the_ID(), 'receipt_client', true);
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
