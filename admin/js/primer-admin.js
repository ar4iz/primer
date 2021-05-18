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
			var sibling_divs = $(this).prevAll($('.cmb-row.cmb-type-text'));
			var email_fields = sibling_divs.find('input');
			create_form(email_fields);
		});

		/*$(document).on('click', '#test-email-form-submit', function () {
			$('#email-test-form').submit();
			return true;
		});*/

		$(document).on('submit', '#email-test-form', function (e) {
			e.preventDefault();
			var data = $('#email-test-form').serialize();

			$.ajax({
				url: ajaxurl,
				data: data,
				method: "POST",
				success: function (data) {
					if (data) {
						$('#wpbody-content .nav-tab-wrapper').prepend(data);
					}
				}
			})
		})
	});

})( jQuery );
