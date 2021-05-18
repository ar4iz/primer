<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PrimerSMTP {

	public $opts;
	protected static $instance = null;

	public function __construct() {

		$this->opts        = get_option( 'primer_emails' );
		$this->opts        = ! is_array( $this->opts ) ? array() : $this->opts;

		add_filter( 'wp_mail', array( $this, 'wp_mail' ), 2147483647 );
		add_action( 'phpmailer_init', array( $this, 'init_smtp' ), 999 );

		require_once 'class-primer-smtp-utils.php';
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function wp_mail( $args ) {
		return $args;
	}

	public function init_smtp( &$phpmailer ) {
		//check if SMTP credentials have been configured.

		/* Set the mailer type as per config above, this overrides the already called isMail method */
		$phpmailer->IsSMTP();

		$from_email = $this->opts['send_from'];

		$phpmailer->From     = $from_email;
		$phpmailer->SetFrom( $phpmailer->From );

		/* Set the other options */
		$phpmailer->Host = $this->opts['smtp_server'];
		$phpmailer->Port = $this->opts['port'];

		if ( 'yes' === $this->opts['autentication'] ) {
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = $this->opts['name'];
			$phpmailer->Password = $this->get_password();
		}
		//PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate.
		$phpmailer->SMTPAutoTLS = false;

		// Insecure SSL option enabled
		$phpmailer->SMTPOptions = array(
			'ssl' => array(
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true,
			),
		);

		//set reasonable timeout
		$phpmailer->Timeout = 10;

	}

	public function credentials_configured() {
		$credentials_configured = true;
		if ( ! isset( $this->opts['send_from'] ) || empty( $this->opts['send_from'] ) ) {
			$credentials_configured = false;
		}
		return $credentials_configured;
	}

	public function test_mail( $to_email, $subject, $message ) {
		$ret = array();
		if ( ! $this->credentials_configured() ) {
			return false;
		}

		global $wp_version;

		if ( version_compare( $wp_version, '5.4.99' ) > 0 ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			$mail = new PHPMailer( true );
		} else {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			$mail = new \PHPMailer( true );
		}

		try {

			$charset       = get_bloginfo( 'charset' );
			$mail->CharSet = $charset;

			$from_name  = get_bloginfo( 'name' );
			$from_email = $this->opts['send_from'];

			$mail->IsSMTP();


			// send plain text test email
			$mail->ContentType = 'text/plain';
			$mail->IsHTML( false );

			if (!empty($this->opts['smtp_server']) && !empty($this->opts['port']) && !empty($this->opts['username']) && !empty($this->get_password())) {
				$this->opts['autentication'] = 'yes';
				update_option('primer_emails', $this->opts);
			}

			/* If using smtp auth, set the username & password */
			/*if ($this->opts['autentication']) {
				if ( 'yes' === $this->opts['autentication'] ) {
					$mail->SMTPAuth = true;
					$mail->Username = $this->opts['name'];
					$mail->Password = $this->get_password();
				}
			}*/


			/* PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate. */
			$mail->SMTPAutoTLS = true;

			// Insecure SSL option enabled
			/*$mail->SMTPOptions = array(
				'ssl' => array(
					'verify_peer'       => false,
					'verify_peer_name'  => false,
					'allow_self_signed' => true,
				),
			);*/

			/* Set the other options */
			if (!empty($this->opts['smtp_server'])) {
				$mail->Host = $this->opts['smtp_server'];
			}
			if (!empty($this->opts['port'])) {
				$mail->Port = $this->opts['port'];
			}

			if (empty($this->opts['smtp_server']) || empty($this->opts['port'])) {
				$mail->Mailer = 'mail';
			}

			//Add reply-to if set in settings.
			if ( ! empty( $this->opts['reply_to_email'] ) ) {
				$mail->AddReplyTo( $this->opts['reply_to_email'], $from_name );
			}

			$mail->SetFrom( $from_email, $from_name );
			//This should set Return-Path header for servers that are not properly handling it, but needs testing first
			//$mail->Sender		 = $mail->From;
			$mail->Subject = $subject;
			$mail->Body    = $message;
			$mail->AddAddress( $to_email );
			global $debug_msg;
			$debug_msg         = '';
			$mail->Debugoutput = function ( $str, $level ) {
				global $debug_msg;
				$debug_msg .= $str;
			};
			$mail->SMTPDebug   = 1;
			//set reasonable timeout
			$mail->Timeout = 10;

			/* Send mail and return result */
			$mail->Send();
			$mail->ClearAddresses();
			$mail->ClearAllRecipients();


		} catch ( \Exception $e ) {
			$ret['error'] = $mail->ErrorInfo;
		} catch ( \Throwable $e ) {
			$ret['error'] = $mail->ErrorInfo;
		}
		$ret['debug_log'] = $debug_msg;

		if (!empty($ret['error'])) {
			echo '<div class="notice notice-error is-dismissible"><p><strong>';
			_e('SMTP connect failed. Check your SMTP settings fields', 'primer');
			echo '</strong></p></div>';
		}
		else {
			echo '<div class="notice notice-success is-dismissible"><p><strong>';
			_e('Test email sent successfully', 'primer');
			echo '</strong></p></div>';
		}

		return $ret;
	}

	public function get_password() {
		$temp_password = isset( $this->opts['password'] ) ? $this->opts['password'] : '';
		if ( '' === $temp_password ) {
			return '';
		}

		$password     = '';
		$decoded_pass = base64_decode( $temp_password ); //phpcs:ignore
		/* no additional checks for servers that aren't configured with mbstring enabled */
		if ( ! function_exists( 'mb_detect_encoding' ) ) {
			return $decoded_pass;
		}
		/* end of mbstring check */
		if ( base64_encode( $decoded_pass ) === $temp_password ) { //phpcs:ignore
			//it might be encoded
			if ( false === mb_detect_encoding( $decoded_pass ) ) {  //could not find character encoding.
				$password = $temp_password;
			} else {
				$password = base64_decode( $temp_password ); //phpcs:ignore
			}
		} else { //not encoded
			$password = $temp_password;
		}
		return stripslashes( $password );
	}

	public function encrypt_password( $pass ) {
		if ( '' === $pass ) {
			return '';
		}

		if ( empty( $this->opts['encrypt_pass'] ) || ! extension_loaded( 'openssl' ) ) {
			// no openssl extension loaded - we can't encrypt the password
			$password = base64_encode( $pass ); //phpcs:ignore
			update_option( 'primer_smtp_pass_encrypted', false );
		} else {
			// let's encrypt password
			$cryptor  = Primer_SMTP_Utils::get_instance();
			$password = $cryptor->encrypt_password( $pass );
			update_option( 'primer_smtp_pass_encrypted', true );
		}
		return $password;
	}
}

PrimerSMTP::get_instance();

add_action('wp_ajax_test_send_form', 'test_send_form');
function test_send_form() {
	var_dump($_POST);
	wp_die();
}
