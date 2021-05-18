(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(document).ready(function () {
		var $invoice_type = $('input[name="billing_invoice_type"]');

		function checkInvoiceFieldsVisibility(radio_val) {
			var required = '<abbr class="required" title="<?php echo $required; ?>">*</abbr>';
			var invoice_type = radio_val === 'invoice';
			var optional_element = '<span class="optional">(optional)</span>';
			if (invoice_type) {
				my_callback()
				$('.invoice_type-hide').slideDown('fast');
				$('#billing_vat_field label > .optional').remove();
				$('#billing_vat_field').find('abbr').remove();
				$('#billing_vat_field'+' label').append(required);
				$('#billing_store_field label > .optional').remove();
				$('#billing_store_field').find('abbr').remove();
				$('#billing_store_field'+' label').append(required);
				$('#billing_company_field label > .optional').remove();
				$('#billing_company_field').find('abbr').remove();
				$('#billing_company_field'+' label').append(required);
				$('#billing_doy_field label > .optional').remove();
				$('#billing_doy_field').find('abbr').remove();
				$('#billing_doy_field'+' label').append(required);
			} else {
				my_callback()
				$('.invoice_type-hide').slideUp('fast');
				$('#billing_company_field').find('abbr').remove();
			}
		}

		$invoice_type.on('change', function () {
			checkInvoiceFieldsVisibility($(this).val())
		})

		console.log($invoice_type.val());
		checkInvoiceFieldsVisibility($invoice_type.val());

		function my_callback() {
			jQuery('body').trigger('update_checkout');
		}
	})

})( jQuery );
