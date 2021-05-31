<?php
/**
 * Test settings module class.
 */

declare( strict_types=1 );

namespace RtCamp\GoogleLogin\Tests\Unit\Modules;

use WP_Mock;
use RtCamp\GoogleLogin\Interfaces\Module as ModuleInterface;
use RtCamp\GoogleLogin\Tests\TestCase;
use RtCamp\GoogleLogin\Modules\Settings as Testee;

/**
 * Class SettingsTest
 *
 * @coversDefaultClass \RtCamp\GoogleLogin\Modules\Settings
 *
 * @package RtCamp\GoogleLogin\Tests\Unit\Modules
 */
class SettingsTest extends TestCase {
	/**
	 * Object in test.
	 *
	 * @var Testee
	 */
	private $testee;

	/**
	 * Run before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		$this->testee = new Testee();
	}

	/**
	 * @covers ::name
	 */
	public function testName() {
		$this->assertSame( 'settings', $this->testee->name() );
	}

	public function testImplementsModuleInterface() {
		$this->assertTrue( $this->testee instanceof ModuleInterface );
	}

	/**
	 * @covers ::init
	 */
	public function testInit() {
		$this->wpMockFunction(
			'get_option',
			[
				'wp_github_login_settings',
				[]
			],
			1,
			[]
		);

		WP_Mock::expectActionAdded( 'admin_init', [ $this->testee, 'register_settings' ] );
		WP_Mock::expectActionAdded( 'admin_menu', [ $this->testee, 'settings_page' ] );

		$this->testee->init();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::register_settings
	 */
	public function testRegisterSettings() {
		$this->wpMockFunction(
			'register_setting',
			[
				'wp_github_login',
				'wp_github_login_settings'
			],
			1,
			true
		);

		$this->wpMockFunction(
			'add_settings_section',
			[
				'wp_github_login_section',
				'Log in with Github Settings',
				\Closure::class,
				'login-with-github'
			],
			1
		);

		WP_Mock::userFunction(
			'add_settings_field',
			[
				'args'  => [
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'callable' ),
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'array' ),
				],
				'times' => 4
			]
		);

		$this->testee->register_settings();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::settings_page
	 */
	public function testSettingsPage() {
		$this->wpMockFunction(
			'add_options_page',
			[
				'Login with Github settings',
				'Login with Github',
				'manage_options',
				'login-with-github',
				[
					$this->testee,
					'output'
				],
			]
		);

		$this->testee->settings_page();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::output
	 */
	public function testOutput() {
		$this->wpMockFunction(
			'settings_fields',
			[
				'wp_github_login',
			],
			1
		);

		$this->wpMockFunction(
			'do_settings_sections',
			[
				'login-with-github',
			],
			1
		);

		$this->wpMockFunction(
			'submit_button',
			[],
			1,
			''
		);

		$this->setOutputCallback(function() {});
		$this->testee->output();
		$this->assertConditionsMet();
	}
}
