<?php
/**
 * Test assets module class.
 */

declare( strict_types=1 );

namespace RtCamp\GithubLogin\Tests\Unit\Modules;

use WP_Mock;
use RtCamp\GithubLogin\Tests\TestCase;
use RtCamp\GithubLogin\Modules\Assets as Testee;

/**
 * Class AssetsTest
 *
 * @coversDefaultClass \RtCamp\GithubLogin\Modules\Assets
 *
 * @package RtCamp\GithubLogin\Tests\Unit\Modules
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
				'enqueue_login_styles'
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
			'RtCamp\GithubLogin\plugin',
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
				'github-login',
				'https://example.com/assets/css/login.css',
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
	 * @covers ::register_script
	 */
	public function testRegisterLoginScript() {
		$this->wpMockFunction(
			'RtCamp\GithubLogin\plugin',
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
				'github-login-js',
				'https://example.com/assets/js/login.js',
				[
					'some-other-script'
				],
				false,
				true,
			],
			1,
			true
		);

		$this->testee->register_script(
			'github-login-js',
			'js/login.js',
			[
				'some-other-script'
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
			'wp_script_is',
			[
				'github-login',
				'registered',
			],
			1,
			true
		);

		$this->wpMockFunction(
			'wp_register_style',
			[
				'github-login',
				'https://example.com/assets/css/login.css',
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
				'github-login',
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
			'wp_script_is',
			[
				'github-login',
				'registered',
			],
			1,
			false
		);

		$this->wpMockFunction(
			'RtCamp\GithubLogin\plugin',
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
				'github-login',
				'https://example.com/assets/css/login.css',
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
				'github-login',
			],
			1,
			true
		);

		$this->testee->enqueue_login_styles();
		$this->assertConditionsMet();
	}
}
