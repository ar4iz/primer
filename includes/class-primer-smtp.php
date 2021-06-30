<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PrimerSMTP {

	public $opts;
	protected static $instance = null;

	public function __construct() {

		$this->opts        = get_option( 'primer_emails' );
		$this->opts        = ! is_array( $this->opts ) ? array() : $this->opts;

		require_once 'class-primer-smtp-utils.php';

//		add_filter( 'wp_mail', array( $this, 'wp_mail' ), 2147483647 );
//		add_action( 'phpmailer_init', array( $this, 'init_smtp' ), 999 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'wp_mail_failed', array( $this, 'wp_mail_failed' ) );
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

	public function wp_mail_failed( $wp_error ) {
		if ( ! empty( $wp_error->errors ) && ! empty( $wp_error->errors['wp_mail_failed'] ) && is_array( $wp_error->errors['wp_mail_failed'] ) ) {
			echo '<div class="primer_popup popup_error"><h3>';
			echo '*** ' . implode( ' | ', $wp_error->errors['wp_mail_failed'] ) . " ***\r\n";
			echo '</h3></div>';
		}
	}

	public function init_smtp( &$phpmailer ) {
		//check if SMTP credentials have been configured.
		if ( ! $this->credentials_configured() ) {
			return;
		}

		/* Set the mailer type as per config above, this overrides the already called isMail method */
		$phpmailer->IsSMTP();

		$from_email = $this->opts['from_email_field'];
		$from_name  = get_bloginfo( 'name' );
		$phpmailer->SetFrom( $phpmailer->From, $phpmailer->FromName );

		if ( 'none' !== $this->opts['smtp_settings']['type_encryption'] ) {
			$phpmailer->SMTPSecure = $this->opts['smtp_settings']['type_encryption'];
		}

		/* Set the other options */
		$phpmailer->Host = $this->opts['smtp_settings']['smtp_server'];
		$phpmailer->Port = $this->opts['smtp_settings']['port'];


		/* If we're using smtp auth, set the username & password */
		if ( 'yes' === $this->opts['smtp_settings']['authentication'] ) {
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = $this->opts['smtp_settings']['username'];
			$phpmailer->Password = $this->get_password();
		}
		//PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate.
		$phpmailer->SMTPAutoTLS = false;

		//set reasonable timeout
		$phpmailer->Timeout = 10;

//		global $wp_version;
//
//		if ( version_compare( $wp_version, '5.4.99' ) > 0 ) {
//			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
//			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
//			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
//			$mail = new PHPMailer( true );
//		} else {
//			require_once ABSPATH . WPINC . '/class-phpmailer.php';
//			$mail = new \PHPMailer( true );
//		}
//
//		try {
//
//			$charset       = get_bloginfo( 'charset' );
//			$mail->CharSet = $charset;
//
//			$from_name  = get_bloginfo( 'name' );
//			$from_email = $this->opts['from_email_field'];
//
//			$mail->IsSMTP();
//
//
//			// send plain text test email
//			$mail->ContentType = 'text/html';
//			$mail->IsHTML( true );
//			$mail->Body    = __('Receipt from site', 'primer');
//
//
//			/* If using smtp auth, set the username & password */
//			if ( 'yes' === $this->opts['smtp_settings']['authentication'] ) {
//				$mail->SMTPAuth = true;
//				$mail->Username = $this->opts['smtp_settings']['username'];
//				$mail->Password = $this->get_password();
//			}
//
//			/* Set the SMTPSecure value, if set to none, leave this blank */
//			if ( 'none' !== $this->opts['smtp_settings']['type_encryption'] ) {
//				$mail->SMTPSecure = $this->opts['smtp_settings']['type_encryption'];
//			}
//
//
//			/* PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate. */
//			$mail->SMTPAutoTLS = true;
//
//			// Insecure SSL option enabled
//			/*$mail->SMTPOptions = array(
//				'ssl' => array(
//					'verify_peer'       => false,
//					'verify_peer_name'  => false,
//					'allow_self_signed' => true,
//				),
//			);*/
//
//			/* Set the other options */
//			if (!empty($this->opts['smtp_settings']['smtp_server'])) {
//				$mail->Host = $this->opts['smtp_settings']['smtp_server'];
//			}
//			if (!empty($this->opts['smtp_settings']['port'])) {
//				$mail->Port = $this->opts['smtp_settings']['port'];
//			}
//

//			//Add reply-to if set in settings.
//			if ( ! empty( $this->opts['reply_to_email'] ) ) {
//				$mail->AddReplyTo( $this->opts['reply_to_email'], $from_name );
//			}
//
//			$mail->SetFrom( $from_email, $from_name );
//			//This should set Return-Path header for servers that are not properly handling it, but needs testing first
//
//			//$mail->Sender		 = $mail->From;
//			global $debug_msg;
//			$debug_msg         = '';
//			$mail->Debugoutput = function ( $str, $level ) {
//				global $debug_msg;
//				$debug_msg .= $str;
//			};
//			$mail->SMTPDebug   = 1;
//			//set reasonable timeout
//			$mail->Timeout = 10;
//
//			/* Send mail and return result */
//			$mail->Send();
//			$mail->ClearAddresses();
//			$mail->ClearAllRecipients();
//
//
//		} catch ( \Exception $e ) {
//			$ret['error'] = $mail->ErrorInfo;
//		} catch ( \Throwable $e ) {
//			$ret['error'] = $mail->ErrorInfo;
//		}
//
//		if (!empty($ret['error'])) {
//			echo '<div class="primer_popup popup_error"><h3>';
//			echo $ret['error'];
//			echo '</h3></div>';
//		} else {
//			echo '<div class="primer_popup popup_success"><h3>';
//			_e('Test email sent successfully', 'primer');
//			echo '</h3></div>';
//		}

	}

	public function primer_mail_sender($send_to_mail, $mail_subject, $mail_message, $attachments) {
		$response = array();
		//check if SMTP credentials have been configured.
		if ( ! $this->credentials_configured() ) {
			return;
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
			$mail->IsSMTP();

			$charset       = get_bloginfo( 'charset' );
			$mail->CharSet = $charset;
			// send plain text test email
			$mail->ContentType = 'text/html';
			$mail->IsHTML( true );


			/* If using smtp auth, set the username & password */
			if ( 'yes' === $this->opts['smtp_settings']['authentication'] ) {
				$mail->SMTPAuth = true;
				if (!empty($this->opts['smtp_settings']['username'])) {
					$mail->Username = $this->opts['smtp_settings']['username'];
				}
				$mail->Password = $this->get_password();
			}

			/* Set the SMTPSecure value, if set to none, leave this blank */
			if ( 'none' !== $this->opts['smtp_settings']['type_encryption'] ) {
				$mail->SMTPSecure = $this->opts['smtp_settings']['type_encryption'];
			} else {
				$mail->SMTPSecure = 'ssl';
			}

			/* PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate. */
			$mail->SMTPAutoTLS = true;

			/* Set the other options */
			if (!empty($this->opts['smtp_settings']['smtp_server'])) {
				$mail->Host = $this->opts['smtp_settings']['smtp_server'];
			}

			if (!empty($this->opts['smtp_settings']['port'])) {
				$mail->Port = $this->opts['smtp_settings']['port'];
			}
			$request_port = isset($_POST['primer_smtp_port']) ? $_POST['primer_smtp_port'] : '';
			if (!empty($this->opts['smtp_settings']['port']) && !empty($request_host)) {
				$mail->Port = $request_port;
			}


			$send_from_mail = $this->opts['from_email_field'];
			$from_name  = get_bloginfo( 'name' );
			$mail->SetFrom( $send_from_mail, $from_name );

			$mail->Subject = $mail_subject;
			$mail->Body    = $mail_message;
			$mail->AddAddress( $send_to_mail, 'User Name' );

			$mail->addAttachment($attachments);

			global $debug_msg;
			$debug_msg         = '';
			$mail->Debugoutput = function ( $str, $level ) {
				global $debug_msg;
				$debug_msg .= $str;
			};
			$mail->SMTPDebug   = 2;
			//set reasonable timeout
			$mail->Timeout = 10;

			/* Send mail and return result */
			$mail->Send();

			$mail->ClearAddresses();
			$mail->ClearAllRecipients();

		} catch ( \Exception $e ) {
			$response['error'] = $mail->ErrorInfo;
		} catch ( \Throwable $e ) {
			$response['error'] = $mail->ErrorInfo;
		}
		$response['debug_log'] = $debug_msg;

		if (!empty($response['error'])) {
			echo '<div class="primer_popup popup_error"><h3>';
			echo $response['error'];
			echo '</h3></div>';
		}
		else {
			echo '<div class="primer_popup popup_success"><h3>';
			_e('Test email sent successfully', 'primer');
			echo '</h3></div>';
		}

		return $response;
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
			$from_email_request = isset($_POST['primer_from_email']) ? $_POST['primer_from_email'] : '';

			if (!empty($this->opts['from_email_field'])) {
				$from_email = $this->opts['from_email_field'];
			} else {
				if (!empty($from_email_request)) {
					$from_email = $from_email_request;
				}
			}

			$mail->IsSMTP();


			// send plain text test email
			$mail->ContentType = 'text/html';
			$mail->IsHTML( true );

			$mail->SMTPAuth = true;
			/* If using smtp auth, set the username & password */
			if ( 'yes' === $this->opts['smtp_settings']['authentication'] || 'yes' === $_POST['primer_smtp_authentication'] ) {

				$request_username = isset($_POST['primer_smtp_username']) ? $_POST['primer_smtp_username'] : '';
				if (!empty($this->opts['smtp_settings']['username'])) {
					$mail->Username = $this->opts['smtp_settings']['username'];
				} else {
					if (!empty($request_username)) {
						$mail->Username = $request_username;
					}
				}

				$mail->Password = $this->get_password();

			}

			/* Set the SMTPSecure value, if set to none, leave this blank */
			if ( 'none' !== $this->opts['smtp_settings']['type_encryption'] ) {
				$mail->SMTPSecure = $this->opts['smtp_settings']['type_encryption'];
			} else {
				$mail->SMTPSecure = 'ssl';
			}


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
			if (!empty($this->opts['smtp_settings']['smtp_server'])) {
				$mail->Host = $this->opts['smtp_settings']['smtp_server'];
			}
			$request_host = isset($_POST['primer_smtp_host']) ? $_POST['primer_smtp_host'] : '';
			if (!empty($this->opts['smtp_settings']['smtp_server']) && !empty($request_host)) {
				$mail->Host = $request_host;
			}

			if (!empty($this->opts['smtp_settings']['port'])) {
				$mail->Port = $this->opts['smtp_settings']['port'];
			}
			$request_port = isset($_POST['primer_smtp_port']) ? $_POST['primer_smtp_port'] : '';
			if (!empty($this->opts['smtp_settings']['port']) && !empty($request_host)) {
				$mail->Port = $request_port;
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
			$mail->SMTPDebug   = 2;
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
			echo '<div class="primer_popup popup_error"><h3>';
			echo $ret['error'];
			echo '</h3></div>';
		}
		else {
			echo '<div class="primer_popup popup_success"><h3>';
			_e('Test email sent successfully', 'primer');
			echo '</h3></div>';
		}

		return $ret;
	}

	public function admin_notices() {
		if (! $this->credentials_configured()) {
			$settings_url = admin_url() . 'admin.php?page=primer_settings&tab=emails'; ?>
			<div class="error">
				<p>
					<?php
					printf( __( 'Please configure your SMTP credentials in the <a href="%s">settings menu</a> in order to send email using SMTP.', 'primer' ), esc_url( $settings_url ) );
					?>
				</p>
			</div>
		<?php }

	}

	public function get_password() {
		$request_password = isset($_POST['primer_smtp_password']) ? $_POST['primer_smtp_password'] : '';
		$temp_password = isset( $this->opts['smtp_settings']['password'] ) ? $this->opts['smtp_settings']['password'] : $request_password;
		if ( '' === $temp_password ) {
			return '';
		}

		try {

			if ( get_option('primer_pass_encrypted') ) {
				// this is encrypted password
				$cryptor = Primer_SMTP_Utils::get_instance();
				$decrypted = $cryptor->decrypt_password( $temp_password );
				//check if encryption option is disabled
				if ( empty( $this->opts['smtp_settings']['encrypt_pass'] ) ) {
					//it is. let's save decrypted password
					$this->opts['smtp_settings']['password'] = $this->encrypt_password( addslashes( $decrypted ) );
					update_option('primer_emails', $this->opts);
				}
				return $decrypted;
			}
		} catch ( Exception $e ) {
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

		if ( empty( $this->opts['smtp_settings']['encrypt_pass'] ) || ! extension_loaded( 'openssl' ) ) {
			// no openssl extension loaded - we can't encrypt the password
			$password = base64_encode( $pass ); //phpcs:ignore
			update_option( 'primer_pass_encrypted', false );
		} else {
			// let's encrypt password
			$cryptor  = Primer_SMTP_Utils::get_instance();
			$password = $cryptor->encrypt_password( $pass );
			update_option( 'primer_pass_encrypted', true );
		}
		return $password;
	}

	public function credentials_configured() {
		$credentials_configured = true;
		if ( ! isset( $this->opts['from_email_field'] ) || empty( $this->opts['from_email_field'] ) ) {
			$credentials_configured = false;
		}
		return $credentials_configured;
	}
}

PrimerSMTP::get_instance();

add_action('wp_ajax_test_send_form', 'test_send_form');
function test_send_form() {
	var_dump($_POST);
	wp_die();
}
