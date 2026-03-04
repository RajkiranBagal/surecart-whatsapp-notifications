<?php
/**
 * Refund data extractor.
 *
 * @package SCWhatsApp\DataExtractor
 */

namespace SCWhatsApp\DataExtractor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RefundDataExtractor
 *
 * Extracts template variables from a SureCart Refund model.
 */
class RefundDataExtractor {

	/**
	 * Extract template variables from a Refund object.
	 *
	 * @param object $refund SureCart Refund model.
	 * @return array|null Associative array of template variables, or null on failure.
	 */
	public static function extract( $refund ): ?array {
		// Navigate: refund -> charge -> checkout -> order.
		$charge = $refund->getAttribute( 'charge' );

		if ( empty( $charge ) || ! is_object( $charge ) ) {
			// Try to re-fetch the refund with expanded relations.
			try {
				$refund_id = $refund->getAttribute( 'id' );
				$refund    = \SureCart\Models\Refund::with( array( 'charge', 'charge.checkout', 'charge.checkout.customer' ) )->find( $refund_id );
				$charge    = $refund->getAttribute( 'charge' );
			} catch ( \Exception $e ) {
				return null;
			}
		}

		if ( empty( $charge ) || ! is_object( $charge ) ) {
			return null;
		}

		$checkout = $charge->getAttribute( 'checkout' );

		if ( empty( $checkout ) || ! is_object( $checkout ) ) {
			return null;
		}

		// Get order ID from checkout.
		$order_attr = $checkout->getAttribute( 'order' );
		$order_id   = is_object( $order_attr ) ? $order_attr->id : $order_attr;

		// Extract base data from checkout.
		$data = CheckoutDataExtractor::extract( $checkout );

		// Add refund-specific fields.
		$refund_amount  = $refund->getAttribute( 'amount' );
		$currency       = $checkout->getAttribute( 'currency' ) ?? 'usd';

		$data['refund_amount'] = self::format_amount( $refund_amount, $currency );
		$data['refund_id']     = $refund->getAttribute( 'id' ) ?? '';
		$data['order_id']      = is_string( $order_id ) ? $order_id : '';

		return $data;
	}

	/**
	 * Format a SureCart amount (in cents) to display string.
	 *
	 * @param int|null $amount   Amount in smallest currency unit.
	 * @param string   $currency Currency code.
	 * @return string
	 */
	private static function format_amount( $amount, string $currency = 'usd' ): string {
		if ( null === $amount || '' === $amount ) {
			return '0.00';
		}

		$amount   = (int) $amount;
		$currency = strtoupper( $currency );

		$zero_decimal = array( 'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF' );

		if ( in_array( $currency, $zero_decimal, true ) ) {
			return number_format( $amount );
		}

		return number_format( $amount / 100, 2 );
	}
}
