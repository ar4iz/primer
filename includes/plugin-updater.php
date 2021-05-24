<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit;
}

/**
 * Calls the class.
 */

function primer_call_licence_updater_class() {
	new Primer_Licence_Updater();
}
add_action('primer_loaded', 'primer_call_licence_updater_class', 1);

class Primer_Licence_Updater {
	private $store_url 	= 'https://example.com';
	private $name 		= 'Licence Key';
	private $version 	= PRIMER_VERSION;
	private $slug 		= 'primer';

	private $key_name = 'primer_license_key';
	private $status_name = 'primer_license_status';

	private $license_key = '';
	private $license_status = '';

	public function __construct() {
		// retrieve our license key from the DB
		$licenses = get_option( 'primer_licenses' );

		if ( isset( $licenses[ $this->key_name ] ) ) {
			$this->license_key = trim( $licenses[ $this->key_name ] );
		}
		if ( isset( $licenses[ $this->status_name ] ) ) {
			$this->license_status = trim( $licenses[ $this->status_name ] );
		}

		add_filter( 'primer_licenses_option_fields', array( $this, 'license_field' ), 1 );
		add_action( 'admin_init', array( $this, 'activate_license' ) );

	}

	public function license_field( $options ) {
		$options['fields'][] = array(
			'name'      => $this->name,
			'desc'      => __('', 'primer'),
			'id'        => $this->key_name,
			'type'      => 'text',
			'after_field' => array( $this, 'after_field' ),
			'default'   => $this->license_key,
		);

		return $options;
	}

	public function after_field( $args, $field ) {
		if ( empty( $this->license_key ) ) {
			return;
		}

		$status = '';
		if ( $this->license_status ) {
			$status = 'valid' === $this->license_status ? 'active' : $this->license_status;
			$status = '<span class="license-status license-'. $status .'">' . sprintf( esc_html__( 'License: %s', 'primer' ), $status ) . '</span>';
		}

		$nonce = wp_nonce_field( 'primer_license_nonce', 'primer_license_nonce_' . $this->slug, false, false );

		$id = $this->slug . ( 'valid' === $this->license_status ? '_license_deactivate' : '_license_activate' );

		$label = 'valid' === $this->license_status
			? esc_html__('Deactivate License', 'primer')
			: esc_html__('Activate License', 'primer');

		printf(
			'<p>%1$s%2$s<input type="submit" class="button-secondary" name="%3$s" value="%4$s"/></p>',
			$status,
			$nonce,
			$id,
			$label
		);
	}

	public function activate_license() {

		// listen for our activate button to be clicked
		if ( isset( $_POST[ $this->slug . '_license_activate' ], $_POST[ $this->key_name ] ) ) {

			// run a quick security check
			if ( ! check_admin_referer( 'primer_license_nonce', 'primer_license_nonce_' . $this->slug ) ) {
				return; // get out if we didn't click the Activate button
			}

			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => sanitize_text_field( $_POST[ $this->key_name ] ),
				'item_name'  => urlencode( $this->name ), // the name of our product in EDD
				'url'        => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_post( $this->store_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
			if (is_wp_error( $response )) {
				return false;
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// $license_data->license will be either "valid" or "invalid"
			$licenses = get_option( 'primer_licenses' );
			$licenses[ $this->key_name ]    = trim( $api_params['license'] );
			$licenses[ $this->status_name ] = trim( $license_data->license );

			update_option( 'primer_licenses', $licenses );
			$this->license_key    = $licenses[ $this->key_name ];
			$this->license_status = $licenses[ $this->status_name ];
		}
	}

	public function deactivate_license() {

		// listen for our activate button to be clicked
		if( isset( $_POST[ $this->slug . '_license_deactivate' ] ) ) {

			// run a quick security check
			if ( ! check_admin_referer( 'primer_nonce', 'primer_license_nonce_' . $this->slug ) ) {
				return; // get out if we didn't click the Activate button
			}

			// data to send in our API request
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $this->license_key,
				'item_name'  => urlencode( $this->name ), // the name of our product in EDD
				'url'        => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_post( $this->store_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				return false;
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			$licenses = get_option( 'primer_licenses' );
			$licenses[ $this->status_name ] = trim( $license_data->license );

			$this->license_status = $licenses[ $this->status_name ];

			// $license_data->license will be either "deactivated" or "failed"
			if ( $license_data->license == 'deactivated' ) {
				update_option( 'primer_licenses', $licenses );
				//wp_redirect( admin_url( 'admin.php?page=sliced_licenses' ) );
				//exit;
			}

		}
	}

}
