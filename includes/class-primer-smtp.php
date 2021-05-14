<?php

class PrimerSMTP {

	public $opts;
	protected static $instance = null;

	public function __construct() {

		add_filter( 'wp_mail', array( $this, 'wp_mail' ), 2147483647 );
		add_action( 'phpmailer_init', array( $this, 'init_smtp' ), 999 );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init_smtp( &$phpmailer ) {
		//check if SMTP credentials have been configured.

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

			$from_email = $this->opts['send_from'];

			$mail->IsSMTP();


			// send plain text test email
			$mail->ContentType = 'text/plain';
			$mail->IsHTML( false );

			/* If using smtp auth, set the username & password */
			if ( 'yes' === $this->opts['autentication'] ) {
				$mail->SMTPAuth = true;
				$mail->Username = $this->opts['username'];
//				$mail->Password = $this->get_password();
			}


			/* PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate. */
			$mail->SMTPAutoTLS = false;

			// Insecure SSL option enabled
			$mail->SMTPOptions = array(
				'ssl' => array(
					'verify_peer'       => false,
					'verify_peer_name'  => false,
					'allow_self_signed' => true,
				),
			);

			/* Set the other options */
			$mail->Host = $this->opts['smtp_server'];
			$mail->Port = $this->opts['port'];

			//Add reply-to if set in settings.
			if ( ! empty( $this->opts['reply_to_email'] ) ) {
				$mail->AddReplyTo( $this->opts['reply_to_email'], $from_email );
			}

			$mail->SetFrom( $from_email );
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

		return $ret;
	}
}

PrimerSMTP::get_instance();
