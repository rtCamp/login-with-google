<?php
/**
 * Test_Google_Auth class for all function test.
 *
 * @author  Suraj Singh <suraj.sk243@gmail.com>
 *
 * @package wp-google-login
 */

namespace WP_Google_Login\Tests;

use  WP_Google_Login\Inc\Google_Auth;

/**
 * Class Test_Google_Auth
 *
 * @coversDefaultClass \WP_Google_Login\Inc\Google_Auth
 */
class Test_Google_Auth extends \WP_UnitTestCase {

	/**
	 * This google_auth data member will contain google_auth object.
	 *
	 * @var Object Google_auth object.
	 */
	protected $google_auth = false;

	/**
	 * This function set the instance for class google-auth.
	 */
	public function setUp() {

		$this->google_auth = Google_Auth::get_instance();
		/**
		 * Adding helper hook on wp_redirect which will throw exception
		 * which have message as redirected URL and Code as status.
		 * This is one way of escaping from exit in the code.
		 */
		add_filter( 'wp_redirect', array( $this, 'catch_redirect_destination' ), 99, 2 );

	}

	/**
	 * Test the filters and Google_Client instance.
	 *
	 * @covers ::__construct
	 */
	public function test_construct() {

		$client = Utility::get_property( $this->google_auth, '_client' );

		$this->assertInstanceOf( 'Google_Client', $client );
		$this->assertEquals( 10, has_filter( 'authenticate', [ $this->google_auth, 'authenticate_user' ] ) );
		$this->assertEquals( 10, has_filter( 'registration_redirect', [ $this->google_auth, 'get_login_redirect' ] ) );
		$this->assertEquals( 10, has_filter( 'login_redirect', [ $this->google_auth, 'get_login_redirect' ] ) );
		$this->assertEquals( 10, has_filter( 'allowed_redirect_hosts', [ $this->google_auth, 'maybe_whitelist_subdomain' ] ) );
	}

	/**
	 * Test Google client instance and attributes.
	 *
	 * @covers ::_get_client
	 */
	public function test_get_client() {
		$client        = Utility::get_property( $this->google_auth, '_client' );
		$_redirect_to  = Utility::get_property( $this->google_auth, '_redirect_to' );
		$client_config = Utility::get_property( $client, 'config' );

		$this->assertInstanceOf( 'Google_Client', $client );
		$this->assertEquals( $client_config['application_name'], 'WP Google Login' );
		$this->assertEquals( $client_config['client_id'], WP_GOOGLE_LOGIN_CLIENT_ID );
		$this->assertEquals( $client_config['client_secret'], WP_GOOGLE_LOGIN_SECRET );

		$state = $client_config['state'];
		$state = explode( '|', urldecode_deep( $state ) );

		if ( empty( $_redirect_to ) ) {
			$this->assertEquals( $state[0], admin_url() );
		} else {
			$this->assertEquals( $state[0], $_redirect_to );
		}

		if ( ! empty( get_current_blog_id() ) ) {
			$this->assertEquals( $state[1], get_current_blog_id() );
		}

		if ( is_multisite() ) {
			$this->assertEquals( $client_config['redirect_uri'], network_site_url( 'wp-login.php' ) );
		} else {
			$this->assertEquals( $client_config['redirect_uri'], wp_login_url() );
		}

	}

	/**
	 * Test user can register or not.
	 *
	 * @covers ::_can_users_register
	 */
	public function test_can_users_register() {

		define( 'WP_GOOGLE_LOGIN_USER_REGISTRATION', false );
		$this->assertFalse( Utility::invoke_method( $this->google_auth, '_can_users_register' ) );

		update_option( 'users_can_register', true );
		$this->assertFalse( Utility::invoke_method( $this->google_auth, '_can_users_register', [ '' ] ) );

	}

	/**
	 * Test login url.
	 *
	 * @covers ::get_login_url
	 */
	public function test_get_login_url() {
		$this->assertContains( 'https://accounts.google.com/o/oauth2/auth', $this->google_auth->get_login_url() );
	}

