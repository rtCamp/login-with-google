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
	 * To get google authentication URL.
	 *
	 * @return string
	 */
	public function get_login_url() {

		$scopes = $this->_get_scopes();
		$url    = $this->_client->createAuthUrl( $scopes );

		return $url;
	}

}