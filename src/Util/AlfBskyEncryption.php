<?php
/**
 * Encryption utility class for ALF Bluesky Poster.
 *
 * @package ALF_Bsky_Poster.
 */

namespace AlfBsky\Util;

/**
 * Handles encryption and decryption of sensitive data.
 */
class AlfBskyEncryption {
	/**
	 * Encrypt sensitive data.
	 *
	 * @param string $value The value to encrypt.
	 * @return string The encrypted value.
	 */
	public static function encrypt( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}

		$salt      = wp_salt( 'auth' );
		$method    = 'aes-256-cbc';
		$iv_length = openssl_cipher_iv_length( $method );
		$iv        = openssl_random_pseudo_bytes( $iv_length );

		$encrypted = openssl_encrypt(
			$value,
			$method,
			$salt,
			0,
			$iv
		);

		if ( false === $encrypted ) {
			return '';
		}

		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt sensitive data.
	 *
	 * @param string $encrypted_value The encrypted value.
	 * @return string The decrypted value.
	 */
	public static function decrypt( string $encrypted_value ): string {
		if ( empty( $encrypted_value ) ) {
			return '';
		}

		$salt      = wp_salt( 'auth' );
		$method    = 'aes-256-cbc';
		$iv_length = openssl_cipher_iv_length( $method );

		$decoded = base64_decode( $encrypted_value );
		if ( false === $decoded ) {
			return '';
		}

		$iv        = substr( $decoded, 0, $iv_length );
		$encrypted = substr( $decoded, $iv_length );

		$decrypted = openssl_decrypt(
			$encrypted,
			$method,
			$salt,
			0,
			$iv
		);

		return false === $decrypted ? '' : $decrypted;
	}
} 