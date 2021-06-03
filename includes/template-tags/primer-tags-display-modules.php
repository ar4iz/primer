<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }

function primer_display_issuer_container() {
	$issuer_container = '';
	$receipt_id = get_the_ID();
	$issuer_name = get_post_meta($receipt_id, 'receipt_client', true);

	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$customer = new WC_Customer( $issuer_client_id );

	$billing_first_name = $customer->get_billing_first_name();
	$billing_last_name = $customer->get_billing_last_name();

	$customer_full_name = $billing_first_name . ' ' . $billing_last_name;
	if (empty($customer_full_name)) {
		$customer_full_name = $issuer_name;
	}

	$billing_address_1  = $customer->get_billing_address_1();
	$billing_address_2  = $customer->get_billing_address_2();

	$issuer_container .= '<span class="issuer_name skin">'.__('ISSUER\'S COMPANY NAME').'</span>';

	$issuer_container .= '<p> <span class="issuer_subjectField skin">'.__('COMPANY ACTIVITY').'</span></p>';

	$issuer_container .= '<p><span class="issuer_address skin">ADDRESS</span></p>';
	$issuer_container .= '<p><span class="issuer_address skin">ΑΦΜ: 800434990</span></p>';
	$issuer_container .= '<p><span class="issuer_address skin">ΔΟΥ: NEAS IONIAS </span></p>';

//	$issuer_container .= '<p> <span class="skin">ΑΦΜ: </span><span class="issuer_vat skin">{ISSUER_VAT}</span> <span class="skin">ΔΟΥ: </span> <span class="issuer_doy skin">{ISSUER_DOY}</span></p>';

//	$issuer_container .= '<p class="gemh_issuer_p skin"> <span class="skin">ΑΡ.ΓΕΜΗ: </span> <span class="issuer_gemh">{ISSUER_GEMH}</span></p>';

	echo $issuer_container;
}

function primer_main_info_table_head() {
	$issuer_main_info_table_head = '';
	$receipt_id = get_the_ID();
	$issuer_name = get_post_meta($receipt_id, 'receipt_client', true);

	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$customer = new WC_Customer( $issuer_client_id );
	$customer_country = $customer->get_country();

	$issuer_main_info_table_head = '<tr class="heading">';


	if ($customer_country == 'GR') {
		$issuer_main_info_table_head .= '<td><p>ΕΙΔΟΣ ΠΑΡΑΣΤΑΤΙΚΟΥ</p></td>';
		$issuer_main_info_table_head .= '<td><p>ΑΡΙΘΜΟΣ</p></td>';
		$issuer_main_info_table_head .= '<td><p>ΗΜΕΡ/ΝΙΑ</p></td>';
		$issuer_main_info_table_head .= '<td><p>ΩΡΑ</p></td>';
	} else {
		$issuer_main_info_table_head .= '<td><p>INVOICE TYPE</p></td>';
		$issuer_main_info_table_head .= '<td><p>INVOICE NUMBER</p></td>';
		$issuer_main_info_table_head .= '<td><p>DATE</p></td>';
		$issuer_main_info_table_head .= '<td><p>TIME</p></td>';
	}

	$issuer_main_info_table_head .= '</tr>';

	echo $issuer_main_info_table_head;
}

