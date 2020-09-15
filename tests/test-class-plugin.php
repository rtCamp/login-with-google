<?php
/**
 * Test_Plugin class for all plugin function test.
 *
 * @author  Suraj Singh <suraj.sk243@gmail.com>
 *
 * @package login-with-google
 */

namespace WP_Google_Login\Tests;

use WP_Google_Login\Inc\Plugin;
use WP_Google_Login\Inc\Google_Auth;

/**
 * Class Test_Plugin
 *
 * @coversDefaultClass \WP_Google_Login\Inc\Plugin
 */
class Test_Plugin extends \WP_UnitTestCase {

	/**
	 * @var \WP_Google_Login\Inc\Plugin
	 */
	protected $_instance = false;

	/**
	 * Setup method.
	 *
	 * @return void
	 */
	public function setUp() {

		$this->_instance = Plugin::get_instance();

		parent::setUp();
	}

	/**
	 * @covers ::__construct
	 * @covers ::_setup_hooks
	 */
	public function test__construct() {

		Utility::invoke_method( $this->_instance, '__construct' );

		$this->assertEquals( 10, has_action( 'login_enqueue_scripts', [ $this->_instance, 'login_enqueue_scripts' ] ) );
		$this->assertEquals( 10, has_action( 'login_form', [ $this->_instance, 'add_google_login_button' ] ) );
		$this->assertEquals( 10, has_action( 'register_form', [ $this->_instance, 'add_google_login_button' ] ) );

	}

	/**
	 * @covers ::login_enqueue_scripts
	 */
	public function test_login_enqueue_scripts() {

		$this->_instance->login_enqueue_scripts();

		$this->assertTrue( wp_style_is( 'wp_google_login_style', 'registered' ) );
		$this->assertTrue( wp_script_is( 'wp_google_login_script', 'registered' ) );

	}

	/**
	 * @covers ::add_google_login_button
	 */
	public function test_add_google_login_button() {

		$google_auth = Google_Auth::get_instance();
		$login_url   = esc_url( $google_auth->get_login_url() );

		ob_start();
		$this->_instance->add_google_login_button();
		$login_button_html = ob_get_clean();

		$this->assertContains( $login_url, $login_button_html );
	}

}
