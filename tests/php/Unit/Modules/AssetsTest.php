<?php
/**
 * Test assets module class.
 */

declare( strict_types=1 );

namespace RtCamp\GoogleLogin\Tests\Unit\Modules;

use WP_Mock;
use RtCamp\GoogleLogin\Tests\TestCase;
use RtCamp\GoogleLogin\Modules\Assets as Testee;

/**
 * Class AssetsTest
 *
 * @coversDefaultClass \RtCamp\GoogleLogin\Modules\Assets
 *
 * @package RtCamp\GoogleLogin\Tests\Unit\Modules
 */
class AssetsTest extends TestCase {
	/**
	 * Object in test.
	 *
	 * @var Testee
	 */
	private $testee;

	public function setUp(): void {
		$this->testee = new Testee();
	}

	/**
	 * @covers ::name
	 */
	public function testName() {
		$this->assertSame( 'assets', $this->testee->name() );
	}

	/**
	 * @covers ::init
	 */
	public function testInit() {
		WP_Mock::expectActionAdded(
			'login_enqueue_scripts',
			[
				$this->testee,
				'enqueue_login_styles',
			]
		);

		WP_Mock::expectActionAdded(
			'admin_enqueue_scripts',
			[
				$this->testee,
				'enqueue_admin_scripts',
			]
		);

		WP_Mock::expectActionAdded(
			'admin_enqueue_scripts',
			[
				$this->testee,
				'enqueue_admin_styles',
			]
		);

		$this->testee->init();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::register_login_styles
	 * @covers ::register_style
	 * @covers ::get_file_version
	 */
	public function testRegisterLoginStyles() {
		$this->wpMockFunction(
			'RtCamp\GoogleLogin\plugin',
			[],
			2,
			function () {
				return (object) [
					'url'        => 'https://example.com/',
					'assets_dir' => 'https://example.com/assets',
				];
			}
		);

		$this->wpMockFunction(
			'wp_register_style',
			[
				'login-with-google',
				'https://example.com/assets/build/css/login.css',
				[],
				false,
				true,
			],
			1,
			true
		);

		$this->testee->register_login_styles();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::register_admin_styles
	 */
	public function testEnqueueAdminStylesWithStyleRegistered() {
		$this->wpMockFunction(
			'wp_style_is',
			[
				$this->testee::SETTINGS_PAGE_STYLE_HANDLE,
				'registered',
			],
			1,
			true,
		);

		$this->wpMockFunction(
			'wp_enqueue_style',
			[
				$this->testee::SETTINGS_PAGE_STYLE_HANDLE,
			],
		);

		$this->testee->enqueue_admin_styles( $this->testee::SETTINGS_PAGE_HOOK_SUFFIX );
		$this->assertConditionsMet();

	}

	/**
	 * @covers ::register_admin_styles
	 */
	public function testEnqueueAdminStylesWithStyleNotRegistered() {
		$this->wpMockFunction(
			'RtCamp\GoogleLogin\plugin',
			[],
			2,
			function () {
				return (object) [
					'url'        => 'https://example.com/',
					'assets_dir' => 'https://example.com/assets',
				];
			}
		);

		$this->wpMockFunction(
			'wp_style_is',
			[
				$this->testee::SETTINGS_PAGE_STYLE_HANDLE,
				'registered',
			],
			1,
			false,
		);

		$this->wpMockFunction(
			'wp_register_style',
			[
				$this->testee::SETTINGS_PAGE_STYLE_HANDLE,
				'https://example.com/assets/build/css/settings.css',
				[],
				false,
				true,
			],
			1,
			true,
		);

		$this->wpMockFunction(
			'wp_enqueue_style',
			[
				$this->testee::SETTINGS_PAGE_STYLE_HANDLE,
			],
			1,
			true,
		);

		$this->testee->enqueue_admin_styles( $this->testee::SETTINGS_PAGE_HOOK_SUFFIX );
		$this->assertConditionsMet();

	}

	/**
	 * @covers ::enqueue_admin_scripts
	 */
	public function testEnqueueAdminScriptsWithScriptRegistered() {
		$this->wpMockFunction(
			'wp_script_is',
			[
				$this->testee::SETTINGS_PAGE_SCRIPT_HANDLE,
				'registered',
			],
			1,
			true,
		);

		$this->wpMockFunction(
			'wp_enqueue_script',
			[
				$this->testee::SETTINGS_PAGE_SCRIPT_HANDLE,
			],
		);

		$this->testee->enqueue_admin_scripts( $this->testee::SETTINGS_PAGE_HOOK_SUFFIX );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::enqueue_admin_scripts
	 */
	public function testEnqueueAdminScriptsWithScriptNotRegistered() {
		$this->wpMockFunction(
			'RtCamp\GoogleLogin\plugin',
			[],
			2,
			function () {
				return (object) [
					'url'        => 'https://example.com/',
					'assets_dir' => 'https://example.com/assets',
				];
			}
		);

		$this->wpMockFunction(
			'wp_script_is',
			[
				$this->testee::SETTINGS_PAGE_SCRIPT_HANDLE,
				'registered',
			],
			1,
			false,
		);

		$this->wpMockFunction(
			'wp_register_script',
			[
				$this->testee::SETTINGS_PAGE_SCRIPT_HANDLE,
				'https://example.com/assets/build/js/settings.js',
				[],
				false,
				true,
			],
			1,
			true,
		);

		$this->wpMockFunction(
			'wp_enqueue_script',
			[
				$this->testee::SETTINGS_PAGE_SCRIPT_HANDLE,
			],
			1,
			true,
		);

		$this->testee->enqueue_admin_scripts( $this->testee::SETTINGS_PAGE_HOOK_SUFFIX );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::register_script
	 */
	public function testRegisterLoginScript() {
		$this->wpMockFunction(
			'RtCamp\GoogleLogin\plugin',
			[],
			2,
			function () {
				return (object) [
					'url'        => 'https://example.com/',
					'assets_dir' => 'https://example.com/assets',
				];
			}
		);

		$this->wpMockFunction(
			'wp_register_script',
			[
				'login-with-google',
				'https://example.com/assets/js/login.js',
				[
					'some-other-script',
				],
				false,
				true,
			],
			1,
			true
		);

		$this->testee->register_script(
			'login-with-google',
			'js/login.js',
			[
				'some-other-script',
			]
		);

		$this->assertConditionsMet();
	}

	/**
	 * Test enqueuing style when it is already registered.
	 *
	 * @covers ::enqueue_login_styles
	 */
	public function testEnqueueLoginStyleWithStyleRegistered() {
		$this->wpMockFunction(
			'wp_style_is',
			[
				'login-with-google',
				'registered',
			],
			1,
			true
		);

		$this->wpMockFunction(
			'wp_script_is',
			[
				'login-with-google-script',
				'registered',
			],
			1,
			true
		);

		$this->wpMockFunction(
			'wp_register_style',
			[
				'login-with-google',
				'https://example.com/assets/build/css/login.css',
				[],
				false,
				true,
			],
			0,
			true
		);

		$this->wpMockFunction(
			'wp_enqueue_style',
			[
				'login-with-google',
			],
			1,
			true
		);

		$this->wpMockFunction(
			'wp_enqueue_script',
			[
				'login-with-google-script',
			],
			1,
			true
		);

		$this->testee->enqueue_login_styles();
		$this->assertConditionsMet();
	}

	/**
	 * Test enqueuing style when it is already registered.
	 *
	 * @covers ::enqueue_login_styles
	 * @covers ::get_file_version
	 */
	public function testEnqueueLoginStyleWithStyleNotRegistered() {
		$this->wpMockFunction(
			'wp_style_is',
			[
				'login-with-google',
				'registered',
			],
			1,
			false
		);

		$this->wpMockFunction(
			'wp_script_is',
			[
				'login-with-google-script',
				'registered',
			],
			1,
			false
		);

		$this->wpMockFunction(
			'RtCamp\GoogleLogin\plugin',
			[],
			4,
			function () {
				return (object) [
					'url'        => 'https://example.com/',
					'assets_dir' => 'https://example.com/assets',
				];
			}
		);

		$this->wpMockFunction(
			'wp_register_style',
			[
				'login-with-google',
				'https://example.com/assets/build/css/login.css',
				[],
				false,
				true,
			],
			1,
			true
		);

		$this->wpMockFunction(
			'wp_enqueue_style',
			[
				'login-with-google',
			],
			1,
			true
		);

		$this->wpMockFunction(
			'wp_enqueue_script',
			[
				'login-with-google-script',
			],
			1,
			true
		);

		$this->testee->enqueue_login_styles();
		$this->assertConditionsMet();
	}
}