function primer_display_issuer_product_head() {
	$issuer_product_head = '';

	$receipt_id = get_the_ID();
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$customer = new WC_Customer( $issuer_client_id );
	$customer_country = $customer->get_country();

	$issuer_product_head = '<tr class="heading">';
	if ($customer_country == 'GR') {
		$issuer_product_head .= '<td class="code_head_td"><p> ΚΩΔΙΚΟΣ</p></td>';
		$issuer_product_head .= '<td class="description_head_td"><p> ΠΕΡΙΓΡΑΦΗ</p></td>';
		$issuer_product_head .= '<td class="quantity_head_td"><p> ΠΟΣΟΤΗΤΑ</p></td>';
		$issuer_product_head .= '<td class="mu_head_td"><p> Μ.Μ</p></td>';
		$issuer_product_head .= '<td class="up_head_td"><p> ΤΙΜΗ ΜΟΝΑΔΑΣ</p></td>';
		$issuer_product_head .= '<td class="disc_head_td"><p> ΕΚΠΤΩΣΗ</p></td>';
		$issuer_product_head .= '<td class="vat_head_td"><p> ΦΠΑ %</p></td>';
		$issuer_product_head .= '<td class="pricenovat_head_td"><p> ΤΙΜΗ ΠΡΟ ΦΠΑ</p></td>';
		$issuer_product_head .= '<td class="price_head_td"><p> ΤΕΛΙΚΗ ΑΞΙΑ</p></td>';
	} else {
		$issuer_product_head .= '<td class="code_head_td"><p> PRODUCT ID</p></td>';
		$issuer_product_head .= '<td class="description_head_td"><p> DESCRIPTION</p></td>';
		$issuer_product_head .= '<td class="quantity_head_td"><p> PIECES</p></td>';
		$issuer_product_head .= '<td class="mu_head_td"><p> UNIT</p></td>';
		$issuer_product_head .= '<td class="up_head_td"><p> PRICE PER UNIT</p></td>';
		$issuer_product_head .= '<td class="disc_head_td"><p> SALE</p></td>';
		$issuer_product_head .= '<td class="vat_head_td"><p> VAT %</p></td>';
		$issuer_product_head .= '<td class="pricenovat_head_td"><p> PRICE BEFORE TAXES</p></td>';
		$issuer_product_head .= '<td class="price_head_td"><p> TOTAL AMOUNT</p></td>';
	}
	$issuer_product_head .= '</tr>';

	echo $issuer_product_head;
}

function primer_display_issuer_product() {

	$issuer_product = '';

	$receipt_id = get_the_ID();
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);

	$order = wc_get_order( $order_id );

	$discount = $order->get_discount_total();
	$total_tax = $order->get_total_tax();


	foreach ( $order->get_items() as $item_id => $item ) {
		$issuer_product .= '<tr class="products">';
		$product_id = $item->get_product_id();
		$product_instance = wc_get_product($product_id);

		$issuer_product .= '<td><span class="item_code">'.$product_id.'</span></td>';

		$product_name = $product_instance->get_name();

		$product_sale = $product_instance->get_total_sales();

		$sale_price = $product_instance->get_sale_price();
		if (empty($sale_price)) {
			$sale_price = '0';
		}

		$issuer_product .= '<td><span class="item_name">'.$product_name.'</span></td>';

		$product_full_description = $product_instance->get_description();
		$product_short_description = $product_instance->get_short_description();

		$quantity = $item->get_quantity();

		$issuer_product .= '<td><span class="item_quantity">'.$quantity.'</span></td>';

		$measure_unit = 'PIECES';

		$issuer_product .= '<td><span class="item_mu">'.$measure_unit.'</span></td>';

		$regular_price = $product_instance->get_regular_price();

		$issuer_product .= '<td><span class="item_unit_price">'.$regular_price.'</span></td>';

		$issuer_product .= '<td><span class="item_discount">'.$sale_price.'</span></td>';

		$price_excl_tax = wc_get_price_excluding_tax( $product_instance ); // price without VAT
		$price_incl_tax = wc_get_price_including_tax( $product_instance );  // price with VAT

		$subtotal_order_payment = $item->get_subtotal();

		$subtotal_item_tax = $item->get_subtotal_tax();

		if ($price_excl_tax == $price_incl_tax) {
			$percent = (($subtotal_item_tax / $quantity) / $price_excl_tax ) * 100;
		} else {
			$percent = (($subtotal_item_tax / $quantity) / $price_excl_tax ) * 100;
		}

//		$percent = (($total_tax / $quantity) / $price_excl_tax) * 100;

		$total_order_payment = $item->get_total();

		$total_order_item = $total_order_payment + $subtotal_item_tax;


		$issuer_product .= '<td><span class="item_vat">'.$percent.'</span></td>';

		$issuer_product .= '<td><span class="item_price_novat">'.$subtotal_order_payment.'</span></td>';

		$issuer_product .= '<td><span class="item_price_novat">'.$total_order_item.'</span></td>';

		$issuer_product .= '</tr>';
	}

	echo $issuer_product;
}

