<?php
/**
 * Checkout data extractor.
 *
 * @package SCWhatsApp\DataExtractor
 */

namespace SCWhatsApp\DataExtractor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CheckoutDataExtractor
 *
 * Extracts template variables from a SureCart Checkout model.
 */
class CheckoutDataExtractor {

	/**
	 * Extract template variables from a Checkout object.
	 *
	 * @param object $checkout SureCart Checkout model.
	 * @return array Associative array of template variables.
	 */
	public static function extract( $checkout ): array {
		$customer_name  = '';
		$customer_email = '';
		$customer_phone = '';
		$first_name     = '';

		// Try to get customer data.
		$customer = $checkout->getAttribute( 'customer' );
		if ( is_object( $customer ) ) {
			$customer_name  = $customer->getAttribute( 'name' ) ?? '';
			$customer_email = $customer->getAttribute( 'email' ) ?? '';
			$customer_phone = $customer->getAttribute( 'phone' ) ?? '';
		} elseif ( is_string( $customer ) ) {
			// Customer is a string ID — try checkout-level fields.
			$customer_name  = $checkout->getAttribute( 'name' ) ?? '';
			$customer_email = $checkout->getAttribute( 'email' ) ?? '';
			$customer_phone = $checkout->getAttribute( 'phone' ) ?? '';
		}

		// Fallback: direct checkout properties.
		if ( empty( $customer_phone ) ) {
			$customer_phone = $checkout->getAttribute( 'phone' ) ?? '';
		}
		if ( empty( $customer_email ) ) {
			$customer_email = $checkout->getAttribute( 'email' ) ?? '';
		}
		if ( empty( $customer_name ) ) {
			$customer_name = $checkout->getAttribute( 'name' ) ?? '';
		}

		// Extract first name from full name.
		if ( ! empty( $customer_name ) ) {
			$parts      = explode( ' ', $customer_name );
			$first_name = $parts[0];
		}

		// Amount formatting.
		$total_amount = $checkout->getAttribute( 'total_amount' );
		$currency     = $checkout->getAttribute( 'currency' ) ?? 'usd';
		$order_total  = self::format_amount( $total_amount, $currency );

		// Order number.
		$order_number = '';
		$order        = $checkout->getAttribute( 'order' );
		if ( is_object( $order ) ) {
			$order_number = $order->getAttribute( 'number' ) ?? '';
		}
		if ( empty( $order_number ) ) {
			$order_number = $checkout->getAttribute( 'number' ) ?? '';
		}

		return array(
			'customer_name'       => $customer_name,
			'customer_first_name' => $first_name,
			'customer_email'      => $customer_email,
			'customer_phone'      => $customer_phone,
			'order_number'        => $order_number,
			'order_total'         => $order_total,
			'currency'            => strtoupper( $currency ),
			'store_name'          => get_bloginfo( 'name' ),
			'checkout_url'        => home_url(),
		);
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

		// Zero-decimal currencies.
		$zero_decimal = array( 'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF' );

		if ( in_array( $currency, $zero_decimal, true ) ) {
			return number_format( $amount );
		}

		return number_format( $amount / 100, 2 );
	}
}
