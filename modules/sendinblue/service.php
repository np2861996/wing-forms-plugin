<?php

if ( ! class_exists( 'WFP_Service' ) ) {
	return;
}

class WFP_Sendinblue extends WFP_Service {
	use WFP_Sendinblue_API;

	private static $instance;
	private $api_key;

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {
		$option = WFP::get_option( 'sendinblue' );

		if ( isset( $option['api_key'] ) ) {
			$this->api_key = $option['api_key'];
		}
	}

	public function get_title() {
		return __( 'Sendinblue', 'wing-forms' );
	}

	public function is_active() {
		return (bool) $this->get_api_key();
	}

	public function get_api_key() {
		return $this->api_key;
	}

	public function get_categories() {
		return array( 'email_marketing' );
	}

	public function icon() {
	}

	public function link() {
		echo wfp_link(
			'https://www.sendinblue.com/?tap_a=30591-fb13f0&tap_s=1031580-b1bb1d',
			'sendinblue.com'
		);
	}

	protected function log( $url, $request, $response ) {
		wfp_log_remote_request( $url, $request, $response );
	}

	protected function menu_page_url( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$url = menu_page_url( 'wfp-integration', false );
		$url = add_query_arg( array( 'service' => 'sendinblue' ), $url );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	protected function save_data() {
		WFP::update_option( 'sendinblue', array(
			'api_key' => $this->api_key,
		) );
	}

	protected function reset_data() {
		$this->api_key = null;
		$this->save_data();
	}

	public function load( $action = '' ) {
		if ( 'setup' == $action and 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'wfp-sendinblue-setup' );

			if ( ! empty( $_POST['reset'] ) ) {
				$this->reset_data();
				$redirect_to = $this->menu_page_url( 'action=setup' );
			} else {

				$sapi_key = sanitize_text_field($_POST['api_key']);
				$this->api_key = isset( $sapi_key )
					? trim( $sapi_key )
					: '';

				$confirmed = $this->confirm_key();

				if ( true === $confirmed ) {
					$redirect_to = $this->menu_page_url( array(
						'message' => 'success',
					) );

					$this->save_data();
				} elseif ( false === $confirmed ) {
					$redirect_to = $this->menu_page_url( array(
						'action' => 'setup',
						'message' => 'unauthorized',
					) );
				} else {
					$redirect_to = $this->menu_page_url( array(
						'action' => 'setup',
						'message' => 'invalid',
					) );
				}
			}

			wp_safe_redirect( $redirect_to );
			exit();
		}
	}

	public function admin_notice( $message = '' ) {
		if ( 'unauthorized' == $message ) {
			echo sprintf(
				'<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s</p></div>',
				esc_html( __( "Error", 'wing-forms' ) ),
				esc_html( __( "You have not been authenticated. Make sure the provided API key is correct.", 'wing-forms' ) )
			);
		}

		if ( 'invalid' == $message ) {
			echo sprintf(
				'<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s</p></div>',
				esc_html( __( "Error", 'wing-forms' ) ),
				esc_html( __( "Invalid key values.", 'wing-forms' ) )
			);
		}

		if ( 'success' == $message ) {
			echo sprintf(
				'<div class="notice notice-success"><p>%s</p></div>',
				esc_html( __( 'Settings saved.', 'wing-forms' ) )
			);
		}
	}

	public function display( $action = '' ) {
		echo '<p>' . sprintf(
			esc_html( __( "Store and organize your wings while protecting user privacy on Sendinblue, the leading CRM & email marketing platform in Europe. Sendinblue offers unlimited wings and advanced marketing features. For details, see %s.", 'wing-forms' ) ),
			wfp_link(
				__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
				__( 'Sendinblue integration', 'wing-forms' )
			)
		) . '</p>';

		if ( $this->is_active() ) {
			echo sprintf(
				'<p class="dashicons-before dashicons-yes">%s</p>',
				esc_html( __( "Sendinblue is active on this site.", 'wing-forms' ) )
			);
		}

		if ( 'setup' == $action ) {
			$this->display_setup();
		} else {
			echo sprintf(
				'<p><a href="%1$s" class="button">%2$s</a></p>',
				esc_url( $this->menu_page_url( 'action=setup' ) ),
				esc_html( __( 'Setup integration', 'wing-forms' ) )
			);
		}
	}