function primer_display_issuer_comments() {

	$issuer_comment = '';

	$receipt_id = get_the_ID();
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);

	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$customer = new WC_Customer( $issuer_client_id );
	$customer_country = $customer->get_country();

	$order = wc_get_order( $order_id );

	$order_comment = $order->get_customer_note();
	$order_comment_all = $order->get_customer_order_notes();

	if ($customer_country == 'GR') {
		$issuer_comment .= '<div class="cont_notation"><span class="skin bold">ΠΑΡΑΤΗΡΗΣΕΙΣ:</span>
							<div class="cont_notation_inner">
								<span class="notes">'.$order_comment.'</span>
							</div>
						</div>';
	} else {
		$issuer_comment .= '<div class="cont_notation"><span class="skin bold">COMMENTS:</span>
							<div class="cont_notation_inner">
								<span class="notes">'.$order_comment.'</span>
							</div>
						</div>';
	}

	echo $issuer_comment;
}

function primer_sign_issuer_title() {
	$sign_issuer_title = '';

	$receipt_id = get_the_ID();
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$customer = new WC_Customer( $issuer_client_id );
	$customer_country = $customer->get_country();

	if ($customer_country == 'GR') {
		$sign_issuer_title = '<span class="sign_left">ΕΚΔΟΣΗ</span>';
	} else {
		$sign_issuer_title = '<span class="sign_left">ISSUER</span>';
	}

	echo $sign_issuer_title;
}

function primer_sign_issuer_fullname() {
	$sign_issuer_fullname = '';

	$receipt_id = get_the_ID();
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$customer = new WC_Customer( $issuer_client_id );
	$customer_country = $customer->get_country();

	if ($customer_country == 'GR') {
		$sign_issuer_fullname = '<span class="fullname_sign">Ονοματεπώνυμο Υπογραφή</span>';
	} else {
		$sign_issuer_fullname = '<span class="fullname_sign">FULL NAME SIGNATURE</span>';
	}

	echo $sign_issuer_fullname;
}

function primer_sign_recipient_title() {
	$sign_recipient_title = '';

	$receipt_id = get_the_ID();
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$customer = new WC_Customer( $issuer_client_id );
	$customer_country = $customer->get_country();

	if ($customer_country == 'GR') {
		$sign_recipient_title = '<span class="sign_right">ΠΑΡΑΛΑΒΗ</span>';
	} else {
		$sign_recipient_title = '<span class="sign_right">RECIPIENT</span>';
	}

	echo $sign_recipient_title;
}

function primer_sign_recipient_fullname() {
	$sign_recipient_fullname = '';

	$receipt_id = get_the_ID();
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$customer = new WC_Customer( $issuer_client_id );
	$customer_country = $customer->get_country();

	if ($customer_country == 'GR') {
		$sign_recipient_fullname = '<span class="fullname_sign">Ονοματεπώνυμο Υπογραφή</span>';
	} else {
		$sign_recipient_fullname = '<span class="fullname_sign">FULL NAME <BR>SIGNATURE</span>';
	}

	echo $sign_recipient_fullname;
}

function primer_sum_unit_title() {
	$sum_unit_title = '';

	$receipt_id = get_the_ID();
	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$customer = new WC_Customer( $issuer_client_id );
	$customer_country = $customer->get_country();

	if ($customer_country == 'GR') {
		$sum_unit_title = 'ΣΥΝΟΛΟ ΤΕΜΑΧΙΩΝ: ';
	} else {
		$sum_unit_title = 'SUM OF UNITS: ';
	}

	echo $sum_unit_title;
}

