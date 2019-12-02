<?php
/**
 * Test_Helper class for all helper function test.
 *
 * @package wp-google-login
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
}

