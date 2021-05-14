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
			form.setAttribute("action", "primer_smtp_form_submit");
			$(inputs).each(function (i, el) {
				form.append(el)
			})
			var s = document.createElement('input');
			s.setAttribute("type", "submit");
			s.setAttribute("value", "Submit");
			form.append(s);
			$('#primer_emails').before($(form));
			$(form).submit();
		}
		$('.send_tested_email').on('click', function () {
			var sibling_divs = $(this).prevAll($('.cmb-row.cmb-type-text'));
			var email_fields = sibling_divs.find('input');
			create_form(email_fields);
		})
	});

})( jQuery );
