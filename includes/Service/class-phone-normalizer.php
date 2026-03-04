<?php
/**
 * Phone number normalizer.
 *
 * @package SCWhatsApp\Service
 */

namespace SCWhatsApp\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PhoneNormalizer
 *
 * Normalizes phone numbers to E.164 format.
 */
class PhoneNormalizer {

	/**
	 * Normalize a phone number to E.164 format.
	 *
	 * @param string $phone                Raw phone number.
	 * @param string $default_country_code Default country code (without +).
	 * @return string|null Normalized phone or null if invalid.
	 */
	public static function normalize( string $phone, string $default_country_code = '91' ): ?string {
		// Strip all non-numeric characters except leading +.
		$cleaned = preg_replace( '/[^\d+]/', '', trim( $phone ) );

		if ( empty( $cleaned ) ) {
			return null;
		}

		// Already in E.164 format.
		if ( self::is_valid_e164( $cleaned ) ) {
			return $cleaned;
		}

		// Remove leading + if present but number is too short.
		$cleaned = ltrim( $cleaned, '+' );

		if ( empty( $cleaned ) ) {
			return null;
		}

		// If starts with 0, remove leading zero and prepend country code.
		if ( '0' === $cleaned[0] ) {
			$cleaned = substr( $cleaned, 1 );
		}

		// Prepend country code if the number doesn't appear to have one.
		// Heuristic: if number is <= 10 digits, it likely needs a country code.
		if ( strlen( $cleaned ) <= 10 ) {
			$cleaned = $default_country_code . $cleaned;
		}

		$normalized = '+' . $cleaned;

		return self::is_valid_e164( $normalized ) ? $normalized : null;
	}

	/**
	 * Check if a phone number is in valid E.164 format.
	 *
	 * E.164: + followed by 7-15 digits.
	 *
	 * @param string $phone Phone number to validate.
	 * @return bool
	 */
	public static function is_valid_e164( string $phone ): bool {
		return (bool) preg_match( '/^\+[1-9]\d{6,14}$/', $phone );
	}
}
