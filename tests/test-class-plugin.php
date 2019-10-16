<?php
/**
 * Test_Plugin class for all plugin function test.
 *
 * @author  Suraj Singh <suraj.sk243@gmail.com>
 *
 * @package wp-google-login
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
	 * @covers ::_setup_hooks
	 */
	public function test_setup_hooks() {

		$plugin = Plugin::get_instance();
		$this->assertEquals( 10, has_action( 'login_enqueue_scripts', [ $plugin, 'login_enqueue_scripts' ] ) );
		$this->assertEquals( 10, has_action( 'login_form', [ $plugin, 'add_google_login_button' ] ) );
		$this->assertEquals( 10, has_action( 'register_form', [ $plugin, 'add_google_login_button' ] ) );

	}

	/**
	 * @covers ::login_enqueue_scripts
	 */
	public function test_login_enqueue_scripts() {

		$plugin = Plugin::get_instance();
		$plugin->login_enqueue_scripts();
		$this->assertTrue( wp_style_is( 'wp_google_login_style', 'registered' ) );
		$this->assertTrue( wp_script_is( 'wp_google_login_script', 'registered' ) );

	}

	/**
	 * @covers ::add_google_login_button
	 */
	public function test_add_google_login_button() {

		$plugin      = Plugin::get_instance();
		$google_auth = Google_Auth::get_instance();
		$login_url   = esc_url( $google_auth->get_login_url() );

		ob_start();
		$plugin->add_google_login_button();
		$login_button_html = ob_get_clean();

		$this->assertContains( $login_url, $login_button_html );
	}

}
