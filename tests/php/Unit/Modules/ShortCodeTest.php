<?php
/**
 * Test shortcode module class.
 */

declare( strict_types=1 );

namespace RtCamp\GithubLogin\Tests\Unit\Modules;

use RtCamp\GithubLogin\Interfaces\Module as ModuleInterface;
use RtCamp\GithubLogin\Utils\Helper;
use WP_Mock;
use Mockery;
use RtCamp\GithubLogin\Modules\Shortcode as Testee;
use RtCamp\GithubLogin\Tests\TestCase;
use RtCamp\GithubLogin\Utils\GithubClient;
use RtCamp\GithubLogin\Modules\Assets;

/**
 * Class ShortCodeTest
 *
 * @coversDefaultClass \RtCamp\GithubLogin\Modules\Shortcode
 *
 * @package RtCamp\GithubLogin\Tests\Unit\Modules
 */
class ShortCodeTest extends TestCase {
	/**
	 * @var GithubClient
	 */
	private $ghClientMock;

	/**
	 * @var Assets
	 */
	private $assetMock;

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
		$this->ghClientMock = $this->createMock( GithubClient::class );
		$this->assetMock    = $this->createMock( Assets::class );

		$this->testee = new Testee( $this->ghClientMock, $this->assetMock );
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
				'gh_login',
				[
					$this->testee,
					'callback',
				]
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
						'button_text'   => __( 'Login with github', 'github-login' ),
						'force_display' => 'no',
						'redirect_to'   => 'https://example.com/',
					],
					[],
					'gh_login',
				],
				'times'      => 1,
				'return_arg' => 0
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
						'button_text'   => __( 'Login with github', 'github-login' ),
						'force_display' => 'no',
						'redirect_to'   => null,
					],
					[],
					'gh_login',
				],
				'times'      => 1,
				'return_arg' => 0
			]
		);

		$this->wpMockFunction(
			'is_user_logged_in',
			[],
			1,
			false
		);

		WP_Mock::expectFilterAdded( 'rtcamp.github_redirect_url', [ $this->testee, 'redirect_url' ] );

		$this->wpMockFunction(
			'RtCamp\GithubLogin\plugin',
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

		$this->ghClientMock->expects( $this->once() )
		                   ->method( 'authorization_url' )
		                   ->willReturn( 'https://github.com/auth/' );


		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'render_template' )->once()->withArgs(
			[
				'/some/path/templates/github-login-button.php',
				[
					'button_text'   => 'Login with github',
					'force_display' => 'no',
					'redirect_to'   => null,
					'login_url'     => 'https://github.com/auth/',
				],
				false
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

		$this->assetMock->expects( $this->once() )->method( 'enqueue_login_styles' );

		$output = $this->testee->scan_shortcode( 'Hello', 'gh_login', [] );
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


		$this->assetMock->expects( $this->never() )->method( 'enqueue_login_styles' );

		$output = $this->testee->scan_shortcode( 'Hello', 'other_tag', [] );
		$this->assertSame( 'Hello', $output );
	}

	/**
	 * @covers ::redirect_url
	 */
	public function testRedirectURL() {
		$url = 'https://example.com/?redirect_to=https://example.com/wp-admin';
		$this->testee->redirect_uri = 'https://example.com/some-page';

		$this->wpMockFunction(
			'remove_query_arg',
			[
				'redirect_to',
				$url
			],
			1,
			'https://example.com/'
		);

		$this->wpMockFunction(
			'add_query_arg',
			[
				[
					'redirect_to' => 'https://example.com/some-page'
				],
				'https://example.com/'
			],
			1,
			'https://example.com/redirect_to=https://example.com/some-page'
		);

		$r_url = $this->testee->redirect_url( $url );
		$this->assertSame( $r_url, 'https://example.com/redirect_to=https://example.com/some-page' );
	}
}
