<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Calls the class.
 */
function primer_call_invoice_class() {
	new Primer_Invoice();
}
add_action('primer_loaded', 'primer_call_invoice_class');

class Primer_Invoice {

	public function __construct() {

	}

	/**
	 * Get the invoice template.
	 *
	 * @since   1.0.0
	 */
	public static function get_greek_template() {
		$invoices 	= get_option( 'primer_mydata' );
		$template 	= isset( $invoices['greek_template'] ) ? $invoices['greek_template'] : 'greek_template1';
		return $template;
	}

	public static function get_english_template() {
		$invoices 	= get_option( 'primer_mydata' );
		$template 	= isset( $invoices['english_template'] ) ? $invoices['english_template'] : 'english_template1';
		return $template;
	}
}
