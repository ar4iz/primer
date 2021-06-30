(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
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
		function create_form(inputs) {
			var form = document.createElement('form');
			form.setAttribute("method", "post");
			form.setAttribute("id", "email-test-form");
			form.setAttribute("style", "display:none");
			// form.setAttribute("action", "primer_smtp_settings");
			$(inputs).each(function (i, el) {
				el = el.cloneNode();
				form.append(el)
			})
			var h = document.createElement('input');
			h.setAttribute("type", "hidden");
			h.setAttribute("name", "action");
			h.setAttribute("value", "primer_smtp_settings");
			form.append(h);

			var s = document.createElement('input');
			s.setAttribute("type", "submit");
			s.setAttribute("id", "test-email-form-submit");
			s.setAttribute("value", "Submit");
			form.append(s);
			$('#primer_emails').before($(form));
			// $(form).submit();
			$('#test-email-form-submit').trigger('click');
		}
		$('.send_tested_email').on('click', function () {
			$('.send_tested_email').attr('disabled', true);
			var sibling_divs = $(this).prevAll($('.cmb-row.cmb-type-text'));
			var email_fields = sibling_divs.find('input');
			create_form(email_fields);
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
						$(popup).remove();
					}
				}
			})

		}

		$(document).on('submit', '#email-test-form', function (e) {
			e.preventDefault();

			var data = $('#email-test-form').serialize();
			$.ajax({
				url: ajaxurl,
				data: data,
				method: "POST",
				success: function (data) {
					if (data) {
						$('#primer_emails').append(data);
						popupOpenClose('.primer_popup');
						$('.send_tested_email').removeAttr('disabled');
					}
				},
				error: function(xhr, status, error) {
					console.log(error)
					$('#email-test-form').remove();
				},
				complete: function () {
					$('#email-test-form').remove();
				}
			})
		})

		$(".button.save_order").on('click', function (e) {
			var save_btn_val = $(".button.save_order").val();
			var confirmation;

			var confirm_text = '';

			var line_items = $('#order_line_items');

			if (line_items.children().length <= 0) {
				confirm_text += 'Product item, ';
			}

			var exist_taxes = true;

			var invoice_required = true;

			var country_required = true;

			var tax_column = line_items.find('td.line_tax');
			var tax_column_item = tax_column.find('.view');
			var check_tax_items = true;
			tax_column_item.each(function (i, el) {
				let tax_column_item_text = $(el).text();
				let tax_trim_text = tax_column_item_text.trim();
				if (tax_trim_text == "–") {
					check_tax_items = false;
				}
			})
			var tax_column_item_text = tax_column_item.text();
			var tax_trim_text = tax_column_item_text.trim();


			if (tax_column.length <= 0 || check_tax_items == false) {
				confirm_text += 'Tax value, ';
				exist_taxes = false;
			}

			var select_invoice_type = $('.wc-radios input[name="get_invoice_type"]:checked').val();
			var edit_address_wrap = $('.edit_address');

			var first_name_label = edit_address_wrap.find('._billing_first_name_field label').text();
			var first_name = edit_address_wrap.find('input[name="_billing_first_name"]').val();
			if (first_name == '') {
				confirm_text += first_name_label + ', '
			}

			var last_name_label = edit_address_wrap.find('._billing_last_name_field label').text();
			var last_name = edit_address_wrap.find('input[name="_billing_last_name"]').val();
			if (last_name == '') {
				confirm_text += last_name_label + ', '
			}

			var country_label = edit_address_wrap.find('._billing_country_field label').text();
			var country = edit_address_wrap.find('select[name="_billing_country"]').val();
			if (country == '') {
				confirm_text += country_label + ', '
				country_required = false
			}

			var address_1_label = edit_address_wrap.find('._billing_address_1_field label').text();
			var address_1 = edit_address_wrap.find('input[name="_billing_address_1"]').val();
			if (address_1 == '') {
				confirm_text += address_1_label + ', '
			}

			var city_label = edit_address_wrap.find('._billing_city_field label').text();
			var city = edit_address_wrap.find('input[name="_billing_city"]').val();
			if (city == '') {
				confirm_text += city_label + ', '
			}

			var postcode_label = edit_address_wrap.find('._billing_postcode_field label').text();
			var postcode = edit_address_wrap.find('input[name="_billing_postcode"]').val();
			if (postcode == '') {
				confirm_text += postcode_label + ', '
			}

			var phone_label = edit_address_wrap.find('._billing_phone_field label').text();
			var phone = edit_address_wrap.find('input[name="_billing_phone"]').val();
			if (phone == '') {
				confirm_text += phone_label + ', '
			}

			var vat_label = edit_address_wrap.find('._billing_vat_field label').text();
			var vat = edit_address_wrap.find('input[name="_billing_vat"]').val();


			var store_label = edit_address_wrap.find('._billing_store_field label').text();
			var store = edit_address_wrap.find('input[name="_billing_store"]').val();


			var doy_label = edit_address_wrap.find('._billing_doy_field label').text();
			var doy = edit_address_wrap.find('input[name="_billing_doy"]').val();


			var company_label = edit_address_wrap.find('._billing_company_field label').text();
			var company = edit_address_wrap.find('input[name="_billing_company"]').val();

			if (select_invoice_type == 'invoice') {
				if (vat == '') {
					confirm_text += vat_label + ', '
					invoice_required = false
				}
				if (store == '') {
					confirm_text += store_label + ', '
					invoice_required = false
				}
				if (doy == '') {
					confirm_text += doy_label + ', '
					invoice_required = false
				}

				if (company == '') {
					confirm_text += company_label + ' '
					invoice_required = false
				}
			}


			if (save_btn_val == 'Create' || save_btn_val == 'Update') {
				if (confirm_text != '') {
					confirmation = confirm(confirm_text + ' are required fields! Do you want to continue?');
				} else {
					confirmation = confirm('Do you want to continue?');
				}
				if (select_invoice_type == 'invoice' && invoice_required == false) {
					confirmation = false;
				}

				if (country_required == false) {
					confirmation = false;
				}

				if (exist_taxes == false) {
					confirmation = false;
				}
				if (confirmation == false) {
					e.preventDefault();
				}
			}
		});


		var getUrlParameter = function getUrlParameter(sParam) {
			var sPageURL = window.location.search.substring(1),
				sURLVariables = sPageURL.split('&'),
				sParameterName,
				i;

			for (i = 0; i < sURLVariables.length; i++) {
				sParameterName = sURLVariables[i].split('=');

				if (sParameterName[0] === sParam) {
					return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
				}
			}
			return false;
		};

		if (getUrlParameter('tab') == 'automation') {
			var val_args = {};
			$('#primer_automation').on('change', function (el) {
				var element = $(el.target);
				var conditional_selects = $('select[id*="_receipt_order_states"]');
				if (conditional_selects.length && element.is('select')) {
					for(let i = 0; i < conditional_selects.length; i++) {
						val_args[i] = $(conditional_selects[i]).val();
					}
				}
				var selectsVal = Object.values(val_args);
				let result = selectsVal.some((element, index) => {return selectsVal.indexOf(element) !== index});
				if (result) {
					var popup_data = '<div class="primer_popup popup_error"><h3>Duplicate values are not accepted. Please select a different option</h3></div>';
					$('#primer_automation').append(popup_data)
					popupOpenClose('.primer_popup');
				}

				var send_admin_check = $('input[name="send_email_to_admin"]');
				if (send_admin_check.length && send_admin_check.prop('checked')) {
					var suc_check = $('input[name="send_successful_log"]');
					var fail_check = $('input[name="send_failed_log"]');
					if ((suc_check.prop('checked') === false) && (fail_check.prop('checked') === false)) {
						var popup_data_check = '<div class="primer_popup popup_error"><h3>Send email to admin is active-please select one option from “send successful receipts log” or “send failed receipts log” to continue</h3></div>';
						$('#primer_automation').append(popup_data_check)
						popupOpenClose('.primer_popup');
					}
				}
			});

			$(document).on('input', 'input[name="admin_email"]', function () {
				this.value = $.trim(this.value);
			})
		}

		$('#cron-execute-cron-task-now').on('click', function (e) {
			e.preventDefault();
			$(this).text('Loading...');

			var data = {
				'action': 'primer_fire_cron',
			};

			/*$.ajax({
				url: ajaxurl,
				data: data,
				method: "POST",
				success: function (response) {
					console.log(response);
				}
			})*/

			$.post( ajaxurl, data, function (response) {
				if (response != "OK") {
					alert('Problems executing cron task: ' + response);
					document.location.reload();
				} else {
					alert('Cron task successfully executed');
					document.location.reload();
				}
			} )
		})

	});

})( jQuery );
