<?php
/**
 * To handle google authentication.
 *
 * @author  Dhaval Parekh <dmparekh007@gmail.com>
 *
 * @package login-with-google
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
	 * To store after login redirect URL.
	 *
	 * @var string
	 */
	protected $_redirect_to = '';

	/**
	 * @var \WP_User
	 */
	protected $_user = false;
	
	/**
	 * @var boolean
	 */
	protected $_is_logged = false;
	/**
	 * Google_Auth constructor.
	 */
	protected function __construct() {

		$this->_include_vendor();

		$this->_client = $this->_get_client();

		$this->_is_logged = add_filter( 'authenticate', [ $this, 'authenticate_user' ] );		
		add_filter( 'login_redirect', [ $this, 'get_login_redirect' ] );
		add_filter( 'registration_redirect', [ $this, 'get_login_redirect' ] );
		add_filter( 'allowed_redirect_hosts', [ $this, 'maybe_whitelist_subdomain' ] );

	}

	/**
	 * To include vendor file.
	 *
	 * @return void
	 */
	protected function _include_vendor() {

		$vendor_autoload = sprintf( '%s/vendor/autoload.php', WP_GOOGLE_LOGIN_PATH );

		$validate_file = validate_file( $vendor_autoload );
		// Function validate_file returns 2 for Windows drive path, so we check that as well.
		if ( ! empty( $vendor_autoload ) && file_exists( $vendor_autoload ) && ( 0 === $validate_file || 2 === $validate_file ) ) {
			require_once( $vendor_autoload ); // phpcs:ignore
		}

	}

	/**
	 * To get instance of Google Client.
	 *
	 * @return \Google_Client
	 */
	protected function _get_client() {
		$client_id     = wp_google_login_get_client_id();
		$client_secret = wp_google_login_get_client_secret();

		// If we don't have client id and secret then bail out, plugin won't work.
		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return;
		}

		$client = new \Google_Client();
		$client->setApplicationName( 'WP Google Login' );

		$client->setClientId( $client_id );
		$client->setClientSecret( $client_secret );

		$redirect_to = filter_input( INPUT_GET, 'redirect_to', FILTER_SANITIZE_URL );
		$redirect_to = ( ! empty( $redirect_to ) ) ? $redirect_to : admin_url();

		// If redirect_to url don't have host name then add that.
		$redirect_to = ( ! wp_parse_url( $redirect_to, PHP_URL_HOST ) ) ? home_url( $redirect_to ) : $redirect_to;

		$state = [
			'redirect_to' => $redirect_to,
			'blog_id'     => get_current_blog_id(),
		];
		$state = urlencode_deep( implode( '|', $state ) );

		$client->setState( $state );

		$login_url = $this->_get_login_url();

		$client->setRedirectUri( $login_url );

		return $client;

	}

	/**
	 * Get login URL, on which user will redirect after authenticated from google.
	 *
	 * @return string Redirect URL.
	 */
	protected function _get_login_url() {

		// By default we will use current site's login URL.
		$login_url = wp_login_url();

		// If it's multisite setup.
		// Then check if plugin is activate on network wide or if plugin is activate on main site
		// Then use main site login url.
		if ( is_multisite() && defined( 'BLOG_ID_CURRENT_SITE' ) ) {

			$mu_plugins = get_site_option( 'active_sitewide_plugins', [] );

			$plugins_activate_on_main_site = get_blog_option( BLOG_ID_CURRENT_SITE, 'active_plugins' );

			if ( ! empty( $mu_plugins[ WP_GOOGLE_LOGIN_PLUGIN_NAME ] ) || in_array( WP_GOOGLE_LOGIN_PLUGIN_NAME, $plugins_activate_on_main_site, true ) ) {
				$login_url = network_site_url( 'wp-login.php' ); // @codeCoverageIgnore
			}
		}

		return $login_url;
	}

	/**
	 * To get user info from google auth token.
	 *
	 * @param string $token Auth token.
	 *
	 * @return array|\Exception|\Google_Service_Exception User info
	 */
	protected function _get_user_from_token( $token ) {

		if ( empty( $token ) ) {
			return [];
		}

		$token = urldecode( $token );

		try {

			// @codeCoverageIgnoreStart
			// Ignoring because we cannot mock token and associate it with a user in test cases.
			$token = $this->_client->fetchAccessTokenWithAuthCode( $token );

			$oauthservice = new \Google_Service_Oauth2( $this->_client );

			$google_userinfo = $oauthservice->userinfo->get();

			$user_info = [
				'user_email'   => $google_userinfo->getEmail(),
				'display_name' => $google_userinfo->getName(),
				'first_name'   => $google_userinfo->getGivenName(),
				'last_name'    => $google_userinfo->getFamilyName(),
				'picture'      => $google_userinfo->getPicture(),
			];

			/**
			 * This hook provides access token fetched by google sign-in.
			 *
			 * @since 1.0
			 *
			 * @param array  $token     Converted access token.
			 * @param array  $user_info User details fetched from this token.
			 * @param object $client    Google_Client object.
			 */
			do_action( 'wp_google_login_token', $token, $user_info, $this->_client );

			return $user_info;

			// @codeCoverageIgnoreEnd
		} catch ( \Google_Service_Exception $exception ) {
			return $exception;
		}

	}

	/**
	 * To get scopes.
	 *
	 * @return string
	 */
	protected function _get_scopes() {

		$scopes = [
			'email',
			'profile',
			'openid',
		];

		/**
		 * This hook can be used to add/change google API scope.
		 * By setting different scopes, you can ask different permissions.
		 *
		 * @since 1.0
		 *
		 * @param array $scopes Scopes array.
		 *
		 * @return array Modified scopes.
		 */
		$scopes = apply_filters( 'wp_google_login_scopes', $scopes );

		return implode( ' ', $scopes );

	}

	/**
	 * Create user base on provided data.
	 *
	 * @param array $user_info User info,
	 *  user_email : User email address
	 *  display_name : Display name
	 *  first_name : First name
	 *  last_name : Last name.
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
		$user_info['user_pass']  = wp_generate_password( 18 );

		$user_id = wp_insert_user( $user_info );

		return ( ! empty( $user_id ) && ! is_wp_error( $user_id ) ) ? $user_id : 0;

	}

	/**
	 * To check if user can register or not.
	 *
	 * @return bool
	 */
	protected function _can_users_register() {
		$options  = get_option( 'wp_google_login_settings' );

		if ( defined( 'WP_GOOGLE_LOGIN_USER_REGISTRATION' ) ) {
			return (bool) WP_GOOGLE_LOGIN_USER_REGISTRATION;
		}

		$registration_enabled = ! empty( $options['registration_enabled'] ) ? (bool) $options['registration_enabled'] : false;

		if ( $registration_enabled ) {
			return true;
		}

		$can_user_register = get_option( 'users_can_register' );

		return ( ! empty( $can_user_register ) ) ? true : false;
	}

	/**
	 * To check if given email address can be register or not.
	 *
	 * @param string $email Email address.
	 *
	 * @return bool True if it can register, Otherwise False.
	 */
	protected function _can_register_with_email( $email ) {

		if ( empty( $email ) ) {
			return false;
		}

		$whitelisted_domains = wp_google_login_get_whitelisted_domains();

		/**
		 * If Const is not defined or empty,
		 * then allow all domain.
		 */
		if ( empty( $whitelisted_domains ) ) {
			return true;
		}

		$email_parts  = explode( '@', $email );
		$email_domain = ( ! empty( $email_parts[1] ) ) ? strtolower( trim( $email_parts[1] ) ) : '';

		$whitelisted_domains = explode( ',', $whitelisted_domains );
		$whitelisted_domains = array_map( 'trim', $whitelisted_domains );

		$count = ( ! empty( $whitelisted_domains ) ) && is_array( $whitelisted_domains ) ? count( $whitelisted_domains ) : 1;

		for ( $i = 0; $i < ( $count - 1 ); $i++ ) {

			$whitelisted_domains[ $i ] = strtolower( trim( $whitelisted_domains[ $i ] ) );
			$whitelisted_domains[ $i ] = str_replace( 'www.', '', $whitelisted_domains[ $i ] );

		}

		$whitelisted_domains = array_unique( $whitelisted_domains );

		return ( ! empty( $email_domain ) && in_array( $email_domain, $whitelisted_domains, true ) ) ? true : false;
	}

	/**
	 * To get google authentication URL.
	 *
	 * @return string
	 */
	public function get_login_url() {

		$scopes = $this->_get_scopes();
		if ( ! is_null( $this->_client ) ) {
			$url    = $this->_client->createAuthUrl( $scopes );
		}

		return $url;
	}

	/**
	 * To authenticate user.
	 *
	 * @param null|\WP_User|\WP_Error $user WP_User if the user is authenticated.
	 *  WP_Error or null otherwise.
	 *
	 * @return null|\WP_User|\WP_Error WP_User if the user is authenticated.
	 *  WP_Error or null otherwise.
	 */
	public function authenticate_user( $user = null ) {

		$is_mu_site = is_multisite();

		$token = Helper::filter_input( INPUT_GET, 'code', FILTER_SANITIZE_STRING );
		$state = Helper::filter_input( INPUT_GET, 'state', FILTER_SANITIZE_STRING );
		$state = urldecode( $state );
		$state = explode( '|', $state );

		$redirect_to = ( ! empty( $state[0] ) ) ? esc_url_raw( $state[0] ) : '';
		$blog_id     = ( ! empty( $state[1] ) && 0 < intval( $state[1] ) ) ? intval( $state[1] ) : 0;

		if ( empty( $token ) ) {
			return $user;
		}

		// Set redirect URL. so we can redirect after login.
		$this->_redirect_to = $redirect_to;

		/**
		 * If blog_id in state does not match current blog ID.
		 * Then redirect to login page of request blog.
		 * So that can take care of authentication.
		 */
		if ( $is_mu_site && $blog_id !== get_current_blog_id() ) {

			$query_string = filter_input( INPUT_SERVER, 'QUERY_STRING', FILTER_SANITIZE_STRING );

			$blog_url       = get_blog_option( $blog_id, 'siteurl' );
			$blog_login_url = sprintf( '%s/wp-login.php?%s', $blog_url, $query_string );

			wp_safe_redirect( $blog_login_url );
			// @codeCoverageIgnoreStart
			// Ignoring because cannot test exit.
			exit();
			// @codeCoverageIgnoreEnd
		}

		$user_info = $this->_get_user_from_token( $token );

		if ( ! is_array( $user_info ) || empty( $user_info['user_email'] ) || ! is_email( $user_info['user_email'] ) ) {
			return $user;
		}

		// @codeCoverageIgnoreStart
		// Ignoring because we cannot mock token and associate it with a user in test cases.
		$user = get_user_by( 'email', $user_info['user_email'] );

		// We found the user.
		if ( ! empty( $user ) && $user instanceof \WP_User ) {

			$this->_user = $user;

			if ( ! $is_mu_site ) {
				return $user;
			}

			// Check for MU site.
			if ( ! empty( $blog_id ) && is_user_member_of_blog( $user->ID, $blog_id ) ) {
				return $user;
			}
		}

		// Check if user registration is allow or not.
		if ( ! $this->_can_users_register() ) {
			return new \WP_Error(
				'wp_google_login_error',
				// translators: %s: User email.
				sprintf( __( 'User <strong>%s</strong> not registered in WordPress.', 'login-with-google' ), $user_info['user_email'] )
			);
		}

		// Check if email address is allowed or not.
		if ( ! $this->_can_register_with_email( $user_info['user_email'] ) ) {
			return new \WP_Error(
				'wp_google_login_error',
				// translators: %s: User email.
				sprintf( __( 'User can not register with <strong>%s</strong> email address.', 'login-with-google' ), $user_info['user_email'] )
			);
		}

		// Let's create WP user first.
		if ( empty( $user ) || ! $user instanceof \WP_User ) {
			$user_id = $this->_create_user( $user_info );
			$user    = get_user_by( 'id', $user_id );
			$this->_user = $user;
		}

		if ( $is_mu_site ) {
			$default_user_role = get_blog_option( $blog_id, 'default_role', 'subscriber' );
			add_user_to_blog( $blog_id, $user->ID, $default_user_role );
		}

		return $user;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * To redirect to appropriate URL after auth with google.
	 *
	 * @param string $redirect_to Redirect to URL.
	 *
	 * @return string Redirect to URL.
	 */
	public function get_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
		if($this->_user instanceof \WP_User && $this->_is_logged){ 
			/**
			 * This hook provides access to user after login from this plugin.
			 * @param \WP_User $user logged in.
			 */
			do_action('wp_google_login_user_loggedin', $user);
		}
		return ( ! empty( $this->_redirect_to ) ) ? $this->_redirect_to : $redirect_to;
	}

	/**
	 * To whitelist domain where we going to redirect after authentication user with google.
	 *
	 * @param array $hosts Whitelisted domains.
	 *
	 * @return array Whitelisted domains.
	 */
	public function maybe_whitelist_subdomain( $hosts = [] ) {

		$hosts = ( ! empty( $hosts ) && is_array( $hosts ) ) ? $hosts : [];

		if ( ! empty( $this->_redirect_to ) ) {
			$subdomain = wp_parse_url( $this->_redirect_to, PHP_URL_HOST );

			$hosts[] = $subdomain;
		}

		$hosts = array_unique( $hosts );

		return $hosts;
	}

}
