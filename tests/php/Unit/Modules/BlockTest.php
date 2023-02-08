<?php
/**
 * Test Block module class.
 */

declare( strict_types=1 );

namespace RtCamp\GoogleLogin\Tests\Unit\Modules;

use RtCamp\GoogleLogin\Interfaces\Module as ModuleInterface;
use RtCamp\GoogleLogin\Utils\Helper;
use WP_Mock;
use Mockery;
use RtCamp\GoogleLogin\Modules\Block as Testee;
use RtCamp\GoogleLogin\Tests\TestCase;
use RtCamp\GoogleLogin\Utils\GoogleClient;
use RtCamp\GoogleLogin\Modules\Assets;

/**
 * Class BlockTest
 *
 * @coversDefaultClass \RtCamp\GoogleLogin\Modules\Block
 *
 * @package RtCamp\GoogleLogin\Tests\Unit\Modules
 */
class BlockTest extends TestCase {
	/**
	 * @var GoogleClient
	 */
	private $gh_client_mock;

	/**
	 * @var Assets
	 */
	private $assert_mock;

	/**
	 * @var Testee
	 */
	private $testee;

	/**
	 * Run before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		$this->gh_client_mock = $this->createMock( GoogleClient::class );
		$this->assert_mock    = $this->createMock( Assets::class );
		$this->testee         = new Testee( $this->assert_mock, $this->gh_client_mock );
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->gh_client_mock = null;
		$this->assert_mock    = null;
		unset( $this->testee );
	}

	/**
	 * @covers ::name
	 */
	public function testName() {
		$this->assertSame( 'google_login_block', $this->testee->name() );
	}

	public function testImplementsModuleInterface() {
		$this->assertTrue( $this->testee instanceof ModuleInterface );
	}

	/**
	 * @covers ::init
	 */
	public function testInit() {
		WP_Mock::expectActionAdded( 'wp_enqueue_scripts', [ $this->testee->assets, 'register_login_styles' ] );
		WP_Mock::expectActionAdded( 'enqueue_block_editor_assets', [ $this->testee, 'enqueue_block_editor_assets' ] );
		WP_Mock::expectActionAdded( 'init', [ $this->testee, 'register' ] );

		$this->testee->init();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::enqueue_block_editor_assets
	 */
	public function testEnqueueBlockEditorAssets() {
		$path = dirname( __DIR__, 4 ) . '/assets/';

		WP_Mock::userFunction(
			'trailingslashit',
			[
				'times'      => 1,
				'args'       => [ $path ],
				'return_arg' => 0,
			]
		);

		$this->wpMockFunction(
			'RtCamp\GoogleLogin\plugin',
			[],
			1,
			function () use ( $path ) {
				return (object) [
					'url'        => 'https://example.com/',
					'assets_dir' => $path,
				];
			}
		);

		$this->wpMockFunction(
			'wp_enqueue_script',
			[ 'google-login-block' ],
			1,
			true
		);

		$this->assert_mock->expects( $this->once() )->method( 'register_login_styles' );
		$this->assert_mock->expects( $this->once() )->method( 'register_script' )
						->with(
							'google-login-block',
							'build/js/block-button.js',
							[
								'wp-blocks',
								'wp-element',
								'wp-editor',
								'wp-components',
							],
							filemtime( $path . 'build/js/block-button.js' ),
							false
						);

		$this->testee->enqueue_block_editor_assets();
	}

	/**
	 * @covers ::register
	 */
	public function testRegister() {
		$this->wpMockFunction(
			'register_block_type',
			[
				'google-login/login-button',
				[
					'editor_style'    => 'login-with-google',
					'style'           => 'login-with-google',
					'render_callback' => [ $this->testee, 'render_login_button' ],
					'attributes'      => [
						'buttonText'   => [
							'type' => 'string',
						],
						'forceDisplay' => [
							'type'    => 'boolean',
							'default' => false,
						],
					],
				],
			],
			1,
			true
		);

		$this->testee->register();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::render_login_button, ::markup
	 */
	public function testRenderLoginButton() {

		$mock_attributes = [
			'login_url'       => '#',
			'custom_btn_text' => 'test',
			'force_display'   => false,
		];

		$this->wpMockFunction(
			'is_user_logged_in',
			[],
			1,
			false
		);

		$this->wpMockFunction(
			'wp_parse_args',
			[],
			1,
			$mock_attributes
		);

		$this->wpMockFunction(
			'wp_kses_post',
			[],
			1,
			''
		);

		$path = dirname( __DIR__, 4 ) . '/templates/';

		$this->wpMockFunction(
			'RtCamp\GoogleLogin\plugin',
			[],
			1,
			function () use ( $path ) {
				return (object) [
					'template_dir' => $path,
				];
			}
		);

		WP_Mock::userFunction(
			'trailingslashit',
			[
				'times'      => 1,
				'args'       => [ $path ],
				'return_arg' => 0,
			]
		);


		$helper_mock = \Mockery::mock( 'alias:' . Helper::class );
		$helper_mock->expects( 'render_template' )->once()->withArgs(
			[
				$path . 'google-login-button.php',
				$mock_attributes,
				false,
			]
		)->andReturn( '' );

		$markup = $this->testee->render_login_button(
			[
				$path . '/google-login-button.php',
				$mock_attributes,
				false,
			]
		);

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::render_login_button, ::markup
	 */
	public function testRenderLogoutButton() {

		$mock_attributes = [
			'login_url'       => '#',
			'custom_btn_text' => 'test',
			'force_display'   => true,
		];

		$this->wpMockFunction(
			'is_user_logged_in',
			[],
			1,
			false
		);

		$this->wpMockFunction(
			'wp_parse_args',
			[],
			1,
			$mock_attributes
		);

		$this->wpMockFunction(
			'wp_kses_post',
			[],
			1,
			''
		);

		$path = dirname( __DIR__, 4 ) . '/templates/';

		$this->wpMockFunction(
			'RtCamp\GoogleLogin\plugin',
			[],
			1,
			function () use ( $path ) {
				return (object) [
					'template_dir' => $path,
				];
			}
		);

		WP_Mock::userFunction(
			'trailingslashit',
			[
				'times'      => 1,
				'args'       => [ $path ],
				'return_arg' => 0,
			]
		);


		$helper_mock = \Mockery::mock( 'alias:' . Helper::class );
		$helper_mock->expects( 'render_template' )->once()->withArgs(
			[
				$path . 'google-login-button.php',
				$mock_attributes,
				false,
			]
		)->andReturn( '' );

		$markup = $this->testee->render_login_button(
			[
				$path . '/google-login-button.php',
				$mock_attributes,
				false,
			]
		);

		$this->assertConditionsMet();
	}
}

