<?php
/**
 * Test_Google_Auth class for all function test.
 *
 * @author  Suraj Singh <suraj.sk243@gmail.com>
 *
 * @package wp-google-login
 */

namespace WP_Google_Login\Tests;

use Exception;
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
	 * @var \WP_Google_Login\Inc\Google_Auth
	 */
	protected $_instance = false;

	/**
	 * This function set the instance for class google-auth.
	 */
	public function setUp(): void {

		$this->_instance = Google_Auth::get_instance();

	}

	/**
	 * Test the filters and Google_Client instance.
	 *
	 * @covers ::__construct
	 */
	public function test_construct() {

		Utility::invoke_method( $this->_instance, '__construct' );
		
		$client = Utility::get_property( $this->_instance, '_client' );

		$this->assertInstanceOf( 'Google_Client', $client );

		$this->assertEquals( 10, has_filter( 'authenticate', [ $this->_instance, 'authenticate_user' ] ) );
		$this->assertEquals( 10, has_filter( 'registration_redirect', [ $this->_instance, 'get_login_redirect' ] ) );
		$this->assertEquals( 10, has_filter( 'login_redirect', [ $this->_instance, 'get_login_redirect' ] ) );
		$this->assertEquals( 10, has_filter( 'allowed_redirect_hosts', [ $this->_instance, 'maybe_whitelist_subdomain' ] ) );
	}

	/**
	 * Test Google client instance and attributes.
	 *
	 * @covers ::_get_client
	 */
	public function test_get_client() {

		$google_client = Utility::invoke_method( $this->_instance, '_get_client' );
		$client_config = Utility::get_property( $google_client, 'config' );

		$this->assertInstanceOf( 'Google_Client', $google_client );

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

		$this->assertFalse( Utility::invoke_method( $this->_instance, '_can_users_register' ) );

		define( 'WP_GOOGLE_LOGIN_USER_REGISTRATION', false );
		$this->assertFalse( Utility::invoke_method( $this->_instance, '_can_users_register' ) );

	}

	/**
	 * @covers ::_get_login_url
	 */
	public function test__get_login_url() {

		/**
		 * Test 1: For single site.
		 */
		$this->assertEquals( Utility::invoke_method( $this->_instance, '_get_login_url' ), wp_login_url() );

		/**
		 * Test 2:
		 */
		define( 'BLOG_ID_CURRENT_SITE', 1 );

		$this->assertEquals( Utility::invoke_method( $this->_instance, '_get_login_url' ), wp_login_url() );

	}

	/**
	 * Test login url.
	 *
	 * @covers ::get_login_url
	 */
	public function test_get_login_url() {
		$this->assertContains( 'https://accounts.google.com/o/oauth2/auth', $this->_instance->get_login_url() );
	}

	/**
	 * Test the user info from google auth token.
	 *
	 * @covers ::_get_user_from_token
	 */
	public function test_get_user_from_token() {

		$output = Utility::invoke_method( $this->_instance, '_get_user_from_token', [ '' ] );
		$this->assertEmpty( $output );

		$output = Utility::invoke_method( $this->_instance, '_get_user_from_token', [ 'sadjhsfjf64das2d4s' ] );
		$this->assertInstanceOf( 'Google_Service_Exception', $output );

	}

	/**
	 * @covers ::_get_scopes
	 */
	public function test_get_scopes() {

		$output = Utility::invoke_method( $this->_instance, '_get_scopes' );

		$this->assertEquals( 'email profile openid', $output );

	}

	/**
	 * Test create user base on provided data.
	 *
	 * @covers ::_create_user
	 */
	public function test_create_user() {

		/**
		 * Test 1: User detail without email.
		 */
		$this->assertEquals( 0, Utility::invoke_method( $this->_instance, '_create_user', [ [ 'display_name' => 'User Name' ] ] ) );

		/**
		 * Test 2: Pass user email address.
		 */
		$user_1_id   = Utility::invoke_method( $this->_instance, '_create_user', [ [ 'user_email' => 'user@example.com' ] ] );
		$user_1_data = get_userdata( $user_1_id );
		$this->assertGreaterThan( 0, $user_1_id );

		/**
		 * Test 3: With identical email address.
		 */
		$user_2_id   = Utility::invoke_method( $this->_instance, '_create_user', [ [ 'user_email' => 'user@example2.com' ] ] );
		$user_2_data = get_userdata( $user_2_id );

		$this->assertNotEquals( $user_1_id, $user_2_id );
		$this->assertNotEquals( $user_1_data->data->user_login, $user_2_data->data->user_login );
	}

	/**
	 * Test for given email address can be register or not.
	 *
	 * @covers ::_can_register_with_email
	 */
	public function test_can_register_with_email() {

		/**
		 * Test 1: Don't allow empty email
		 */
		$this->assertFalse( Utility::invoke_method( $this->_instance, '_can_register_with_email', [ '' ] ) );

		/**
		 * Test 2: Allow email with any domain.
		 */
		$this->assertTrue( Utility::invoke_method( $this->_instance, '_can_register_with_email', [ 'user@gmail.com' ] ) );
		$this->assertTrue( Utility::invoke_method( $this->_instance, '_can_register_with_email', [ 'user@sample.com' ] ) );
		$this->assertTrue( Utility::invoke_method( $this->_instance, '_can_register_with_email', [ 'user@example.com' ] ) );

		/**
		 * Test 3: Allow selected domains.
		 */
		define( 'WP_GOOGLE_LOGIN_WHITELIST_DOMAINS', 'example.com, sample.com' );

		$this->assertFalse( Utility::invoke_method( $this->_instance, '_can_register_with_email', [ 'user@gmail.com' ] ) );
		$this->assertTrue( Utility::invoke_method( $this->_instance, '_can_register_with_email', [ 'user@sample.com' ] ) );
		$this->assertTrue( Utility::invoke_method( $this->_instance, '_can_register_with_email', [ 'user@example.com' ] ) );
	}

	/**
	 * To authenticate user.
	 *
	 * @covers ::authenticate_user
	 */
	public function test_authenticate_user() {

		/**
		 * Adding helper hook on wp_redirect which will throw exception
		 * which have message as redirected URL and Code as status.
		 * This is one way of escaping from exit in the code.
		 */
		add_filter( 'wp_redirect', [ $this, 'catch_redirect_destination' ], 99, 2 );

		/**
		 * Test 1: No User passed, No token provided.
		 */
		$this->assertEmpty( $this->_instance->authenticate_user( null ) );

		/**
		 * Test 2: After passing token and state.
		 */
		$state = [
			'redirect_to' => home_url( 'wp-admin/edit.php' ),
			'blog_id'     => 1,
		];

		$_GET['code']  = 'token_code';
		$_GET['state'] = urlencode_deep( implode( '|', $state ) );

		$output = $this->_instance->authenticate_user( 'custom_user' );
		$this->assertEquals( 'custom_user', $output );

		/**
		 * Test 3: blog id not equals to current blog
		 */
		$blog_id = self::factory()->blog->create();
		$current_blog = get_current_blog_id();

		switch_to_blog( $blog_id ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.switch_to_blog_switch_to_blog

		$this->expectException(Exception::class);

		$output = $this->_instance->authenticate_user( 'custom_user' );

		switch_to_blog( $current_blog ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.switch_to_blog_switch_to_blog

		remove_filter( 'wp_redirect', [ $this, 'catch_redirect_destination' ], 99 );
	}

	/**
	 * Test login url.
	 *
	 * @covers ::get_login_redirect
	 */
	public function test_get_login_redirect() {

		$_redirect_to = Utility::get_property( $this->_instance, '_redirect_to' );
		$this->assertEquals( $_redirect_to, $this->_instance->get_login_redirect( $_redirect_to ) );
	}

	/**
	 * Test whitelisted sub domain and redirect_to.
	 *
	 * @covers ::maybe_whitelist_subdomain
	 */
	public function test_maybe_whitelist_subdomain() {

		Utility::set_and_get_property( $this->_instance, '_redirect_to', 'https://externalurl.com' );

		$this->assertContains( 'externalurl.com', $this->_instance->maybe_whitelist_subdomain( [] ) );

	}

	/**
	 * To catch any redirection and throw location and status in Exception.
	 * Note : Destination location can be get from Exception Message and
	 * status can be get from Exception code.
	 *
	 * @param string $location Redirected location.
	 * @param int    $status   Status.
	 *
	 * @throws \Exception Redirection data.
	 *
	 * @return void
	 */
	public function catch_redirect_destination( $location, $status ) { // phpcs:ignore WordPressVIPMinimum.Filters.AlwaysReturn.missingReturnStatement
		throw new \Exception( $location, $status );
	}
}