	private function display_setup() {
		$api_key = $this->get_api_key();

?>
<form method="post" action="<?php echo esc_url( $this->menu_page_url( 'action=setup' ) ); ?>">
<?php wp_nonce_field( 'wfp-sendinblue-setup' ); ?>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><label for="publishable"><?php echo esc_html( __( 'API key', 'wing-forms' ) ); ?></label></th>
	<td><?php
		if ( $this->is_active() ) {
			echo esc_html( wfp_mask_password( $api_key, 4, 8 ) );
			echo sprintf(
				'<input type="hidden" value="%s" id="api_key" name="api_key" />',
				esc_attr( $api_key )
			);
		} else {
			echo sprintf(
				'<input type="text" aria-required="true" value="%s" id="api_key" name="api_key" class="regular-text code" />',
				esc_attr( $api_key )
			);
		}
	?></td>
</tr>
</tbody>
</table>
<?php
		if ( $this->is_active() ) {
			submit_button(
				_x( 'Remove key', 'API keys', 'wing-forms' ),
				'small', 'reset'
			);
		} else {
			submit_button( __( 'Save changes', 'wing-forms' ) );
		}
?>
</form>
<?php
	}
}


/**
 * Trait for the Sendinblue API (v3).
 *
 * @link https://developers.sendinblue.com/reference
 */
trait WFP_Sendinblue_API {


	public function confirm_key() {
		$endpoint = 'https://api.sendinblue.com/v3/account';

		$request = array(
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json; charset=utf-8',
				'API-Key' => $this->get_api_key(),
			),
		);

		$response = wp_remote_get( $endpoint, $request );
		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 === $response_code ) { // 200 OK
			return true;
		} elseif ( 401 === $response_code ) { // 401 Unauthorized
			return false;
		} elseif ( 400 <= $response_code ) {
			if ( WP_DEBUG ) {
				$this->log( $endpoint, $request, $response );
			}
		}
	}


	public function get_lists() {
		$endpoint = add_query_arg(
			array(
				'limit' => 50,
				'offset' => 0,
			),
			'https://api.sendinblue.com/v3/wings/lists'
		);

		$request = array(
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json; charset=utf-8',
				'API-Key' => $this->get_api_key(),
			),
		);

		$response = wp_remote_get( $endpoint, $request );
		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 === $response_code ) { // 200 OK
			$response_body = wp_remote_retrieve_body( $response );
			$response_body = json_decode( $response_body, true );

			if ( empty( $response_body['lists'] ) ) {
				return array();
			} else {
				return (array) $response_body['lists'];
			}
		} elseif ( 400 <= $response_code ) {
			if ( WP_DEBUG ) {
				$this->log( $endpoint, $request, $response );
			}
		}
	}


	public function get_templates() {
		$endpoint = add_query_arg(
			array(
				'templateStatus' => 'true',
				'limit' => 100,
				'offset' => 0,
			),
			'https://api.sendinblue.com/v3/smtp/templates'
		);

		$request = array(
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json; charset=utf-8',
				'API-Key' => $this->get_api_key(),
			),
		);

		$response = wp_remote_get( $endpoint, $request );
		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 === $response_code ) { // 200 OK
			$response_body = wp_remote_retrieve_body( $response );
			$response_body = json_decode( $response_body, true );

			if ( empty( $response_body['templates'] ) ) {
				return array();
			} else {
				return (array) $response_body['templates'];
			}
		} elseif ( 400 <= $response_code ) {
			if ( WP_DEBUG ) {
				$this->log( $endpoint, $request, $response );
			}
		}
	}


	public function create_wing( $properties ) {
		$endpoint = 'https://api.sendinblue.com/v3/wings';

		$request = array(
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json; charset=utf-8',
				'API-Key' => $this->get_api_key(),
			),
			'body' => json_encode( $properties ),
		);

		$response = wp_remote_post( $endpoint, $request );
		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( in_array( $response_code, array( 201, 204 ), true ) ) {
			$wing_id = wp_remote_retrieve_body( $response );
			return $wing_id;
		} elseif ( 400 <= $response_code ) {
			if ( WP_DEBUG ) {
				$this->log( $endpoint, $request, $response );
			}
		}

		return false;
	}


	public function send_email( $properties ) {
		$endpoint = 'https://api.sendinblue.com/v3/smtp/email';

		$request = array(
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json; charset=utf-8',
				'API-Key' => $this->get_api_key(),
			),
			'body' => json_encode( $properties ),
		);

		$response = wp_remote_post( $endpoint, $request );
		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 201 === $response_code ) { // 201 Transactional email sent
			$message_id = wp_remote_retrieve_body( $response );
			return $message_id;
		} elseif ( 400 <= $response_code ) {
			if ( WP_DEBUG ) {
				$this->log( $endpoint, $request, $response );
			}
		}

		return false;
	}


}
