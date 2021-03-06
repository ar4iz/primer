<?php
if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * !! IMPORTANT !!
 *
 * Do not edit this file!
 * To create a new template, simply create a folder in your current theme folder called 'primer'.
 * You can then copy this file into the 'primer' folder and edit that copy.
 * This will ensure that your template files are not overwritten if/when you update the Primer Receipts plugin.
 *
 */

do_action( 'primer_before_receipt_display' ); ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<title><?php wp_title() ?></title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<style>
        @page {
            size: A4;
            margin: 0;
        }

        @media print {

            html,
            body {
                width: 210mm;
                /*height: 297mm;*/
                height: 247mm;
            }

            .page {
                margin: 0;
                border: initial;
                border-radius: initial;
                width: initial;
                min-height: initial;
                box-shadow: initial;
                background: initial;
                page-break-after: always;
            }
        }
        body { font-family: DejaVu Sans, sans-serif; }

        .page {
            width: 210mm;
            height: 247mm;
            /*height: 297mm;*/
            border: 1px solid #ddd;
        }

        body {

            margin: 0;
            padding: 0;
            /* filter: grayscale(100%); */
        }


        .invoice-box {


            margin-left: 20px;
            margin-right: 20px;
            font-size: 10px;
            /*font-family: 'Helvetica Neue', Helvetica, Helvetica, Arial, sans-serif;*/
            color: #555;
            /* border: 1px solid #ddd; */
            /* padding-bottom: 40px; */
            /*width: calc(100% - 40px);
            height: calc(100% - 20px);*/
            width: 730px;
            height: 900px;
            position: relative;
        }

        .qrcode_img {
            position:absolute;
            left:276px;
            margin: auto;

        }

        .qrcodeimg_container {
            min-width: 180px;
            max-width: 180px;
            margin: 0px;
            padding: 0 12px 0 0;
        }
        .total_td_block {
			width: 263px;
		}
        .total_td_block .totals_table {
            width: 263px;
		}

        .logo_img {
            width: 100%;
        }


        /* ------------------totals------------------ */

        .total_container {
            margin-top: 10px;
            border: 1px solid transparent;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 10px;
            /* margin-right: 6px; */
        }

        .total_container>.totals {

            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);
            font-size: 4px;
            background-color: #555;
        }

        .total_container>.totals p {
            padding: 4px;
            margin: 4px;
            font-size: 12px;
			white-space: nowrap;
        }

        .totals_table {
            border-spacing: 0;
            border-collapse: collapse;
            border: 0;
        }

        .totals_table tr {
            padding: 16px;
            border: none;
        }

        .totals_table td {
            border: none;

        }

        .totalpayment {
            font-weight: 700;
            font-size: 16px;
        }

        /* ------------------end totals------------------ */

        .invoice-box table {
            width: 100%;
            text-align: left;

        }

        .invoice-box table td {
            padding: 1px;
        }



        .invoice-box table tr.top table td {
            padding: 10px
        }

        .invoice-box table tr.top table td.logo_container {
            font-size: 30px;
            width: 50%;
            color: #333
        }

        .invoice-box table tr.information table td {
            padding-bottom: 40px
        }

        .main_info_table tr {
            border: 1px solid #ddd;
            font-weight: 700;
            text-align: center;
        }

        .invoice-box table tr.details td {
            padding-bottom: 20px
        }

        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee
        }

        .invoice-box table tr.item.last td {
            border-bottom: none
        }

        .invoice-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: 700
        }

        .products {
            border: 1px solid #eee;
            text-align: center;

        }

        .products p {
            height: 24px;
            font-size: 12px;
            margin: 4px;
        }

        .product_table .products td {
            font-size: 10px;
            text-align: center;
            border-right: 1px solid #ddd;
        }

        .product_table .heading td {
            font-size: 10px;
            text-align: center;
            border-right: 1px solid #ddd;
        }

        .main_info_table .heading p {
            margin: 4px;
        }

        .product_container {
            margin-top: 4px;
            border: 2px solid #555;
            border-radius: 4px;
            overflow: hidden;
        }

        .rtl {
            direction: rtl;
            /*font-family: Tahoma, 'Helvetica Neue', Helvetica, Helvetica, Arial, sans-serif*/
        }

        .rtl table {
            text-align: right
        }

        .rtl table tr td:nth-child(2) {
            text-align: left
        }

        .sender_sign {
            /*position: absolute;
            bottom: 20px;
            left: 0px;*/
        }

        .mydata_sign {
            /*position: absolute;*/
            /*float: right*/
        }

        .pol_number {
            float: right;
        }

        .heading {
            background-color: #555;
            color: white;
        }

        .heading>td {

            height: 4px;
        }

        .information {

            border: 1px solid #555;
            border-radius: 4px;
            padding: 10px;

        }

        .main_info {

            border: 1px solid #555;
            border-radius: 4px;
            overflow: hidden;

        }

        .skin {
            color: #555;
        }

        .bold {
            color: #555;
            font-weight: bold;
        }

        .footer_container {
            position: absolute;
            bottom: 10px;
            /* border: 1px solid #ddd; */
            width: 100%;
            margin: auto;
            padding-bottom: 10px;
        }


        .header_table td {
            border: none;

        }

        .issuer_container {
            text-align: center;
            margin-top: 6px;
        }

        .issuer_container .issuer_name {
            font-size: 14px;
            font-weight: bold;
        }

        .issuer_container .issuer_subjectField {
            font-weight: bold;
            font-style: italic;
        }

        .issuer_container p {
            margin: 0px;
            font-size: 10px;
        }

        .gemh_issuer_p {
            font-style: italic;
        }

        .information_table {
            margin-top: 4px;

        }

        .information_table td {
            padding: 2px !important;
            border: none;
            font-size: 12px;
        }

        .code_head_td {
            width: 14%;
        }

        .description_head_td {
            width: 32%;
        }

        .price_head_td {
            width: 8%;
        }

        .vat_head_td {
            width: 8%;
        }

        .blank_row.bordered td {
            border-top: 1px solid white;
            background-color: white;
            max-height: 2px;
            height: 2px;
            line-height: 2px;
        }

        .text-right {
            text-align: right;
            margin-right: 20px;
            background-color: white;
        }

        .text-left {
            background-color: #555;
            color: white;
        }

        .info_value {
            font-weight: bold;
        }

        .cont_notation {
            border: 1px solid #555;
            padding: 8px;
            border-radius: 8px;
            overflow: hidden;
            height: 68px;
            margin-top: 10px;
        }

        .cont_signs {
            border: 1px solid #555;
            padding: 8px;
            border-radius: 8px;
            height: 72px;
            overflow: hidden;
            margin-top: 10px;
        }

        .footer_table td {
            vertical-align: top;
        }

        .per_vat_totals_container {
            border: 1px solid #555;
            border-radius: 8px;
            margin-top: 10px;
            padding: 12px;
        }

        .totals_per_vat th {
            width: 10%;
            color: #555;
            font-weight: bold;
        }

        .total_funny_box {
            width: 80px;
            height: 46px;
            background-color: #555;
            border: 1px solid white;
            border-radius: 0px 0px 8px 0px;
            position: absolute;
            bottom: 60px;
            right: -2px;
            z-index: -1;
            display: none;
        }

        .union_doc_sign {
            position: absolute;
            transform: translate(-5px, -50%) rotate(-90deg);
            left: -164px;
            bottom: 50%;
            font-size: 11px;
            margin: 0px;

        }

        .count_totals_container {
            padding: 4px;
            border: 4px solid #555;
            border-radius: 8px;
            min-height: 16px;
            max-height: 16px;
            overflow: hidden;
            margin-bottom: 4px;
        }

        .count_totals_container > span {
			vertical-align: middle;
            line-height: 18px;
		}

        .count_total_prods {
            font-size: 16px;
            font-weight: bold;
			vertical-align: middle;
			float: right;
        }

        .cont_sign_left {
            float: left;
            text-align: center;
            width: 50%;
            font-size: 12px;
        }

        .cont_sign_right {
            float: right;
            text-align: center;
            width: 50%;
            font-size: 12px;
        }

        .fullname_sign {
            font-size: 9px;
        }

        .sign_hr {
            margin: 0px;
            width: 80%;
            margin-left: 10%
        }

        .finalprice p {
            font-weight: bold;
            font-size: 16px !important;
        }

        .information_td_left {
            width: 49%;
            font-size: 12px;

        }

        .information_td_right {
            width: 49%;
            font-size: 12px;
        }
	</style>
