<?php
/**
 * Meta WhatsApp Cloud API provider.
 *
 * @package SCWhatsApp\Provider
 */

namespace SCWhatsApp\Provider;

use SCWhatsApp\Contracts\WhatsAppProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MetaCloudApiProvider
 *
 * Implements WhatsApp messaging via the Meta Cloud API.
 */
class MetaCloudApiProvider implements WhatsAppProviderInterface {

	/**
	 * WhatsApp Phone Number ID.
	 *
	 * @var string
	 */
	private $phone_number_id;

	/**
	 * API access token.
	 *
	 * @var string
	 */
	private $access_token;

	/**
	 * API version (e.g., v21.0).
	 *
	 * @var string
	 */
	private $api_version;

	/**
	 * Constructor.
	 *
	 * @param string $phone_number_id WhatsApp Phone Number ID.
	 * @param string $access_token    Decrypted API access token.
	 * @param string $api_version     API version.
	 */
	public function __construct( string $phone_number_id, string $access_token, string $api_version = 'v21.0' ) {
		$this->phone_number_id = $phone_number_id;
		$this->access_token    = $access_token;
		$this->api_version     = $api_version;
	}

	/**
	 * Get the base API URL.
	 *
	 * @return string
	 */
	private function get_base_url(): string {
		return sprintf(
			'https://graph.facebook.com/%s/%s/messages',
			$this->api_version,
			$this->phone_number_id
		);
	}

	/**
	 * Send a plain text message.
	 *
	 * @param string $to   Recipient phone in E.164 format.
	 * @param string $body Message text.
	 * @return array
	 */
	public function send_text( string $to, string $body ): array {
		$payload = array(
			'messaging_product' => 'whatsapp',
			'recipient_type'    => 'individual',
			'to'                => $to,
			'type'              => 'text',
			'text'              => array(
				'preview_url' => false,
				'body'        => $body,
			),
		);

		return $this->make_request( $payload );
	}

	/**
	 * Send a pre-approved Meta template message.
	 *
	 * @param string $to     Recipient phone in E.164 format.
	 * @param string $name   Template name.
	 * @param string $lang   Language code.
	 * @param array  $params Template parameters.
	 * @return array
	 */
	public function send_template( string $to, string $name, string $lang, array $params ): array {
		$components = array();

		if ( ! empty( $params ) ) {
			$parameters = array();
			foreach ( $params as $value ) {
				$parameters[] = array(
					'type' => 'text',
					'text' => (string) $value,
				);
			}
			$components[] = array(
				'type'       => 'body',
				'parameters' => $parameters,
			);
		}

		$payload = array(
			'messaging_product' => 'whatsapp',
			'recipient_type'    => 'individual',
			'to'                => $to,
			'type'              => 'template',
			'template'          => array(
				'name'       => $name,
				'language'   => array(
					'code' => $lang,
				),
				'components' => $components,
			),
		);

		return $this->make_request( $payload );
	}

	/**
	 * Verify API connection by fetching business profile.
	 *
	 * @return array
	 */
	public function verify_connection(): array {
		$url = sprintf(
			'https://graph.facebook.com/%s/%s/whatsapp_business_profile?fields=about,address,description,vertical,websites',
			$this->api_version,
			$this->phone_number_id
		);

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->access_token,
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'data'    => array(),
				'error'   => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 && ! empty( $body['data'] ) ) {
			return array(
				'success' => true,
				'data'    => $body['data'],
				'error'   => '',
			);
		}

		$error_msg = isset( $body['error']['message'] ) ? $body['error']['message'] : 'Unknown API error';

		return array(
			'success' => false,
			'data'    => array(),
			'error'   => $error_msg,
		);
	}

	/**
	 * Make a POST request to the WhatsApp API.
	 *
	 * @param array $payload Request body.
	 * @return array
	 */
	private function make_request( array $payload ): array {
		$response = wp_remote_post(
			$this->get_base_url(),
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success'      => false,
				'message_id'   => '',
				'error'        => $response->get_error_message(),
				'raw_response' => array(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ( 200 === $code || 202 === $code ) && ! empty( $body['messages'][0]['id'] ) ) {
			return array(
				'success'      => true,
				'message_id'   => $body['messages'][0]['id'],
				'error'        => '',
				'raw_response' => $body,
			);
		}

		$error_msg = isset( $body['error']['message'] ) ? $body['error']['message'] : 'Unknown API error (HTTP ' . $code . ')';

		return array(
			'success'      => false,
			'message_id'   => '',
			'error'        => $error_msg,
			'raw_response' => $body ? $body : array(),
		);
	}
}
