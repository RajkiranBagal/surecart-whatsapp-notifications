<?php
/**
 * Notification dispatcher — central orchestrator.
 *
 * @package SCWhatsApp\Service
 */

namespace SCWhatsApp\Service;

use SCWhatsApp\Provider\Encryption;
use SCWhatsApp\Provider\MetaCloudApiProvider;
use SCWhatsApp\Logger\NotificationLogger;
use SCWhatsApp\DataExtractor\CheckoutDataExtractor;
use SCWhatsApp\DataExtractor\FulfillmentDataExtractor;
use SCWhatsApp\DataExtractor\RefundDataExtractor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NotificationDispatcher
 */
class NotificationDispatcher {

	/**
	 * Register SureCart hooks.
	 */
	public function register_hooks(): void {
		// Checkout confirmed — customer + admin.
		add_action( 'surecart/checkout_confirmed', array( $this, 'handle_checkout_confirmed' ), 20 );

		// Fulfillment created — customer (model event + webhook event).
		add_action( 'surecart/models/fulfillment/created', array( $this, 'handle_fulfillment_created' ), 20 );
		add_action( 'surecart/fulfillment_created', array( $this, 'handle_fulfillment_created' ), 20 );

		// Refund created — customer (model event + webhook event).
		add_action( 'surecart/models/refund/created', array( $this, 'handle_refund_created' ), 20 );
		add_action( 'surecart/refund_created', array( $this, 'handle_refund_created' ), 20 );
	}

	/**
	 * Handle checkout confirmed event.
	 *
	 * @param object $checkout SureCart Checkout model.
	 */
	public function handle_checkout_confirmed( $checkout ): void {
		if ( ! $this->is_configured() ) {
			return;
		}

		$data = CheckoutDataExtractor::extract( $checkout );
		if ( empty( $data ) ) {
			return;
		}

		$order_id = $checkout->getAttribute( 'order' );
		if ( is_object( $order_id ) ) {
			$order_id = $order_id->id ?? '';
		}
		$order_id = (string) $order_id;

		// Customer notification.
		if ( $this->is_enabled( 'scwa_enable_order_confirmed' ) ) {
			if ( ! NotificationLogger::has_recent( 'checkout_confirmed', $order_id ) ) {
				$this->send_notification(
					'checkout_confirmed',
					'customer',
					$data['customer_phone'],
					$data,
					$order_id
				);
			}
		}

		// Admin notification.
		if ( $this->is_enabled( 'scwa_enable_admin_new_order' ) ) {
			$admin_phone = get_option( 'scwa_admin_phone', '' );
			if ( ! empty( $admin_phone ) && ! NotificationLogger::has_recent( 'admin_new_order', $order_id ) ) {
				$this->send_notification(
					'admin_new_order',
					'admin',
					$admin_phone,
					$data,
					$order_id
				);
			}
		}
	}

	/**
	 * Handle fulfillment created event.
	 *
	 * @param object $fulfillment SureCart Fulfillment model.
	 */
	public function handle_fulfillment_created( $fulfillment ): void {
		if ( ! $this->is_configured() || ! $this->is_enabled( 'scwa_enable_fulfillment_created' ) ) {
			return;
		}

		$data = FulfillmentDataExtractor::extract( $fulfillment );
		if ( empty( $data ) ) {
			return;
		}

		$order_id = $data['order_id'] ?? '';

		if ( ! empty( $order_id ) && NotificationLogger::has_recent( 'fulfillment_created', $order_id ) ) {
			return;
		}

		$this->send_notification(
			'fulfillment_created',
			'customer',
			$data['customer_phone'],
			$data,
			$order_id
		);
	}

	/**
	 * Handle refund created event.
	 *
	 * @param object $refund SureCart Refund model.
	 */
	public function handle_refund_created( $refund ): void {
		if ( ! $this->is_configured() || ! $this->is_enabled( 'scwa_enable_refund_created' ) ) {
			return;
		}

		$data = RefundDataExtractor::extract( $refund );
		if ( empty( $data ) ) {
			return;
		}

		$order_id = $data['order_id'] ?? '';

		if ( ! empty( $order_id ) && NotificationLogger::has_recent( 'refund_created', $order_id ) ) {
			return;
		}

		$this->send_notification(
			'refund_created',
			'customer',
			$data['customer_phone'],
			$data,
			$order_id
		);
	}

