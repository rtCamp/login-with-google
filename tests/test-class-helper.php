<?php
/**
 * Test_Helper class for all helper function test.
 *
 * @author  Suraj Singh <suraj.sk243@gmail.com>
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

		ob_start();
		Helper::render_template( $template_path, [ 'login_url' => $login_url ] );
		$rendered_contents = ob_get_clean();

		$this->assertContains( $login_url, $rendered_contents );

	}
}