</head>

<body>
	<div class="page">
	<div class="invoice-box">
		<p class="union_doc_sign skin">???????????? ?????????????????????????? ???????????? ?????????????????? ??????????????</p>
		<div class="top_table">
			<table>
				<tbody>
				<tr class="top">
					<td>
						<table class="header_table">
							<tr>
								<td class="logo_container"><?php primer_display_issuer_logo(); ?></td>

								<td class="issuer_container">
									<?php primer_display_issuer_container(); ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				</tbody>
			</table>
			<div class="main_info">
				<table>
					<tbody>
					<tr>
						<td>
							<table class="main_info_table">
								<tbody>
								<?php primer_main_info_table_head(); ?>
								<?php primer_display_invoice_information(); ?>
								</tbody>
							</table>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
			<table class="information_table">
				<tbody>
				<tr>
					<td class="information_td_left">
						<div class="information left">
							<?php primer_display_left_customer_info(); ?>
						</div>
					</td>
					<td> </td>
					<td class="information_td_right">
						<div class="information right">
							<?php primer_display_right_customer_info(); ?>
						</div>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
		<div class="product_container">
			<table class="product_table">
				<?php primer_display_issuer_product_head(); ?>
				<?php primer_display_issuer_product(); ?>
			</table>
		</div>
		<div class="footer_container">

			<div class="per_vat_totals_container">
				<!-- <p class="card-title">???????????? ?????? ??????</p> -->
				<table class="totals_per_vat"> </table>
			</div>
			<table class="footer_table">
				<tbody>
				<tr>

					<td>
						<?php primer_display_issuer_comments(); ?>
						<div class="cont_signs">
							<div class="cont_sign_left">
								<?php primer_sign_issuer_title(); ?>
								<br>
								<br>
								<br>
								<br>
								<hr class="sign_hr">
								<?php primer_sign_issuer_fullname(); ?>
							</div>
							<div class="cont_sign_right">
								<?php primer_sign_recipient_title(); ?>
								<br>
								<br>
								<br>
								<br>
								<hr class="sign_hr">
								<?php primer_sign_recipient_fullname(); ?>
							</div>
						</div>
					</td>
					<td class="qrcodeimg_container">
						<span class="qrcode_img"></span>
					</td>
					<td class="total_td_block">
						<div class="count_totals_container">
							<span><?php primer_sum_unit_title(); ?></span> <span class="count_total_prods"><?php primer_sum_unit_count(); ?></span>
						</div>
						<div class="total_container">

							<?php primer_display_issuer_order_total_price(); ?>

						</div>

					</td>

				</tr>
				</tbody>
			</table>


			<p> <span class="sender_sign">https://primer.gr/searchinvoice <br>Provided by Primer Software P.C.</span></p><br><br>
			<p class="mydata_sign">
				<span>uid: </span> <span class="uid_sign"><?php primer_invoice_uid(); ?></span>
				<span>mark:</span> <span class="mark_sign"><?php primer_invoice_mark(); ?></span>
				<span>authcode:</span> <span class="authcode_sign"><?php primer_invoice_authcode(); ?></span>
			</p>
		</div>


	</div>

</div>
</body>

</html>