function primer_display_issuer_order_total_price() {

	$issuer_total = '';

	$receipt_id = get_the_ID();
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);

	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$customer = new WC_Customer( $issuer_client_id );
	$customer_country = $customer->get_country();

	$order = wc_get_order( $order_id );

	foreach ( $order->get_items() as $item_id => $item ) {
		$product_id = $item->get_product_id();
		$product_instance = wc_get_product($product_id);
	}

	$discount_tax = $order->get_discount_tax();
	$discount_total = $order->get_discount_total();
	$fees = $order->get_fees();
	$shipping_tax = $order->get_shipping_tax();
	$shipping_total = $order->get_shipping_total();
	$tax_totals = $order->get_tax_totals();
	$taxes = $order->get_taxes();
	$total = $order->get_total();

	$subtotal = $order->get_subtotal();

	$total_discount = $order->get_total_discount();
	$total_tax = $order->get_total_tax();

	$currency   = $order->get_currency();
	$currency_symbol = get_woocommerce_currency_symbol( $currency );

	$price_excl_tax = wc_get_price_excluding_tax( $product_instance ); // price without VAT
	$price_incl_tax = $price_excl_tax + $total_tax;  // price with VAT

	$issuer_total .= '<div class="totals">';

	$issuer_total .= '<table class="totals_table">';

	$issuer_total .= '<tr>';
	if ($customer_country == 'GR') {
		$issuer_total .= '<td class="text-left"><p>ΑΞΙΑ ΠΡΟ ΕΚΠΤΩΣΗΣ</p></td>';
	} else {
		$issuer_total .= '<td class="text-left"><p>TOTAL NO DISCOUNT</p></td>';
	}


	$issuer_total .= '<td class="text-right">';
	$issuer_total .= '<p><span class="total_nodiscount">'.$total.' '.$currency_symbol.'</span> </p>';
	$issuer_total .= '</td>';

	$issuer_total .= '</tr>';

	$issuer_total .= '<tr>';
	if ($customer_country == 'GR') {
		$issuer_total .= '<td class="text-left"><p>ΣΥΝΟΛΟ ΕΚΠΤΩΣΗΣ</p></td>';
	} else {
		$issuer_total .= '<td class="text-left"><p>TOTAL DISCOUNT</p></td>';
	}

	$issuer_total .= '<td class="text-right">';
	$issuer_total .= '<p><span class="total_discount">'.$total_discount.' '.$currency_symbol.'</span></p>';
	$issuer_total .= '</td>';
	$issuer_total .= '</tr>';


	$issuer_total .= '<tr>';
	if ($customer_country == 'GR') {
		$issuer_total .= '<td class="text-left"><p>ΣΥΝΟΛΟ ΧΩΡΙΣ ΦΠΑ</p></td>';
	} else {
		$issuer_total .= '<td class="text-left"><p>TOTAL WITHOUT VAT</p></td>';
	}

	$issuer_total .= '<td class="text-right">';
	$issuer_total .= '<p><span class="total_withoutvat">'.$subtotal.' '.$currency_symbol.'</span> </p>';
	$issuer_total .= '</td>';
	$issuer_total .= '</tr>';

	$issuer_total .= '<tr>';
	if ($customer_country == 'GR') {
		$issuer_total .= '<td class="text-left"><p>ΦΟΡΟΙ</p></td>';
	} else {
		$issuer_total .= '<td class="text-left"><p>TAXES</p></td>';
	}

	$issuer_total .= '<td class="text-right">';
	$issuer_total .= '<p><span class="amounttotal">'.$total_tax.' '.$currency_symbol.'</span> </p>';
	$issuer_total .= '</td>';
	$issuer_total .= '</tr>';

	$issuer_total .= '<tr>';
	if ($customer_country == 'GR') {
		$issuer_total .= '<td class="text-left"><p>ΤΕΛΙΚΟ ΣΥΝΟΛΟ</p></td>';
	} else {
		$issuer_total .= '<td class="text-left"><p>TOTAL SUM</p></td>';
	}

	$issuer_total .= '<td class="text-right">';
	$issuer_total .= '<p><span class="amounttotal">'.$total.' '.$currency_symbol.'</span> </p>';
	$issuer_total .= '</td>';
	$issuer_total .= '</tr>';


	$issuer_total .= '<tr class="blank_row bordered"><td class="text-left">&nbsp;</td></tr>';

	$issuer_total .= '<tr>';
	if ($customer_country == 'GR') {
		$issuer_total .= '<td class="text-left finalprice"><p>ΠΛΗΡΩΤΕΟ ΠΟΣΟ</p></td>';
	} else {
		$issuer_total .= '<td class="text-left finalprice"><p>TOTAL PAYMENT</p></td>';
	}

	$issuer_total .= '<td class="text-right">';
	$issuer_total .= '<p><span class="totalpayment">'.$total.' '.$currency_symbol.'</span> </p>';
	$issuer_total .= '</td>';
	$issuer_total .= '</tr>';

	$issuer_total .= '</table>';
	$issuer_total .= '<div class="total_funny_box"></div>';
	$issuer_total .= '</div>';

	echo $issuer_total;

}

