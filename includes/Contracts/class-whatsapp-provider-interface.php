<?php
/**
 * WhatsApp provider interface.
 *
 * @package SCWhatsApp\Contracts
 */

namespace SCWhatsApp\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface WhatsAppProviderInterface
 *
 * All WhatsApp API providers must implement this.
 */
interface WhatsAppProviderInterface {

	/**
	 * Send a plain text message.
	 *
	 * @param string $to   Recipient phone in E.164 format.
	 * @param string $body Message text.
	 * @return array{success: bool, message_id: string, error: string, raw_response: array}
	 */
	public function send_text( string $to, string $body ): array;

	/**
	 * Send a pre-approved Meta template message.
	 *
	 * @param string $to     Recipient phone in E.164 format.
	 * @param string $name   Template name.
	 * @param string $lang   Language code.
	 * @param array  $params Template parameters.
	 * @return array{success: bool, message_id: string, error: string, raw_response: array}
	 */
	public function send_template( string $to, string $name, string $lang, array $params ): array;

	/**
	 * Verify API connection by fetching business profile.
	 *
	 * @return array{success: bool, data: array, error: string}
	 */
	public function verify_connection(): array;
}
