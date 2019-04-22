<?php
/**
 * To handle google authentication.
 *
 * @author  Dhaval Parekh <dmparekh007@gmail.com>
 *
 * @package wp-google-login
 */

namespace WP_Google_Login\Inc;

use WP_Google_Login\Inc\Traits\Singleton;

/**
 * Class Google_Auth
 */
class Google_Auth {

	use Singleton;

	/**
	 * Google client instance.
	 *
	 * @var \Google_Client
	 */
	protected $_client = false;

	/**
	 * Google_Auth constructor.
	 */
	protected function __construct() {

		$this->_client = $this->_get_client();

		add_filter( 'authenticate', [ $this, 'authenticate_user' ] );
	}

	/**
	 * To get instance of Google Client.
	 *
	 * @return \Google_Client
	 */
	protected function _get_client() {

		$client = new \Google_Client();
		$client->setApplicationName( 'WP Google Login' );

		$client->setClientId( WP_GOOGLE_LOGIN_CLIENT_ID );
		$client->setClientSecret( WP_GOOGLE_LOGIN_SECRET );

		$client->setRedirectUri( wp_login_url() );

		if ( defined( 'WP_GOOGLE_LOGIN_HOSTED_DOMAIN' ) ) {
			$client->setHostedDomain( WP_GOOGLE_LOGIN_HOSTED_DOMAIN );
		}

		return $client;

	}

	/**
	 * To get scopes.
	 *
	 * @return string
	 */
	protected function _get_scopes() {

		return implode( ' ', [
			'email',
			'profile',
			'openid',
		] );

	}

	/**
	 * Create user base on provided data.
	 *
	 * @param array $user_info User info,
	 *                         user_email : User email address
	 *                         display_name : Display name
	 *                         first_name : First name
	 *                         last_name : Last name.
	 *
	 * @return int
	 */
	protected function _create_user( $user_info = [] ) {

		if ( empty( $user_info['user_email'] ) || ! is_email( $user_info['user_email'] ) ) {
			return 0;
		}

		$email = $user_info['user_email'];

		$user_login = sanitize_user( current( explode( '@', $email ) ), true );

		// Ensure username is unique.
		$append       = 1;
		$o_user_login = $user_login;

		while ( username_exists( $user_login ) ) {
			$user_login = $o_user_login . $append;
			$append++;
		}

		$user_info['user_login'] = $user_login;

		$user_id = wp_insert_user( $user_info );

		if ( ! empty( $user_id ) && ! is_wp_error( $user_id ) ) {
			return $user_id;
		}

		return 0;
	}

	/**
	 * To get google authentication URL.
	 *
	 * @return string
	 */
	public function get_login_url() {

		$scopes = $this->_get_scopes();
		$url    = $this->_client->createAuthUrl( $scopes );

		return $url;
	}

	/**
	 * To authenticate user.
	 *
	 * @param null|WP_User|WP_Error $user WP_User if the user is authenticated.
	 *                                    WP_Error or null otherwise.
	 *
	 * @return null|\WP_User WP_User if the user is authenticated.
	 *                       WP_Error or null otherwise.
	 */
	public function authenticate_user( $user ) {

		$token = filter_input( INPUT_GET, 'code', FILTER_SANITIZE_STRING );

		if ( empty( $token ) ) {
			return $user;
		}

		$user_info = $this->_get_user_from_token( $token );

		if ( ! empty( $user_info['user_email'] ) && is_email( $user_info['user_email'] ) ) {

			$user = get_user_by( 'email', $user_info['user_email'] );

			if ( empty( $user ) || ! is_a( $user, 'WP_User' ) ) {

				// Only create user if option is enable.
				$users_can_register = get_option( 'users_can_register' );

				if ( ! empty( $users_can_register ) ) {
					$user_id = $this->_create_user( $user_info );
					$user    = get_user_by( 'id', $user_id );
				} else {
					$user = new \WP_Error( 'wp_google_login_error', esc_html__( '<strong>ERROR:</strong> No user found.', 'google-apps-login' ) );
				}

			}

		}

		return $user;
	}

	/**
	 * To get user info from google auth token.
	 *
	 * @param string $token Auth token.
	 *
	 * @return array User info
	 */
	protected function _get_user_from_token( $token ) {

		if ( empty( $token ) ) {
			return [];
		}

		$token = urldecode( $token );

		try {
			$this->_client->authenticate( $token );
			$this->_client->getAccessToken();

			$oauthservice = new \Google_Service_Oauth2( $this->_client );

			$google_userinfo = $oauthservice->userinfo->get();

			$user_info = [
				'user_email'   => $google_userinfo->getEmail(),
				'display_name' => $google_userinfo->getName(),
				'first_name'   => $google_userinfo->getGivenName(),
				'last_name'    => $google_userinfo->getFamilyName(),
				'picture'      => $google_userinfo->getPicture(),
			];

		} catch ( \Google_Service_Exception $exception ) {
			return [];
		}

		return $user_info;
	}

}