function primer_display_issuer_logo() {
	if (!empty(primer_get_mydata_logo())) {
		echo primer_get_mydata_logo() ? '<img class="logo_img" src="'.esc_url( primer_get_mydata_logo() ).'">' : '';
	} else {
		echo '';
	}
}

function primer_get_mydata_logo() {
	$mydata = PrimerSettings::get_mydata_details();
	return apply_filters( 'primer_get_mydata_logo', $mydata['logo'], $mydata );
}

function primer_display_invoice_information() {
	$invoice_information_container = '';
	$receipt_id = get_the_ID();

	$invoice_type_text = '';

	$invoice_type = get_the_terms($receipt_id, 'receipt_status');
	$invoice_type_slug = $invoice_type[0]->slug;
	$invoice_type_name = explode('_', $invoice_type_slug);
	$find_invoice_in_slug = $invoice_type_name[1];
	if ($find_invoice_in_slug == 'receipt') {
		$invoice_type_text = __('RETAIL RECEIPT', 'primer');
	}
	if ($find_invoice_in_slug == 'invoice') {
		$invoice_type_text = __('WHOLESALE INVOICE', 'primer');
	}

	$invoice_information_container = '<tr>';

	$invoice_information_container .= '<td><span class="invoice_type">'.$invoice_type_text.'</span></td>';
	$invoice_information_container .= '<td><span class="invoice_number">'.$receipt_id.'</span></td>';
	$invoice_information_container .= '<td><span class="invoice_date"> '.get_the_date('d/m/Y', $receipt_id).'</span></td>';
	$invoice_information_container .= '<td><span class="invoice_time"> '.get_the_date('H:i', $receipt_id).'</span></td>';

	$invoice_information_container .= '</tr>';

	echo $invoice_information_container;
}

function primer_display_left_customer_info() {
	$left_customer_info = '';

	$receipt_id = get_the_ID();
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);

	$invoice_type_text = '';

	$invoice_type = get_the_terms($receipt_id, 'receipt_status');
	$invoice_type_slug = $invoice_type[0]->slug;
	$invoice_type_name = explode('_', $invoice_type_slug);
	$find_invoice_in_slug = $invoice_type_name[1];

	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$customer = new WC_Customer( $issuer_client_id );

	$customer_country = $customer->get_country();

	$billing_first_name = $customer->get_billing_first_name();
	$billing_last_name = $customer->get_billing_last_name();

	$customer_full_name = $billing_first_name . ' ' . $billing_last_name;

	$left_customer_info = '<table>';

	$left_customer_info .= '<tr>';
	if ($customer_country == 'GR') {
		$left_customer_info .= '<td class="skin bold"><span> ΚΩΔΙΚΟΣ</span></td>';
	} else {
		$left_customer_info .= '<td class="skin bold"><span> CUSTOMER ID</span></td>';
	}
	$left_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_code">'.$issuer_client_id.'</span></td>';
	$left_customer_info .= '</tr>';

	$left_customer_info .= '<tr>';
	if ($customer_country == 'GR') {
		$left_customer_info .= '<td class="skin bold"><span> ΕΠΩΝΥΜΙΑ</span></td>';
	} else {
		$left_customer_info .= '<td class="skin bold"><span> NAME</span></td>';
	}
	$left_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_name">'.$customer_full_name.'</span></td>';
	$left_customer_info .= '</tr>';

	if ($find_invoice_in_slug == 'invoice') {
		$profession = get_post_meta($order_id, '_billing_store', true);
		$vat_number = get_post_meta($order_id, '_billing_vat', true);
		$doy = get_post_meta($order_id, '_billing_doy', true);
		$doy_value = primer_return_doy_args()[$doy];
	} else {
		$profession = '';
		$vat_number = '';
		$doy = '';
		$doy_value = '';
	}

	$left_customer_info .= '<tr>';
	if ($customer_country == 'GR') {
		$left_customer_info .= '<td class="skin bold"><span> ΕΠΑΓΓΕΛΜΑ</span></td>';
	} else {
		$left_customer_info .= '<td class="skin bold"><span> ACTIVITY</span></td>';
	}
	$left_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_activity">'.$profession.'</span></td>';
	$left_customer_info .= '</tr>';

	$left_customer_info .= '<tr>';
	if ($customer_country == 'GR') {
		$left_customer_info .= '<td class="skin bold"><span> ΑΦΜ</span></td>';
	} else {
		$left_customer_info .= '<td class="skin bold"><span> VAT NUMBER</span></td>';
	}
	$left_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_vat">'.$vat_number.'</span></td>';
	$left_customer_info .= '</tr>';

	$left_customer_info .= '<tr>';
	if ($customer_country == 'GR') {
		$left_customer_info .= '<td class="skin bold"><span> ΔΟΥ</span></td>';
	} else {
		$left_customer_info .= '<td class="skin bold"><span> DOY</span></td>';
	}
	$left_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_doy">'.$doy_value.'</span></td>';
	$left_customer_info .= '</tr>';

	$left_customer_info .= '<tr class="blank_row">';
	$left_customer_info .= '<td>&nbsp;</td>';
	$left_customer_info .= '</tr>';

	$left_customer_info .= '</table>';

	echo $left_customer_info;
}

