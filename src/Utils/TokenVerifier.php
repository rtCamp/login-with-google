<?php
/**
 * JWT Token Verifier.
 *
 * This will verify the token based on asymmetric encryption.
 *
 * @package RtCamp\GoogleLogin
 * @since 1.0.16
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Utils;

use RtCamp\GoogleLogin\Modules\Settings;
use WP_User;

/**
 * Class TokenVerifier
 *
 * @package RtCamp\GoogleLogin\Utils
 */
class TokenVerifier {
	/**
	 * Get list of public keys to verify signature.
	 */
	const CERTS_URL = 'https://www.googleapis.com/oauth2/v1/certs';

	/**
	 * List of supported algorithms.
	 */
	const SUPPORTED_ALGORITHMS = [
		'RS256' => OPENSSL_ALGO_SHA256,
		'RS384' => OPENSSL_ALGO_SHA384,
		'RS512' => OPENSSL_ALGO_SHA512,
		'ES384' => OPENSSL_ALGO_SHA384,
		'ES256' => OPENSSL_ALGO_SHA512,
	];

	/**
	 * ID Token Sent via Google.
	 *
	 * @var string
	 */
	private $token = '';

	/**
	 * User who needs to be authenticated.
	 *
	 * @var WP_User
	 */
	private $current_user;

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * TokenVerifier constructor.
	 *
	 * @param Settings $settings Settings instance.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Get supported algorithms value.
	 *
	 * @param string $algo Algorithm.
	 */
	public static function get_supported_algorithm( string $algo = '' ) {
		$find_algo = array_key_exists( $algo, self::SUPPORTED_ALGORITHMS );

		if ( ! $find_algo ) {
			return apply_filters( 'rtcamp.default_algorithm', OPENSSL_ALGO_SHA256, $algo );
		}

		return self::SUPPORTED_ALGORITHMS[ $algo ];
	}

	/**
	 * Verify if a token is valid or not.
	 *
	 * @param $token
	 *
	 * @return bool
	 */
	public function verify_token( $token ): bool {
		$this->token = $token;

		if ( ! $this->is_valid_jwt() ) {
			return false;
		}

		if ( ! $this->is_valid_signature() ) {
			return false;
		}

		return true;
	}

	/**
	 * Base64 URL Encode a string.
	 *
	 * @param string $string Input string to encode.
	 *
	 * @return array|string|string[]
	 */
	public function base64_encode_url( $string ) {
		return str_replace(['+','/','='], ['-','_',''], base64_encode($string));
	}

	/**
	 * Base64 URL Encode a string.
	 *
	 * @param string $string Input string to decode.
	 *
	 * @return false|string
	 */
	public function base64_decode_url( string $string) {
		return base64_decode(str_replace(['-','_'], ['+','/'], $string));
	}

	/**
	 * Get public key based on key ID.
	 *
	 * @param string|null $key_id Key ID.
	 *
	 * @return string|null
	 */
	public function get_public_key( $key_id = null ): ?string {
		if ( ! $key_id ) {
			return null;
		}

		$certs = wp_remote_get( self::CERTS_URL );

		if ( 200 !== wp_remote_retrieve_response_code( $certs ) ) {
			return null;
		}

		$keys = wp_remote_retrieve_body( $certs );
		$keys = json_decode( $keys );

		return property_exists( $keys, $key_id ) ? $keys->{$key_id} : null;
	}

	/**
	 * Checks whether received token is valid JWT token or not.
	 *
	 * @return array|null Decoded informational array with Header|Payload|Signature form.
	 */
	private function is_valid_jwt(): ?array {
		$parts = explode( '.', $this->token );

		if ( ! is_array( $parts ) || 3 !== sizeof( $parts ) ) {
			return null;
		}

		list( $header, $payload, $obtained_signature ) = $parts;
		$header  = $this->base64_decode_url( $header );
		$payload = $this->base64_decode_url( $payload );

		if ( ! $header || ! $payload ) {
			return null;
		}

		return [
			$header,
			$payload,
			$obtained_signature,
		];
	}

	/**
	 * Verifies the signature in token.
	 *
	 * @return bool
	 */
	private function is_valid_signature(): bool {
		list( $header, $payload, $obtained_signature ) = $this->is_valid_jwt();
		$parsed_header = json_decode( $header );
		$parsed_header = wp_parse_args(
			(array) $parsed_header,
			[
				'kid' => null,
				'alg' => null,
				'typ' => 'JWT',
			]
		);

		if ( ! $parsed_header['kid'] || ! $parsed_header['alg'] ) {
			return false;
		}

		$pubkey_pem           = $this->get_public_key( $parsed_header['kid'] );
		$decryption_key       = openssl_pkey_get_public( $pubkey_pem );
		$data                 = $this->base64_encode_url( $header ) . '.' . $this->base64_encode_url( $payload );
		$calculated_signature = openssl_verify( $data, $this->base64_decode_url( $obtained_signature ), $decryption_key, self::get_supported_algorithm( $parsed_header['alg'] ) );

		if ( 1 === $calculated_signature ) {
			$this->current_user = $payload;

			return true;
		}

		return false;
	}

}
