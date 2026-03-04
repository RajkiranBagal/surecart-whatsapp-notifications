<?php
/**
 * Fulfillment data extractor.
 *
 * @package SCWhatsApp\DataExtractor
 */

namespace SCWhatsApp\DataExtractor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FulfillmentDataExtractor
 *
 * Extracts template variables from a SureCart Fulfillment model.
 */
class FulfillmentDataExtractor {

	/**
	 * Extract template variables from a Fulfillment object.
	 *
	 * @param object $fulfillment SureCart Fulfillment model.
	 * @return array|null Associative array of template variables, or null on failure.
	 */
	public static function extract( $fulfillment ): ?array {
		$order_id = $fulfillment->getAttribute( 'order' );

		if ( empty( $order_id ) || ! is_string( $order_id ) ) {
			return null;
		}

		// Re-fetch order with expanded relations.
		try {
			$order = \SureCart\Models\Order::with( array( 'checkout', 'checkout.customer' ) )->find( $order_id );
		} catch ( \Exception $e ) {
			return null;
		}

		if ( empty( $order ) ) {
			return null;
		}

		$checkout = is_object( $order ) ? $order->getAttribute( 'checkout' ) : null;

		if ( empty( $checkout ) || ! is_object( $checkout ) ) {
			return null;
		}

		// Extract base data from checkout.
		$data = CheckoutDataExtractor::extract( $checkout );

		// Add fulfillment-specific fields.
		$tracking_number = $fulfillment->getAttribute( 'tracking_number' ) ?? '';
		$tracking_url    = $fulfillment->getAttribute( 'tracking_url' ) ?? '';

		$data['tracking_number'] = $tracking_number;
		$data['tracking_url']    = $tracking_url;
		$data['order_id']        = $order_id;

		// Override order number from the order object if available.
		$number = is_object( $order ) ? $order->getAttribute( 'number' ) : null;
		if ( ! empty( $number ) ) {
			$data['order_number'] = $number;
		}

		return $data;
	}
}