function primer_display_right_customer_info() {
	$right_customer_info = '';

	$receipt_id = get_the_ID();
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);
	$order = wc_get_order( $order_id );

	$invoice_type_text = '';

	$invoice_type = get_the_terms($receipt_id, 'receipt_status');
	$invoice_type_slug = $invoice_type[0]->slug;
	$invoice_type_name = explode('_', $invoice_type_slug);
	$find_invoice_in_slug = $invoice_type_name[1];

	$issuer_client_id = get_post_meta($receipt_id, 'receipt_client_id', true);

	$customer = new WC_Customer( $issuer_client_id );

	$customer_country = $customer->get_country();

	$customer_city = $customer->get_city();
	$billing_address = $customer->get_billing_address();
	$shipping_address = $customer->get_shipping_address();

	$payment_type = $order->get_payment_method_title();

	$right_customer_info = '<table>';

	$right_customer_info .= '<tr>';
	if ($customer_country == 'GR') {
		$right_customer_info .= '<td class="skin bold"><span> ΤΡΟΠΟΣ ΠΛΗΡΩΜΗΣ</span></td>';
	} else {
		$right_customer_info .= '<td class="skin bold"><span> TYPE OF PAYMENT</span></td>';
	}
	$right_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_paytype">'.$payment_type.'</span></td>';
	$right_customer_info .= '</tr>';

	$right_customer_info .= '<tr>';
	if ($customer_country == 'GR') {
		$right_customer_info .= '<td class="skin bold"><span> ΠΟΛΗ</span></td>';
	} else {
		$right_customer_info .= '<td class="skin bold"><span> CITY</span></td>';
	}
	$right_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_city">'.$customer_city.'</span></td>';
	$right_customer_info .= '</tr>';

	$right_customer_info .= '<tr>';
	if ($customer_country == 'GR') {
		$right_customer_info .= '<td class="skin bold"><span> ΔΙΕΥΘΥΝΣΗ</span></td>';
	} else {
		$right_customer_info .= '<td class="skin bold"><span> ADDRESS</span></td>';
	}
	$right_customer_info .= '<td class="info_value"><span>: </span><span class="counterparty_address">'.$billing_address.'</span></td>';
	$right_customer_info .= '</tr>';

	$right_customer_info .= '<tr>';
	if ($customer_country == 'GR') {
		$right_customer_info .= '<td class="skin bold"><span> ΔΙΕΥΘΥΝΣΗ ΑΠΟΣΤΟΛΗΣ</span></td>';
	} else {
		$right_customer_info .= '<td class="skin bold"><span> SHIPPING ADDRESS</span></td>';
	}
	$right_customer_info .= '<td class="info_value"><span>: </span><span class="send_place">'.$shipping_address.'</span></td>';
	$right_customer_info .= '</tr>';

	$right_customer_info .= '<tr class="blank_row">';
	$right_customer_info .= '<td>&nbsp;</td>';
	$right_customer_info .= '</tr>';

	$right_customer_info .= '</table>';

	echo $right_customer_info;
}

