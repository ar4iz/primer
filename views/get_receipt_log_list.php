<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }

class PrimerReceiptLogList {
	public $receipt_log_array = array();
	public $receipt_log_params_array = array();
	public $receipt_log_customers = array();

	public function get() {
		$receipt_log_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt_log',
		);

		$receipt_log_query = new WP_Query( $receipt_log_args );
		$receipt_log_count = 0;

		if ($receipt_log_query->have_posts()):
			while ($receipt_log_query->have_posts()):
				$receipt_log_query->the_post();

				$receipt_log_status_text = '';
				$receipt_log_status = get_post_meta(get_the_ID(), 'receipt_log_status', true);
				switch ($receipt_log_status) {
					case 'issued':
						$receipt_log_status_text = 'Yes';
						break;
					case 'not_issued':
						$receipt_log_status_text = 'No';
						break;
				}

				$receipt_log_email_status_text = '';
				$receipt_log_email_status = get_post_meta(get_the_ID(), 'receipt_log_email', true);
				switch ($receipt_log_email_status) {
					case 'sent':
						$receipt_log_email_status_text = 'Yes';
						break;
					case 'not_sent':
						$receipt_log_email_status_text = 'No';
						break;
				}

				$receipt_log_email_error = get_post_meta(get_the_ID(), 'receipt_log_email_error', true);

				$receipt_log_error = get_post_meta(get_the_ID(), 'receipt_log_error', true);

				$this->receipt_log_array[$receipt_log_count]['receipt_log_order_id'] = get_post_meta(get_the_ID(), 'receipt_log_order_id', true);
				$this->receipt_log_array[$receipt_log_count]['receipt_log_order_date'] = get_post_meta(get_the_ID(), 'receipt_log_order_date', true);
				$this->receipt_log_array[$receipt_log_count]['receipt_log_client'] = get_post_meta(get_the_ID(), 'receipt_log_client', true);
				$this->receipt_log_array[$receipt_log_count]['receipt_log_status'] = $receipt_log_status_text;
				$this->receipt_log_array[$receipt_log_count]['receipt_log_email'] = $receipt_log_email_status_text;
				$this->receipt_log_array[$receipt_log_count]['receipt_log_error'] = $receipt_log_error;
				$this->receipt_log_array[$receipt_log_count]['receipt_log_email_error'] = $receipt_log_email_error;
				$receipt_log_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_log_array;
	}

	public function get_with_params($receipt_log_error, $receipt_log_issue) {

		$meta_values = array();

		$receipt_log_args = array(
			'posts_per_page' => -1,
			'post_type' => 'primer_receipt_log',
		);

		if (!empty($receipt_log_issue)) {
			$meta_values['receipt_log_total_status'][] = 'only_issued';
		}
		if (!empty($receipt_log_error)) {
			$meta_values['receipt_log_total_status'][] = 'only_errors';
		}

		if (!empty($meta_values)) {
			$receipt_log_args['meta_query']['relation'] = 'AND';
			$i = 0;
			foreach ( $meta_values as $key => $meta_value ) {
				$i++;
				$receipt_log_args['meta_query'][$i]['key'] = $key;
				$receipt_log_args['meta_query'][$i]['value'] = $meta_value;
			}
		}

		$receipt_log_query = new WP_Query( $receipt_log_args );
		$receipt_count = 0;

		if ($receipt_log_query->have_posts()):
			while ($receipt_log_query->have_posts()):
				$receipt_log_query->the_post();

				$receipt_log_status_text = '';
				$receipt_log_status = get_post_meta(get_the_ID(), 'receipt_log_status', true);
				switch ($receipt_log_status) {
					case 'issued':
						$receipt_log_status_text = 'Yes';
						break;
					case 'not_issued':
						$receipt_log_status_text = 'No';
						break;
				}

				$receipt_log_email_status_text = '';
				$receipt_log_email_status = get_post_meta(get_the_ID(), 'receipt_log_email', true);
				switch ($receipt_log_email_status) {
					case 'sent':
						$receipt_log_email_status_text = 'Yes';
						break;
					case 'not_sent':
						$receipt_log_email_status_text = 'No';
						break;
				}

				$receipt_log_email_error = get_post_meta(get_the_ID(), 'receipt_log_email_error', true);

				$this->receipt_log_params_array[$receipt_count]['receipt_log_order_id'] = get_post_meta(get_the_ID(), 'receipt_log_order_id', true);
				$this->receipt_log_params_array[$receipt_count]['receipt_log_order_date'] = get_post_meta(get_the_ID(), 'receipt_log_order_date', true);
				$this->receipt_log_params_array[$receipt_count]['receipt_log_client'] = get_post_meta(get_the_ID(), 'receipt_log_client', true);
				$this->receipt_log_params_array[$receipt_count]['receipt_log_status'] = $receipt_log_status_text;
				$this->receipt_log_params_array[$receipt_count]['receipt_log_email'] = $receipt_log_email_status_text;
				$this->receipt_log_params_array[$receipt_count]['receipt_log_error'] = '';
				$this->receipt_log_params_array[$receipt_count]['receipt_log_email_error'] = $receipt_log_email_error;
				$receipt_count++;
			endwhile;
		endif;
		wp_reset_postdata();

		return $this->receipt_log_params_array;
	}
}