	/**
	 * Test login url.
	 *
	 * @covers ::get_login_url
	 */
	public function test_get_login_redirect() {

		$_redirect_to = Utility::get_property( $this->google_auth, '_redirect_to' );
		$this->assertEquals( $_redirect_to, $this->google_auth->get_login_redirect( $_redirect_to ) );
	}

	/**
	 * Test whitelisted subdomain and redirect_to.
	 *
	 * @covers ::_maybe_whitelist_subdomain
	 */
	public function test_maybe_whitelist_subdomain() {

		$_redirect_to = Utility::get_property( $this->google_auth, '_redirect_to' );

		if ( ! empty( $_redirect_to ) ) {
			$this->assertContains( $_redirect_to, $this->google_auth->maybe_whitelist_subdomain( array() ) );
		}
		$this->assertContains( 'http://rtmedia.com', $this->google_auth->maybe_whitelist_subdomain( [ 'http://rtmedia.com' ] ) );
	}

	/**
	 * Test for given email address can be register or not.
	 *
	 * @covers ::_can_register_with_email
	 */
	public function test_can_register_with_email() {
		$this->assertFalse( Utility::invoke_method( $this->google_auth, '_can_register_with_email', [ '' ] ) );
		$this->assertTrue( Utility::invoke_method( $this->google_auth, '_can_register_with_email', [ 'abc@gmail.com' ] ) );
		$this->assertTrue( Utility::invoke_method( $this->google_auth, '_can_register_with_email', [ 'abc@rtcamp.com' ] ) );
		$this->assertTrue( Utility::invoke_method( $this->google_auth, '_can_register_with_email', [ 'abc@xyz.com' ] ) );

		define( 'WP_GOOGLE_LOGIN_WHITELIST_DOMAINS', [ 'rtcamp.com', 'xyz.com' ] );
	}

	/**
	 * Test create user base on provided data.
	 *
	 * @covers ::_create_user
	 */
	public function test_create_user() {
		$user_id = Utility::invoke_method( $this->google_auth, '_create_user', [ array( 'user_email' => 'suraj@rtcamp.com' ) ] );
		$this->assertGreaterThan( 0, $user_id );
	}

	/**
	 * To authenticate user.
	 *
	 *  * @covers ::authenticate_user
	 */
	public function testauthenticate_user() {

		$is_mu_site = is_multisite();

		$state = filter_input( INPUT_GET, 'state', FILTER_SANITIZE_STRING );
		$state = urldecode( $state );
		$state = explode( '|', $state );

		$blog_id = ( ! empty( $state[1] ) && 0 < intval( $state[1] ) ) ? intval( $state[1] ) : 0;

		if ( $is_mu_site && 1 !== $blog_id ) { /**Current blog id as 1  */

			$query_string = filter_input( INPUT_SERVER, 'QUERY_STRING', FILTER_SANITIZE_STRING );

			$blog_url       = 'google.login';
			$blog_login_url = sprintf( '%s/wp-login.php?%s', $blog_url, $query_string );

			try {
				wp_safe_redirect( $blog_login_url );
			} catch ( \Exception $exp ) {
				$this->assertEquals( $exp->getMessage(), $blog_login_url );
				$this->assertEquals( $exp->getCode(), 302 );
			}
		}
	}

	/**
	 * Test the user info from google auth token.
	 *
	 * @covers ::_get_user_from_token
	 */
	public function test_get_user_from_token() {
		$output = Utility::invoke_method( $this->google_auth, '_get_user_from_token', [ '' ] );
		$this->assertEmpty( $output );
		$output = Utility::invoke_method( $this->google_auth, '_get_user_from_token', [ 'sadjhsfjf64das2d4s' ] );
		$this->assertInstanceOf( 'Google_Service_Exception', $output );
	}

	/**
	 * To catch any redirection and throw location and status in Exception.
	 * Note : Destination location can be get from Exception Message and
	 * status can be get from Exception code.
	 *
	 * @param  string $location Redirected location.
	 * @param  int    $status Status.
	 *
	 * @throws \Exception Redirection data.
	 *
	 * @return void
	 */
	public function catch_redirect_destination( $location, $status ) {
		throw new \Exception( $location, $status );
	}
}
