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

	$issuer_container .= '<span class="issuer_name skin">'.$customer_full_name.'</span>';

	$issuer_container .= '<p> <span class="issuer_subjectField skin">{ISSUER_SUBJECTFIELD}</span></p>';

	$issuer_container .= '<p><span class="issuer_address skin">'.$billing_address_1.'</span></p>';
	$issuer_container .= '<p><span class="issuer_address skin">'.$billing_address_2.'</span></p>';

	$issuer_container .= '<p> <span class="skin">ΑΦΜ: </span><span class="issuer_vat skin">{ISSUER_VAT}</span> <span class="skin">ΔΟΥ: </span> <span class="issuer_doy skin">{ISSUER_DOY}</span></p>';

	$issuer_container .= '<p class="gemh_issuer_p skin"> <span class="skin">ΑΡ.ΓΕΜΗ: </span> <span class="issuer_gemh">{ISSUER_GEMH}</span></p>';

	echo $issuer_container;
}

function primer_display_issuer_product() {

	$issuer_product = '';

	$receipt_id = get_the_ID();
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);

	$order = wc_get_order( $order_id );

	foreach ( $order->get_items() as $item_id => $item ) {
		$issuer_product .= '<tr class="products">';
		$product_id = $item->get_product_id();
		$product_instance = wc_get_product($product_id);

		$issuer_product .= '<td><span class="item_code">'.$product_id.'</span></td>';

		$product_name = $product_instance->get_name();

		$issuer_product .= '<td><span class="item_name">'.$product_name.'</span></td>';

		$product_full_description = $product_instance->get_description();
		$product_short_description = $product_instance->get_short_description();

		$quantity = $item->get_quantity();

		$issuer_product .= '<td><span class="item_quantity">'.$quantity.'</span></td>';

		$measure_unit = 'pc';

		$issuer_product .= '<td><span class="item_mu">'.$measure_unit.'</span></td>';

		$regular_price = $product_instance->get_regular_price();

		$issuer_product .= '<td><span class="item_unit_price">'.$regular_price.'</span></td>';

		$issuer_product .= '<td><span class="item_discount">{DISCOUNT}</span></td>';

		$issuer_product .= '<td><span class="item_whtaxes">{WHTAXES}</span></td>';

		$issuer_product .= '<td><span class="item_vat">{VAT_LIST}</span></td>';

		$issuer_product .= '<td><span class="item_price_novat">{PRICE_NOVAT_LIST}</span></td>';

		$sale_price = $product_instance->get_sale_price();
		$price = $product_instance->get_price();

		$issuer_product .= '<td><span class="item_price_novat">'.$price.'</span></td>';

		$tax_status = $product_instance->get_tax_status();
		$tax_class = $product_instance->get_tax_class();

		$issuer_product .= '</tr>';
	}

	echo $issuer_product;


	//	update_post_meta($post_id, 'receipt_status', 'issued');
//	update_post_meta($post_id, 'order_id_to_receipt', $id_of_order);
//	update_post_meta($id_of_order, 'receipt_status', 'issued');
//	add_post_meta($post_id, 'receipt_client', $user_data);
//	add_post_meta($post_id, 'receipt_client_id', $user_id);
//	add_post_meta($post_id, 'receipt_product', $product_name);
//	add_post_meta($post_id, 'receipt_price', $order_total_price . ' ' .$currency_symbol);
}

function primer_display_issuer_comments() {

	$issuer_comment = '';

	$receipt_id = get_the_ID();
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);

	$order = wc_get_order( $order_id );

	$order_comment = $order->get_customer_note();
	$order_comment_all = $order->get_customer_order_notes();

	$issuer_comment .= '<div class="cont_notation"><span class="skin bold">COMMENTS:</span>
							<div class="cont_notation_inner">
								<span class="notes">'.$order_comment.'</span>
							</div>
						</div>';

	echo $issuer_comment;

}

function primer_display_issuer_order_total_price() {

	$issuer_total = '';

	$receipt_id = get_the_ID();
	$order_id = get_post_meta($receipt_id, 'order_id_to_receipt', true);

	$order = wc_get_order( $order_id );

	$discount_tax = $order->get_discount_tax();
	$discount_total = $order->get_discount_total();
	$fees = $order->get_fees();
	$shipping_tax = $order->get_shipping_tax();
	$shipping_total = $order->get_shipping_total();
	$tax_totals = $order->get_tax_totals();
	$taxes = $order->get_taxes();
	$total = $order->get_total();
	$total_discount = $order->get_total_discount();
	$total_tax = $order->get_total_tax();

	$issuer_total .= '<div class="totals">';

	$issuer_total .= '<table class="totals_table">';

	$issuer_total .= '<tr>';
	$issuer_total .= '<td class="text-left"><p>TOTAL NO DISCOUNT</p></td>';

	$issuer_total .= '<td class="text-right">';
	$issuer_total .= '<p><span class="total_nodiscount">'.$total.'</span> </p>';
	$issuer_total .= '</td>';

	$issuer_total .= '</tr>';

	$issuer_total .= '<tr>';
	$issuer_total .= '<td class="text-left"><p>TOTAL DISCOUNT</p></td>';
	$issuer_total .= '<td class="text-right">';
	$issuer_total .= '<p><span class="total_discount">'.$total_discount.'</span></p>';
	$issuer_total .= '</td>';
	$issuer_total .= '</tr>';


	$issuer_total .= '<tr>';
	$issuer_total .= '<td class="text-left"><p>TOTAL WITHOUT VAT</p></td>';
	$issuer_total .= '<td class="text-right">';
	$issuer_total .= '<p><span class="total_withoutvat">'.$total.'</span> </p>';
	$issuer_total .= '</td>';
	$issuer_total .= '</tr>';


	$issuer_total .= '<tr>';
	$issuer_total .= '<td class="text-left"><p>TOTAL SUM</p></td>';
	$issuer_total .= '<td class="text-right">';
	$issuer_total .= '<p><span class="amounttotal">'.$total.'</span> </p>';
	$issuer_total .= '</td>';
	$issuer_total .= '</tr>';


	$issuer_total .= '<tr class="blank_row bordered"><td class="text-left">&nbsp;</td></tr>';

	$issuer_total .= '<tr>';
	$issuer_total .= '<td class="text-left finalprice"><p>TOTAL PAYMENT</p></td>';
	$issuer_total .= '<td class="text-right">';
	$issuer_total .= '<p><span class="totalpayment">'.$total.'</span> </p>';
	$issuer_total .= '</td>';
	$issuer_total .= '</tr>';

	$issuer_total .= '</table>';
	$issuer_total .= '<div class="total_funny_box"></div>';
	$issuer_total .= '</div>';

	echo $issuer_total;

}

function primer_display_issuer_logo() {
	echo primer_get_mydata_logo() ? '<img class="logo_img" src="'.esc_url( primer_get_mydata_logo() ).'">' : '';
}

function primer_get_mydata_logo() {
	$mydata = PrimerSettings::get_mydata_details();
	return apply_filters( 'primer_get_mydata_logo', $mydata['logo'], $mydata );
}
