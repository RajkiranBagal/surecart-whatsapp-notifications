<?php
/**
 * Encryption helper for API credentials.
 *
 * @package SCWhatsApp\Provider
 */

namespace SCWhatsApp\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Encryption
 *
 * Encrypts/decrypts values using AES-256-CTR with OpenSSL.
 */
final class Encryption {

	/**
	 * Encryption method.
	 *
	 * @var string
	 */
	private const METHOD = 'aes-256-ctr';

	/**
	 * Get the encryption key.
	 *
	 * @return string
	 */
	private static function get_key(): string {
		if ( defined( 'SCWA_ENCRYPTION_KEY' ) && '' !== SCWA_ENCRYPTION_KEY ) {
			return SCWA_ENCRYPTION_KEY;
		}
		if ( defined( 'LOGGED_IN_KEY' ) && '' !== LOGGED_IN_KEY ) {
			return LOGGED_IN_KEY;
		}
		return 'scwa-default-encryption-key';
	}

	/**
	 * Get the encryption salt.
	 *
	 * @return string
	 */
	private static function get_salt(): string {
		if ( defined( 'SCWA_ENCRYPTION_SALT' ) && '' !== SCWA_ENCRYPTION_SALT ) {
			return SCWA_ENCRYPTION_SALT;
		}
		if ( defined( 'LOGGED_IN_SALT' ) && '' !== LOGGED_IN_SALT ) {
			return LOGGED_IN_SALT;
		}
		return 'scwa-default-encryption-salt';
	}

	/**
	 * Encrypt a value.
	 *
	 * @param string $value Plain text value.
	 * @return string Encrypted value (base64 encoded).
	 */
	public static function encrypt( string $value ): string {
		if ( ! extension_loaded( 'openssl' ) ) {
			return $value;
		}

		$key    = self::get_key();
		$salt   = self::get_salt();
		$ivlen  = openssl_cipher_iv_length( self::METHOD );
		$iv     = openssl_random_pseudo_bytes( $ivlen );
		$raw    = openssl_encrypt( $value . $salt, self::METHOD, $key, 0, $iv );

		if ( false === $raw ) {
			return $value;
		}

		return base64_encode( $iv . $raw ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt a value.
	 *
	 * @param string $encrypted_value Encrypted value (base64 encoded).
	 * @return string|false Decrypted value or false on failure.
	 */
	public static function decrypt( string $encrypted_value ) {
		if ( ! extension_loaded( 'openssl' ) ) {
			return $encrypted_value;
		}

		$key   = self::get_key();
		$salt  = self::get_salt();
		$ivlen = openssl_cipher_iv_length( self::METHOD );

		$raw = base64_decode( $encrypted_value, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $raw ) {
			return false;
		}

		$iv        = substr( $raw, 0, $ivlen );
		$encrypted = substr( $raw, $ivlen );
		$decrypted = openssl_decrypt( $encrypted, self::METHOD, $key, 0, $iv );

		if ( false === $decrypted ) {
			return false;
		}

		if ( substr( $decrypted, -strlen( $salt ) ) !== $salt ) {
			return false;
		}

		return substr( $decrypted, 0, -strlen( $salt ) );
	}
}
