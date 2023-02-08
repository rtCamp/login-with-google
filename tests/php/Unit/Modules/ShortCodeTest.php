<?php
/**
 * Test shortcode module class.
 */

declare( strict_types=1 );

namespace RtCamp\GoogleLogin\Tests\Unit\Modules;

use RtCamp\GoogleLogin\Interfaces\Module as ModuleInterface;
use RtCamp\GoogleLogin\Utils\Helper;
use WP_Mock;
use Mockery;
use RtCamp\GoogleLogin\Modules\Shortcode as Testee;
use RtCamp\GoogleLogin\Tests\TestCase;
use RtCamp\GoogleLogin\Utils\GoogleClient;
use RtCamp\GoogleLogin\Modules\Assets;

/**
 * Class ShortCodeTest
 *
 * @coversDefaultClass \RtCamp\GoogleLogin\Modules\Shortcode
 *
 * @package RtCamp\GoogleLogin\Tests\Unit\Modules
 */
class ShortCodeTest extends TestCase {
	/**
	 * @var GoogleClient
	 */
	private $gh_client_mock;

	/**
	 * @var Assets
	 */
	private $asset_mock;

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
		$this->asset_mock     = $this->createMock( Assets::class );

		$this->testee = new Testee( $this->gh_client_mock, $this->asset_mock );
	}

	/**
	 * @covers ::name
	 */
	public function testName() {
		$this->assertSame( 'shortcode', $this->testee->name() );
	}

	/**
	 * @covers ::__construct
	 */
	public function testImplementsModuleInterface() {
		$this->assertTrue( $this->testee instanceof ModuleInterface );
	}

	/**
	 * @covers ::init
	 */
	public function testInit() {
		$this->wpMockFunction(
			'add_shortcode',
			[
				'google_login',
				[
					$this->testee,
					'callback',
				],
			]
		);

		WP_Mock::expectFilterAdded( 'do_shortcode_tag', [ $this->testee, 'scan_shortcode' ], 10, 3 );

		$this->testee->init();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::callback
	 * @covers ::should_display
	 */
	public function testCallbackWhenUserIsLoggedIn() {

		$this->wpMockFunction(
			'get_permalink',
			[],
			1,
			'https://example.com/'
		);

		WP_Mock::userFunction(
			'shortcode_atts',
			[
				'args'       => [
					[
						'button_text'   => __( 'Login with google', 'login-with-google' ),
						'force_display' => 'no',
						'redirect_to'   => 'https://example.com/',
					],
					[],
					'google_login',
				],
				'times'      => 1,
				'return_arg' => 0,
			]
		);

		$this->wpMockFunction(
			'is_user_logged_in',
			[],
			1,
			true
		);

		$shortcode = $this->testee->callback();

		$this->assertSame( '', $shortcode );
	}

	/**
	 * @covers ::callback
	 * @covers ::should_display
	 */
	public function testCallbackWhenUserIsLoggedOut() {

		WP_Mock::userFunction(
			'shortcode_atts',
			[
				'args'       => [
					[
						'button_text'   => __( 'Login with google', 'login-with-google' ),
						'force_display' => 'no',
						'redirect_to'   => null,
					],
					[],
					'google_login',
				],
				'times'      => 1,
				'return_arg' => 0,
			]
		);

		$this->wpMockFunction(
			'is_user_logged_in',
			[],
			1,
			false
		);

		WP_Mock::expectFilterAdded( 'rtcamp.google_redirect_url', [ $this->testee, 'redirect_url' ] );

		$this->wpMockFunction(
			'RtCamp\GoogleLogin\plugin',
			[],
			1,
			(object) [
				'template_dir' => '/some/path/templates',
			]
		);

		$this->wpMockFunction(
			'trailingslashit',
			[],
			1,
			'/some/path/templates/'
		);

		$this->gh_client_mock->expects( $this->once() )
							->method( 'authorization_url' )
							->willReturn( 'https://google.com/auth/' );


		$helper_mock = Mockery::mock( 'alias:' . Helper::class );
		$helper_mock->expects( 'render_template' )->once()->withArgs(
			[
				'/some/path/templates/google-login-button.php',
				[
					'button_text'   => 'Login with google',
					'force_display' => 'no',
					'redirect_to'   => null,
					'login_url'     => 'https://google.com/auth/',
				],
				false,
			]
		)->andReturn( '' );

		$this->testee->callback();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::scan_shortcode
	 * @covers ::should_display
	 */
	public function testScanShortcodeForSinglePage() {

		$this->wpMockFunction(
			'is_single',
			[],
			1,
			true
		);

		$this->wpMockFunction(
			'is_page',
			[],
			0,
			true
		);

		$this->wpMockFunction(
			'is_user_logged_in',
			[],
			1,
			false
		);

		$this->asset_mock->expects( $this->once() )->method( 'enqueue_login_styles' );

		$output = $this->testee->scan_shortcode( 'Hello', 'google_login', [] );
		$this->assertSame( 'Hello', $output );
	}

	/**
	 * @covers ::scan_shortcode
	 * @covers ::should_display
	 */
	public function testScanShortcodeForDifferentTag() {

		$this->wpMockFunction(
			'is_single',
			[],
			1,
			true
		);

		$this->wpMockFunction(
			'is_user_logged_in',
			[],
			0,
			false
		);

		$this->asset_mock->expects( $this->never() )->method( 'enqueue_login_styles' );

		$output = $this->testee->scan_shortcode( 'Hello', 'other_tag', [] );
		$this->assertSame( 'Hello', $output );
	}

	/**
	 * @covers ::redirect_url
	 */
	public function testRedirectURL() {

		$url                        = 'https://example.com/?redirect_to=https://example.com/wp-admin';
		$this->testee->redirect_uri = 'https://example.com/some-page';

		$this->wpMockFunction(
			'remove_query_arg',
			[
				'redirect_to',
				$url,
			],
			1,
			'https://example.com/'
		);

		$r_url = $this->testee->redirect_url( $url );
		$this->assertSame( $r_url, 'https://example.com/' );
	}

	/**
	 * @covers ::state_redirect
	 */
	public function testStateRedirectWithRedirectUrl() {

		$this->testee->redirect_uri = 'https://example.com';

		$state = [
			'provider'    => 'google',
			'redirect_to' => 'https://example.com',
		];

		$expected = $this->testee->state_redirect( $state );
		$this->assertSame( $expected, $state );
	}

	/**
	 * @covers ::state_redirect
	 */
	public function testStateRedirectWithoutRedirectUrl() {

		$this->testee->redirect_uri = null;

		$state = [
			'provider' => 'google',
		];

		$expected = $this->testee->state_redirect( $state );
		$this->assertSame( $expected, $state );
	}
}
