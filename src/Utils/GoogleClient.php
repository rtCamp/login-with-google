<?php
/**
 * Google API Client.
 *
 * Useful for authenticating the user and other API related operations.
 *
 * @package RtCamp\GoogleLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Utils;

use Exception;

/**
 * Class GoogleClient
 *
 * @package RtCamp\GoogleLogin\Utils
 */
class GoogleClient {
	/**
	 * Authorization URL.
	 *
	 * @var string
	 */
	const AUTHORIZE_URL = 'https://accounts.google.com/o/oauth2/auth';

	/**
	 * Access Token URL.
	 *
	 * @var string
	 */
	const TOKEN_URL = 'https://oauth2.googleapis.com/token';

	/**
	 * API base for google.
	 *
	 * @var string
	 */
	const API_BASE = 'https://www.googleapis.com';

	/**
	 * Client ID.
	 *
	 * @var string
	 */
	public $client_id;

	/**
	 * Client secret.
	 *
	 * @var string
	 */
	public $client_secret;

	/**
	 * Redirect URI.
	 *
	 * @var string
	 */
	public $redirect_uri;

	/**
	 * Access token.
	 *
	 * @var string
	 */
	private $access_token;

	/**
	 * GoogleClient constructor.
	 *
	 * @param array $config Configuration for client.
	 */
	public function __construct( array $config ) {
		$this->client_id     = $config['client_id'] ?? '';
		$this->client_secret = $config['client_secret'] ?? '';
		$this->redirect_uri  = $config['redirect_uri'] ?? '';
	}

	/**
	 * Check if access token is set before calling API methods.
	 *
	 * @param string $name Name of method called.
	 * @param mixed  $args Arguments for method.
	 *
	 * @throws Exception Empty access token.
	 */
	public function __call( string $name, $args ) {
		$methods = [
			'user',
			'emails',
		];

		if ( in_array( $name, $methods, true ) && empty( $this->access_token ) ) {
			throw new Exception( __( 'Access token must be set to make this API call', 'login-with-google' ) );
		}
	}

	/**
	 * Set access token.
	 *
	 * @param string $code Token.
	 *
	 * @return self
	 * @throws \Throwable Exception for fetching access token.
	 */
	public function set_access_token( string $code ): self {
		try {
			$this->access_token = $this->access_token( $code )->access_token;

			return $this;
		} catch ( \Throwable $e ) {
			throw $e;
		}
	}

	/**
	 * Return redirect url.
	 *
	 * @return string
	 */
	public function gt_redirect_url(): string {
		return apply_filters( 'rtcamp.google_redirect_url', $this->redirect_uri );
	}

	/**
	 * Get the authorize URL
	 *
	 * @return string
	 */
	public function authorization_url(): string {
		$plugin_scope = [
			'email',
			'profile',
			'openid',
		];

		$scope = apply_filters_deprecated(
			'wp_google_login_scopes',
			[
				$plugin_scope,
			],
			'1.0.15',
			'rtcamp.google_scope'
		);

		/**
		 * Filter the scopes.
		 *
		 * @param array $scope List of scopes.
		 */
		$scope = apply_filters( 'rtcamp.google_scope', $scope );

		$client_args = [
			'client_id'     => $this->client_id,
			'redirect_uri'  => $this->gt_redirect_url(),
			'state'         => $this->state(),
			'scope'         => implode( ' ', $scope ),
			'access_type'   => 'online',
			'response_type' => 'code',
		];

		/**
		 * Filter the arguments for sending in query.
		 *
		 * This is useful in cases for example: choosing the correct prompt.
		 *
		 * @param array $client_args List of query arguments to send to Google OAuth.
		 */
		$client_args = apply_filters( 'rtcamp.google_client_args', $client_args );

		return self::AUTHORIZE_URL . '?' . http_build_query( $client_args );
	}

	/**
	 * Get the access token.
	 *
	 * @param string $code Response code received during authorization.
	 *
	 * @return \stdClass
	 * @throws Exception For access token errors.
	 */
	public function access_token( string $code ): \stdClass {
		$response = wp_remote_post(
			self::TOKEN_URL,
			[
				'headers' => [
					'Accept' => 'application/json',
				],
				'body'    => [
					'client_id'     => $this->client_id,
					'client_secret' => $this->client_secret,
					'redirect_uri'  => $this->gt_redirect_url(),
					'code'          => $code,
					'grant_type'    => 'authorization_code',
				],
			]
		);

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			throw new Exception( __( 'Could not retrieve the access token, please try again.', 'login-with-google' ) );
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Make an API request.
	 *
	 * @return \stdClass
	 * @throws \Throwable Exception during API.
	 * @throws Exception API Exception.
	 */
	public function user(): \stdClass {
		try {
			//phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
			$user = wp_remote_get(
				trailingslashit( self::API_BASE ) . 'oauth2/v2/userinfo?access_token=' . $this->access_token,
				[
					'headers' => [
						'Accept' => 'application/json',
					],
				]
			);

			if ( 200 !== wp_remote_retrieve_response_code( $user ) ) {
				throw new Exception( __( 'Could not retrieve the user information, please try again.', 'login-with-google' ) );
			}

			return json_decode( wp_remote_retrieve_body( $user ) );

		} catch ( \Throwable $e ) {

			throw $e;
		}
	}

	/**
	 * State to pass in GH API.
	 *
	 * @return string
	 */
	public function state(): string {
		$state_data['nonce']    = wp_create_nonce( 'login_with_google' );
		$state_data             = apply_filters( 'rtcamp.google_login_state', $state_data );
		$state_data['provider'] = 'google';

		return base64_encode( wp_json_encode( $state_data ) );
	}

}
