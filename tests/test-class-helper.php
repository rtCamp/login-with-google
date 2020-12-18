<?php
/**
 * Test_Helper class for all helper function test.
 *
 * @author  Suraj Singh <suraj.sk243@gmail.com>
 *
 * @package login-with-google
 */

namespace WP_Google_Login\Tests;

use WP_Google_Login\Inc\Helper;

/**
 * Class Test_Helper
 *
 * @coversDefaultClass \WP_Google_Login\Inc\Helper
 */
class Test_Helper extends \WP_UnitTestCase {

	/**
	 * @covers ::render_template
	 */
	public function test_render_template() {

		$template_path = sprintf( '%s/template/google-login-button.php', WP_GOOGLE_LOGIN_PATH );
		$this->assertFileExists( $template_path );

		$login_url = 'http://google.com';

		/**
		 * Test 1: Without passing third args.
		 */
		ob_start();
		Helper::render_template( $template_path, [ 'login_url' => $login_url ] );
		$rendered_contents = ob_get_clean();

		$this->assertContains( $login_url, $rendered_contents );

		/**
		 * Test 2: By passing third args $echo as false.
		 */
		$output = Helper::render_template( $template_path, [ 'login_url' => $login_url ], false );

		$this->assertContains( $login_url, $output );

		/**
		 * Test 3: By passing invalid file.
		 */
		$output = Helper::render_template( 'invalid/file/path.php', [], false );
		$this->assertEquals( '', $output );
	}

	/**
	 * @covers ::filter_input
	 */
	public function test_filter_input() {

		/**
		 * Test 1: Check with custom values.
		 */
		$_GET['custom_key']    = 'Values on GET variable.';
		$_POST['custom_key']   = 'Values on POST variable.';
		$_COOKIE['custom_key'] = 'Values on COOKIE variable.';
		$_ENV['custom_key']    = 'Values on ENV variable.';

		$this->assertEquals( $_GET['custom_key'], Helper::filter_input( INPUT_GET, 'custom_key' ) );
		$this->assertEquals( $_POST['custom_key'], Helper::filter_input( INPUT_POST, 'custom_key' ) );
		$this->assertEquals( $_COOKIE['custom_key'], Helper::filter_input( INPUT_COOKIE, 'custom_key' ) );
		$this->assertEquals( $_ENV['custom_key'], Helper::filter_input( INPUT_ENV, 'custom_key' ) );
		$this->assertEquals( $_SERVER['HTTP_HOST'], Helper::filter_input( INPUT_SERVER, 'HTTP_HOST' ) );

		unset( $_GET['custom_key'], $_POST['custom_key'], $_COOKIE['custom_key'], $_ENV['custom_key'] );

		/**
		 * Test 2: Check with keys those are not set.
		 */
		$this->assertNull( Helper::filter_input( INPUT_GET, 'custom_key' ) );
		$this->assertNull( Helper::filter_input( INPUT_POST, 'custom_key' ) );
		$this->assertNull( Helper::filter_input( INPUT_COOKIE, 'custom_key' ) );
		$this->assertNull( Helper::filter_input( INPUT_ENV, 'custom_key' ) );
		$this->assertNull( Helper::filter_input( INPUT_SERVER, 'custom_key' ) );
		$this->assertNull( Helper::filter_input( INPUT_REQUEST, 'custom_key' ) );
	}

}

