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
	 * Test the filters and Google_Client instance.
	 *
	 * @covers Google_Auth::__construct
	 */
	public function test_construct() {

		$google_auth = Google_Auth::get_instance();
		$client      = Utility::get_property( $google_auth, '_client' );
		$this->assertInstanceOf( 'Google_Client', $client );
		$this->assertEquals( 10, has_filter( 'authenticate', [ $google_auth, 'authenticate_user' ] ) );
		$this->assertEquals( 10, has_filter( 'registration_redirect', [ $google_auth, 'get_login_redirect' ] ) );
		$this->assertEquals( 10, has_filter( 'login_redirect', [ $google_auth, 'get_login_redirect' ] ) );
		$this->assertEquals( 10, has_filter( 'allowed_redirect_hosts', [ $google_auth, 'maybe_whitelist_subdomain' ] ) );
	}
	/**
	 * Test vendor autoload file.
	 *
	 * @covers Google_Auth::_include_vendor
	 */
	public function test_include_vendor() {
		$vendor_autoload = sprintf( '%s/vendor/autoload.php', WP_GOOGLE_LOGIN_PATH );
		$this->assertFileExists( $vendor_autoload );
	}

	/**
	 * Test Google client instance and attibutes.
	 *
	 * @covers Google_Auth::_get_client
	 */
	public function test_get_client() {
		$google_auth   = Google_Auth::get_instance();
		$client        = Utility::get_property( $google_auth, '_client' );
		$_redirect_to  = Utility::get_property( $google_auth, '_redirect_to' );
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
	 * @covers Google_Auth::_can_users_register
	 */
	public function test_can_users_register() {

		if ( defined( 'WP_GOOGLE_LOGIN_USER_REGISTRATION' ) ) {
			$this->assertTrue( true );
		}

		$this->assertTrue( true, get_option( 'users_can_register' ) );
	}

	/**
	 * Test login url.
	 *
	 * @covers Google_Auth::get_login_url
	 */
	public function test_get_login_url() {
		$google_auth = Google_Auth::get_instance();
		$this->assertContains( 'https://accounts.google.com/o/oauth2/auth', $google_auth->get_login_url() );
	}

	/**
	 * Test login url.
	 *
	 * @covers Google_Auth::get_login_url
	 */
	public function test_get_login_redirect() {
		$google_auth  = Google_Auth::get_instance();
		$_redirect_to = Utility::get_property( $google_auth, '_redirect_to' );
		$this->assertEquals( $_redirect_to, $google_auth->get_login_redirect( $_redirect_to ) );
	}

	/**
	 * Test whitelisted subdomain and redirect_to.
	 *
	 * @covers Google_Auth::_maybe_whitelist_subdomain
	 */
	public function test_maybe_whitelist_subdomain() {
		$google_auth  = Google_Auth::get_instance();
		$_redirect_to = Utility::get_property( $google_auth, '_redirect_to' );

		if ( ! empty( $_redirect_to ) ) {
			$this->assertContains( $_redirect_to, $google_auth->maybe_whitelist_subdomain( array() ) );
		}
		$this->assertContains( 'http://rtmedia.com', $google_auth->maybe_whitelist_subdomain( [ 'http://rtmedia.com' ] ) );
	}

}