	/**
	 * Send a notification through the pipeline.
	 *
	 * @param string $event_type     Event type.
	 * @param string $recipient_type Recipient type (customer|admin).
	 * @param string $phone          Raw phone number.
	 * @param array  $variables      Template variables.
	 * @param string $order_id       Order/checkout ID.
	 */
	private function send_notification(
		string $event_type,
		string $recipient_type,
		string $phone,
		array $variables,
		string $order_id
	): void {
		$default_country = get_option( 'scwa_default_country_code', '91' );
		$normalized      = PhoneNormalizer::normalize( $phone, $default_country );

		// Skip if phone is missing or invalid.
		if ( empty( $normalized ) ) {
			NotificationLogger::log(
				array(
					'event_type'      => $event_type,
					'recipient_phone' => $phone,
					'recipient_type'  => $recipient_type,
					'order_id'        => $order_id,
					'status'          => 'skipped',
					'error_message'   => 'Invalid or missing phone number.',
				)
			);
			return;
		}

		// Render the message.
		$rendered = TemplateRenderer::render( $event_type, $recipient_type, $variables );
		$provider = $this->get_provider();

		if ( ! $provider ) {
			NotificationLogger::log(
				array(
					'event_type'      => $event_type,
					'recipient_phone' => $normalized,
					'recipient_type'  => $recipient_type,
					'order_id'        => $order_id,
					'status'          => 'failed',
					'error_message'   => 'API provider not configured.',
				)
			);
			return;
		}

		// Send via provider.
		if ( 'meta_template' === $rendered['type'] ) {
			$result = $provider->send_template(
				$normalized,
				$rendered['template_name'],
				$rendered['template_lang'],
				$rendered['params']
			);
		} else {
			$result = $provider->send_text( $normalized, $rendered['body'] );
		}

		// Log the result.
		NotificationLogger::log(
			array(
				'event_type'      => $event_type,
				'recipient_phone' => $normalized,
				'recipient_type'  => $recipient_type,
				'order_id'        => $order_id,
				'template_name'   => $rendered['template_name'] ? $rendered['template_name'] : $event_type . '_' . $recipient_type,
				'message_body'    => $rendered['body'],
				'status'          => $result['success'] ? 'sent' : 'failed',
				'api_message_id'  => $result['message_id'] ?? '',
				'api_response'    => $result['raw_response'] ?? array(),
				'error_message'   => $result['error'] ?? '',
			)
		);
	}

	/**
	 * Check if API credentials are configured.
	 *
	 * @return bool
	 */
	private function is_configured(): bool {
		$phone_id = get_option( 'scwa_phone_number_id', '' );
		$token    = get_option( 'scwa_api_access_token', '' );

		return ! empty( $phone_id ) && ! empty( $token );
	}

	/**
	 * Check if a notification type is enabled.
	 *
	 * @param string $option_key Option key.
	 * @return bool
	 */
	private function is_enabled( string $option_key ): bool {
		return '1' === get_option( $option_key, '0' );
	}

	/**
	 * Get the WhatsApp API provider instance.
	 *
	 * @return MetaCloudApiProvider|null
	 */
	private function get_provider(): ?MetaCloudApiProvider {
		$phone_id    = get_option( 'scwa_phone_number_id', '' );
		$token_enc   = get_option( 'scwa_api_access_token', '' );
		$api_version = get_option( 'scwa_api_version', 'v21.0' );

		if ( empty( $phone_id ) || empty( $token_enc ) ) {
			return null;
		}

		$token = Encryption::decrypt( $token_enc );
		if ( false === $token || empty( $token ) ) {
			return null;
		}

		return new MetaCloudApiProvider( $phone_id, $token, $api_version );
	}
}
