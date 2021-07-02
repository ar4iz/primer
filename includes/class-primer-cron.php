<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// reference the Dompdf namespace
use Dompdf\Dompdf;

class PrimerCron {
	public function __construct() {
		add_action( 'primer_cron_save_settings', array( $this, 'primer_save_automation_data' ));
		add_action('primer_cron_process', array( $this, 'convert_order_to_invoice' ));
		add_action('wp_ajax_primer_fire_cron', array( $this, 'ajax_fire_cron' ));
	}

	public function primer_save_automation_data() {
		$next_timestamp = wp_next_scheduled( 'primer_cron_process' );
		$automation_options = get_option('primer_automation');

		$activation_automation = $automation_options['activation_automation'];
		$automation_duration = $automation_options['automation_duration'];

		$current_schedule = wp_get_schedule('primer_cron_process');

		if ($current_schedule !== $automation_duration) {
			wp_unschedule_event( $next_timestamp, 'primer_cron_process');
			wp_schedule_event( time(), $automation_duration, 'primer_cron_process' );
		} elseif (!$next_timestamp) {
			wp_schedule_event( time(), $automation_duration, 'primer_cron_process' );
		}

		if (!empty($automation_options) && !empty($activation_automation)) {
			wp_unschedule_event( $next_timestamp, 'primer_cron_process');
			wp_schedule_event( time(), $automation_duration, 'primer_cron_process' );
		} else {
			wp_clear_scheduled_hook( 'primer_cron_process' );
			wp_unschedule_event( $next_timestamp, 'primer_cron_process');
		}
	}

	/**
	 * Primer Automation Settings conversation
	 */
	public function convert_order_to_invoice() {
		global $wpdb, $woocommerce;

		$log_ids = array();

		$LOG = '';

		$LOG .="cron started "."\n";

		$receipt_log_automation_value = '';

		$emails = array();

		$send_to_admin = '';

		$automation_duration = '';

		// Get Notification Emails
		$automation_options = get_option('primer_automation');

		$activation_automation = $automation_options['activation_automation'];

		if (!empty($automation_options) && !empty($activation_automation)) {

			$primer_conditions = $automation_options['primer_conditions'];

			$primer_start_order_date = $automation_options['calendar_date_timestamp'];

			if (!empty($primer_conditions)) {

				$automation_duration = $automation_options['automation_duration'];

				$condition_order_status = '';
				foreach ( $primer_conditions as $primer_condition ) {
					$condition_order_status = $primer_condition['receipt_order_states'];
					$condition_client_email_send = $primer_condition['client_email_send'];
					$order_args           = array(
						'return'      => 'ids',
						'limit'       => 9999,
						'order'       => 'DESC',
						'numberposts' => - 1,
					);

					$order_args['status'] = $condition_order_status;

					if (!empty($primer_start_order_date)) {
						$order_args['date_created'] = '>' . $primer_start_order_date;
					}

					$orders = wc_get_orders( $order_args );

					$LOG.="orders " . print_r($orders,1)."\n";

					foreach ( $orders as $order_id ) {
						$order = wc_get_order( $order_id );
                        if ( is_a( $order, 'WC_Order_Refund' ) ) {
                            $order = wc_get_order( $order->get_parent_id() );
                        }

						$id_of_order = $order->get_id();

						$issued_order = get_post_meta($id_of_order, 'receipt_status', true);

						if ($issued_order != 'issued') {

							$LOG.="issued_order $id_of_order " . print_r($issued_order,1)."\n";

							$order_country     = $order->get_billing_country();
//							$order_country = get_post_meta($id_of_order, '_billing_country', true);

							$order_create_date = date( 'F j, Y', $order->get_date_created()->getOffsetTimestamp() );
							$order_paid_date   = null;
							$order_paid_hour   = null;
							/*if ( ! empty( $order->get_date_paid() ) ) {
								$order_paid_date = date( 'F j, Y', $order->get_date_paid()->getTimestamp() );
								$order_paid_hour = date( 'H:i:s', $order->get_date_paid()->getTimestamp() );
							} else {
								$order_paid_date = date( 'F j, Y', $order->get_date_created()->getTimestamp() );
								$order_paid_hour = date( 'H:i:s', $order->get_date_created()->getTimestamp() );
							}*/
							$order_paid_date = date( 'F j, Y', $order->get_date_created()->getTimestamp() );
							$order_paid_hour = date( 'H:i:s', $order->get_date_created()->getTimestamp() );

							$order_total_price = $order->get_total();
							$user_id           = $order->get_user_id();
							$user              = $order->get_user();

							$user_first_name = $order->get_billing_first_name();
							$user_last_name  = $order->get_billing_last_name();

							$user_full_name = $user_first_name . ' ' . $user_last_name;

							$tax = $order->get_total_tax();

							$order_invoice_type = get_post_meta( $id_of_order, '_billing_invoice_type', true );

							$insert_taxonomy = 'receipt_status';
							$invoice_term    = '';

							if ( $order_invoice_type == 'receipt' && $order_country == 'GR' ) {
								$invoice_term = 'greek_receipt';
							}
							if ( $order_invoice_type == 'receipt' && $order_country !== 'GR' ) {
								$invoice_term = 'english_receipt';
							}
							if ( $order_invoice_type == 'invoice' && $order_country == 'GR' ) {
								$invoice_term = 'greek_invoice';
							}
							if ( $order_invoice_type == 'invoice' && $order_country !== 'GR' ) {
								$invoice_term = 'english_invoice';
							}

							if (empty($order_invoice_type) && $order_country !== 'GR' ) {
								$invoice_term = 'english_invoice';
							}
							if (empty($order_invoice_type) && $order_country == 'GR' ) {
								$invoice_term = 'greek_invoice';
							}

							$user_data = $user_full_name ? $user_full_name : '';

							$user_order_email = $order->get_billing_email();

							$currency        = $order->get_currency();
							$currency_symbol = get_woocommerce_currency_symbol( $currency );
							$payment_method  = $order->get_payment_method();
							$payment_title   = $order->get_payment_method_title();
							$order_status    = $order->get_status();

							if ( $currency == 'EUR' ) {
								if ( $tax != '0' ) {

									if ( get_page_by_title( 'Receipt for order #' . $id_of_order, OBJECT, 'primer_receipt' ) == null ) {
										$post_id = wp_insert_post( array(
											'post_type'      => 'primer_receipt',
											'post_title'     => 'Receipt for order #' . $id_of_order,
											'comment_status' => 'closed',
											'ping_status'    => 'closed',
											'post_status'    => 'publish',
										) );

										wp_set_object_terms( $post_id, $invoice_term, $insert_taxonomy, false );

										if ( $post_id ) {
											$post_issued = 'issued';
											if ( empty( $user_data ) ) {
												$post_issued                  = 'not_issued';
												$receipt_log_automation_value .= __( 'Order Client name is required!', 'primer' );
											}

											update_post_meta( $post_id, 'receipt_status', $post_issued );
											update_post_meta( $post_id, 'order_id_to_receipt', $id_of_order );
											update_post_meta( $id_of_order, 'receipt_status', $post_issued );
											add_post_meta( $post_id, 'receipt_client', $user_data );
											add_post_meta( $post_id, 'receipt_client_id', $user_id );
											add_post_meta( $post_id, 'receipt_price', $order_total_price . ' ' . $currency_symbol );
											foreach ( $order->get_items() as $item_id => $item_data ) {
												$product_name = $item_data->get_name();
												add_post_meta( $post_id, 'receipt_product', $product_name );
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

											$user_email = $user ? $user_order_email : $user->user_email;

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

											if (get_page_by_title( 'Receipt automation report for #' . $id_of_order, OBJECT, 'pr_log_automation' ) == null) {
												$receipt_log_automation_id = wp_insert_post(array(
													'post_type' => 'pr_log_automation',
													'post_title' => 'Receipt automation report for #' . $id_of_order,
													'comment_status' => 'closed',
													'ping_status' => 'closed',
													'post_status' => 'publish',
												));
											} else {
												$find_exist_post = get_page_by_title('Receipt automation report for #' . $id_of_order, OBJECT, 'pr_log_automation');
												$receipt_log_automation_id = $find_exist_post->ID;
											}

											if ($receipt_log_automation_id) {
												$invoice_date = get_the_date('F j, Y', $post_id);

												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_order_id', $id_of_order);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_order_date', $order_paid_date);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_invoice_id', $post_id);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_invoice_date', $invoice_date);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_client', $user_data);
												$get_issue_status = get_post_meta($post_id, 'receipt_status', true);
												if(empty($get_issue_status)) {
													$get_issue_status = 'issued';
												}

												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_status', $get_issue_status);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_error', $receipt_log_automation_value);

												$log_ids[] = $receipt_log_automation_id;
											}


											$mailResult = false;
											$primer_smtp = PrimerSMTP::get_instance();

											if ( !empty($condition_client_email_send) && $condition_client_email_send == 'on' ) {

												$mailResultSMTP = $primer_smtp->primer_mail_sender($user_order_email, $primer_smtp_subject, $primer_smtp_message, $attachments);

												if (!empty($mailResultSMTP['error'])) {
													update_post_meta($receipt_log_automation_id, 'receipt_log_automation_email', 'not_sent');
													update_post_meta($receipt_log_automation_id, 'receipt_log_automation_email_error', $GLOBALS['phpmailer']->ErrorInfo);
													update_post_meta($receipt_log_automation_id, 'receipt_log_automation_total_status', 'only_errors');
												} else {
													update_post_meta($receipt_log_automation_id, 'receipt_log_automation_email', 'sent');
													update_post_meta($receipt_log_automation_id, 'receipt_log_automation_total_status', 'only_issued');
												}

												update_post_meta($post_id, 'exist_error_log', 'exist_log');

											} else {
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_email', 'not_sent');
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_email_error', '');
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_total_status', 'only_issued');
											}
										}
									} else {
										$find_exist_post = get_page_by_title('Receipt for order #' . $id_of_order, OBJECT, 'primer_receipt');
										$post_id = $find_exist_post->ID;
										if ( $post_id ) {
											$post_issued = 'issued';
											if ( empty( $user_data ) ) {
												$post_issued                  = 'not_issued';
												$receipt_log_automation_value .= __( 'Order Client name is required!', 'primer' );
											}

											update_post_meta( $post_id, 'receipt_status', $post_issued );
											update_post_meta( $post_id, 'order_id_to_receipt', $id_of_order );
											update_post_meta( $id_of_order, 'receipt_status', $post_issued );
											add_post_meta( $post_id, 'receipt_client', $user_data );
											add_post_meta( $post_id, 'receipt_client_id', $user_id );
											add_post_meta( $post_id, 'receipt_price', $order_total_price . ' ' . $currency_symbol );
											foreach ( $order->get_items() as $item_id => $item_data ) {
												$product_name = $item_data->get_name();
												add_post_meta( $post_id, 'receipt_product', $product_name );
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

											$user_email = $user ? $user_order_email : $user->user_email;

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

											$receipt_log_automation_id = wp_insert_post(array(
												'post_type' => 'pr_log_automation',
												'post_title' => 'Receipt automation report for #' . $id_of_order,
												'comment_status' => 'closed',
												'ping_status' => 'closed',
												'post_status' => 'publish',
											));

											if ($receipt_log_automation_id) {
												$invoice_date = get_the_date('F j, Y', $post_id);

												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_order_id', $id_of_order);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_order_date', $order_paid_date);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_invoice_id', $post_id);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_invoice_date', $invoice_date);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_client', $user_data);
												$get_issue_status = get_post_meta($post_id, 'receipt_status', true);
												if(empty($get_issue_status)) {
													$get_issue_status = 'issued';
												}

												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_status', $get_issue_status);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_error', $receipt_log_automation_value);
											}


											$mailResult = false;
											$primer_smtp = PrimerSMTP::get_instance();

											if ( !empty($condition_client_email_send) && $condition_client_email_send == 'on' ) {

												$mailResultSMTP = $primer_smtp->primer_mail_sender($user_order_email, $primer_smtp_subject, $primer_smtp_message, $attachments);

												if (!empty($mailResultSMTP['error'])) {
													update_post_meta($receipt_log_automation_id, 'receipt_log_automation_email', 'not_sent');
													update_post_meta($receipt_log_automation_id, 'receipt_log_automation_email_error', $GLOBALS['phpmailer']->ErrorInfo);
													update_post_meta($receipt_log_automation_id, 'receipt_log_automation_total_status', 'only_errors');
												} else {
													update_post_meta($receipt_log_automation_id, 'receipt_log_automation_email', 'sent');
													update_post_meta($receipt_log_automation_id, 'receipt_log_automation_total_status', 'only_issued');
												}

												update_post_meta($post_id, 'exist_error_log', 'exist_log');

											}
										}
									}
								} else {
									if ( get_page_by_title( 'Receipt for order #' . $id_of_order, OBJECT, 'primer_receipt' ) == null ) {
										$post_id = wp_insert_post( array(
											'post_type'      => 'primer_receipt',
											'post_title'     => 'Receipt for order #' . $id_of_order,
											'comment_status' => 'closed',
											'ping_status'    => 'closed',
											'post_status'    => 'publish',
										) );

										wp_set_object_terms( $post_id, $invoice_term, $insert_taxonomy, false );

										if ( $post_id ) {
											$post_issued                  = 'not_issued';
											update_post_meta( $post_id, 'receipt_status', $post_issued );
											update_post_meta( $post_id, 'order_id_to_receipt', $id_of_order );
											update_post_meta( $id_of_order, 'receipt_status', $post_issued );
											add_post_meta( $post_id, 'receipt_client', $user_data );
											add_post_meta( $post_id, 'receipt_client_id', $user_id );
											add_post_meta( $post_id, 'receipt_price', $order_total_price . ' ' . $currency_symbol );
											foreach ( $order->get_items() as $item_id => $item_data ) {
												$product_name = $item_data->get_name();
												add_post_meta( $post_id, 'receipt_product', $product_name );
											}

											if (get_page_by_title( 'Receipt automation report for #' . $id_of_order, OBJECT, 'pr_log_automation' ) == null) {
												$receipt_log_automation_id = wp_insert_post(array(
													'post_type' => 'pr_log_automation',
													'post_title' => 'Receipt automation report for #' . $id_of_order,
													'comment_status' => 'closed',
													'ping_status' => 'closed',
													'post_status' => 'publish',
												));
											} else {
												$find_exist_post = get_page_by_title('Receipt automation report for #' . $id_of_order, OBJECT, 'pr_log_automation');
												$receipt_log_automation_id = $find_exist_post->ID;
											}

											if ($receipt_log_automation_id) {
												$invoice_date = get_the_date('F j, Y', $post_id);

												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_order_id', $id_of_order);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_order_date', $order_paid_date);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_invoice_id', $post_id);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_invoice_date', $invoice_date);
												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_client', $user_data);
												$get_issue_status = get_post_meta($post_id, 'receipt_status', true);
												if(empty($get_issue_status)) {
													$get_issue_status = 'issued';
												}

												update_post_meta($receipt_log_automation_id, 'receipt_log_automation_status', $get_issue_status);
												$log_ids[] = $receipt_log_automation_id;
											}

											update_post_meta($receipt_log_automation_id, 'receipt_log_automation_email', 'not_sent');
											update_post_meta($receipt_log_automation_id, 'receipt_log_automation_email_error', 'Receipt not issued');
											update_post_meta($receipt_log_automation_id, 'receipt_log_automation_total_status', 'only_errors');

											$get_issue_status = get_post_meta($post_id, 'receipt_status', true);
											if(empty($get_issue_status)) {
												$get_issue_status = 'issued';
											}

											$response_log_automation = __('VAT% is required.', 'primer');

											update_post_meta($receipt_log_automation_id, 'receipt_log_automation_status', $get_issue_status);
											update_post_meta($receipt_log_automation_id, 'receipt_log_automation_error', $response_log_automation);

											update_post_meta($post_id, 'exist_error_log', 'exist_log');
										}
									}
								}
							}

						}
					}
				}
			}

			$primer_send_success_log = '';
			$primer_send_fail_log = '';
			if (!empty($automation_options['send_successful_log'])) {
				$primer_send_success_log = $automation_options['send_successful_log'];
			}
			if (!empty($automation_options['send_failed_log'])) {
				$primer_send_fail_log = $automation_options['send_failed_log'];
			}

            $LOG.="primer_send_success_log " . print_r($primer_send_success_log,1)."\n";
            $LOG.="primer_send_fail_log " . print_r($primer_send_fail_log,1)."\n";
            $LOG.="log_ids  " . print_r($log_ids,1)."\n";

			$this->export_csv_log($log_ids, $primer_send_success_log, $primer_send_fail_log);

		}

		$f=fopen(__DIR__ . '/res.txt','w+');
		fputs($f,print_r($LOG,1)."\n");
		fclose($f);

	}


	/**
	 * Set the headers for the CSV file
	 *
	 * @since 	2.0.0
	 */
	public function set_csv_headers( $filename ) {

		/*
		 * Disables caching
		 */
		$now = date("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");

		/*
		 * Forces the download
		 */
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");

		/*
		 * disposition / encoding on response body
		 */
		header("Content-Disposition: attachment;filename={$filename}");
		header("Content-Transfer-Encoding: binary");
	}

	public function export_csv_log($log_ids, $log_successful, $log_failed) {
		global $wpdb;

		$post_type = 'pr_log_automation';
		$type = 'automation_log';

		$meta_values = array();

		$header_row = array();
		$data_rows = array();

		// Query the posts
		$args 	= array (
			'post_type'     => $post_type,
			'posts_per_page'=> -1,
			'post_status'   => 'publish',
		);

		if(!empty($log_ids)) {
			$args['post__in'] = $log_ids;
		}

		if (!empty($log_successful)) {
			$meta_values['receipt_log_automation_total_status'][] = 'only_issued';
		}
		if (!empty($log_failed)) {
			$meta_values['receipt_log_automation_total_status'][] = 'only_errors';
		}

		if (!empty($meta_values)) {
			$args['meta_query']['relation'] = 'AND';
			$i = 0;
			foreach ( $meta_values as $key => $meta_value ) {
				$i++;
				$args['meta_query'][$i]['key'] = $key;
				$args['meta_query'][$i]['value'] = $meta_value;
			}
		}

		$the_query = new WP_Query( apply_filters('primer_export_csv_query', $args) );

		if ( $the_query->have_posts() ) :
			// post meta header row
			$postmeta_headers = array();
			while ( $the_query->have_posts() ) : $the_query->the_post();
				$id = get_the_ID();
				$post_metas = get_post_meta($id, '', true);
			endwhile;

			// header row
			$header_row = array(
				0  => __( 'Order No', 'primer' ),
				1  => __( 'Order Date', 'primer' ),
				2  => __( 'Invoice No', 'primer' ),
				3  => __( 'Invoice Date', 'primer' ),
				4  => __( 'Client', 'primer' ),
				5  => __( 'Issued receipt', 'primer' ),
				6  => __( 'Email send', 'primer' ),
				7  => __( 'Receipt Error', 'primer' ),
				8  => __( 'Email Error', 'primer' ),
			);

			$columns = count( $header_row );

			// reset to start populating data
			rewind_posts();

			while ( $the_query->have_posts() ) : $the_query->the_post();

				$order_id = get_post_meta(get_the_ID(), 'receipt_log_automation_order_id', true);
				$order_date = get_post_meta(get_the_ID(), 'receipt_log_automation_order_date', true);

				$log_order_id = get_post_meta(get_the_ID(), 'receipt_log_automation_order_id', true);
				$invoice_log_id = get_post_meta(get_the_ID(), 'receipt_log_automation_invoice_id', true);
				$invoice_log_date = get_post_meta(get_the_ID(), 'receipt_log_automation_invoice_date', true);
				$meta_key = 'order_id_to_receipt';
				$get_invoice_by_id = $wpdb->get_col(
					$wpdb->prepare(
						"
					SELECT key1.post_id
					FROM $wpdb->postmeta key1
					WHERE key1.meta_key = %s AND key1.meta_value = '$log_order_id'", $meta_key ) );

				if (empty($invoice_log_id)) {
					if (!empty($get_invoice_by_id)) {
						$invoice_log_id = $get_invoice_by_id[0];
					}
				}
				if (empty($invoice_log_date)) {
					if (!empty($get_invoice_by_id)) {
						$invoice_log_date = get_the_date('F j, Y', $get_invoice_by_id[0]);
					}
				}
				$order_from_invoice_log = get_post_meta(get_the_ID(), 'receipt_log_automation_order_id', true);
				$invoice_log_client = get_post_meta(get_the_ID(), 'receipt_log_automation_client', true);
				$total_order = wc_get_order( $order_from_invoice_log );
                if ( is_a( $total_order, 'WC_Order_Refund' ) ) {
                    $total_order = wc_get_order( $total_order->get_parent_id() );
                }

				$user_first_name = $total_order->get_billing_first_name();
				$user_last_name = $total_order->get_billing_last_name();

				$user_full_name = $user_first_name . ' ' . $user_last_name;

				if (empty($invoice_log_client)) {
					$invoice_log_client = $user_full_name;
				}

				$receipt_log_status_text = '';
				$receipt_log_status = get_post_meta(get_the_ID(), 'receipt_log_automation_status', true);
				switch ($receipt_log_status) {
					case 'issued':
						$receipt_log_status_text = 'Yes';
						break;
					case 'not_issued':
						$receipt_log_status_text = 'No';
						break;
				}

				$receipt_log_email_status_text = '';
				$receipt_log_email_status = get_post_meta(get_the_ID(), 'receipt_log_automation_email', true);
				switch ($receipt_log_email_status) {
					case 'sent':
						$receipt_log_email_status_text = 'Yes';
						break;
					case 'not_sent':
						$receipt_log_email_status_text = 'No';
						break;
				}

				$receipt_log_error = get_post_meta(get_the_ID(), 'receipt_log_automation_error', true);

				$receipt_log_email_error = get_post_meta(get_the_ID(), 'receipt_log_automation_email_error', true);

				// initialize row with empty cells
				$row = array();

				// Put each posts data into the appropriate cell
				$row[0]  = $order_id;
				$row[1]  = $order_date;
				$row[2]  = $invoice_log_id;
				$row[3]  = $invoice_log_date;
				$row[4]  = $invoice_log_client;
				$row[5]  = $receipt_log_status_text;
				$row[6]  = $receipt_log_email_status_text;
				$row[7]  = $receipt_log_error;
				$row[8]  = $receipt_log_email_error;

				$row = apply_filters( 'primer_export_csv_row', $row, get_the_ID(), $header_row );
				$data_rows[] = $row;

			endwhile;

		endif;

		$header_row = apply_filters( 'primer_export_csv_headers', $header_row );
		$data_rows = apply_filters( 'primer_export_csv_data', $data_rows );

		// Create the filename
		$filename = sanitize_file_name( $type . '-export-' . date( 'Y-m-d H-i' ) . '.csv' );

		$this->set_csv_headers( $filename );

		$upload_dir = wp_upload_dir()['basedir'];
		if (!file_exists($upload_dir . '/primer-automation-logs')) {
			mkdir($upload_dir . '/primer-automation-logs');
		}
		$csv_dir_file = $upload_dir . '/primer-automation-logs/'.$filename;

		$fh = @fopen( "$csv_dir_file", 'w' );
		fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
		fputcsv( $fh, $header_row );

		foreach ( $data_rows as $data_row ) {
			fputcsv( $fh, $data_row );
		}

		fclose( $fh );

		// Get Notification Emails
		$automation_options = get_option('primer_automation');
		$activation_automation = $automation_options['activation_automation'];

		$primer_smtp = PrimerSMTP::get_instance();

		if (!empty($automation_options['email_subject'])) {
			$primer_smtp_subject = $automation_options['email_subject'];
		} else {
			$primer_smtp_subject = __('Test email subject', 'primer');
		}

		$primer_send_success_log = '';
		$primer_send_fail_log = '';
		if (!empty($automation_options['send_successful_log'])) {
			$primer_send_success_log = $automation_options['send_successful_log'];
		}
		if (!empty($automation_options['send_failed_log'])) {
			$primer_send_fail_log = $automation_options['send_failed_log'];
		}

		if (!empty($activation_automation)) {
			if (!empty($primer_send_success_log) || !empty($primer_send_fail_log)) {
				if (($primer_send_success_log == 'on') || ($primer_send_fail_log == 'on')) {
					$automation_admin_emails = $automation_options['admin_email'];
					if (!empty($automation_admin_emails)) {
						$admin_emails = explode(',', $automation_admin_emails);
						foreach ( $admin_emails as $admin_email ) {
							$emails[] = trim( sanitize_email($admin_email) );
						}
						if (!empty($emails) && !empty($log_ids)) {
							foreach ( $emails as $to_admin_email ) {
								$mailResultSMTP = $primer_smtp->primer_mail_sender($to_admin_email, $primer_smtp_subject, 'automation log', $csv_dir_file);
							}
						}
					}

				}
			}
		}

		die();
	}

	function ajax_fire_cron() {
		echo "OK";
		$this->convert_order_to_invoice();
		wp_die();
	}
}

new PrimerCron();
