<?php
/**
 * Test OneTapLogin module class.
 */

declare( strict_types=1 );

namespace RtCamp\GoogleLogin\Tests\Unit\Modules;

use Exception;
use Mockery;
use RtCamp\GoogleLogin\Modules\Settings;
use RtCamp\GoogleLogin\Tests\PrivateAccess;
use RtCamp\GoogleLogin\Utils\Authenticator;
use RtCamp\GoogleLogin\Utils\GoogleClient;
use RtCamp\GoogleLogin\Utils\Helper;
use RtCamp\GoogleLogin\Utils\TokenVerifier;
use WP_Mock;
use RtCamp\GoogleLogin\Interfaces\Module as ModuleInterface;
use RtCamp\GoogleLogin\Tests\TestCase;
use RtCamp\GoogleLogin\Modules\OneTapLogin as Testee;

/**
 * Class OneTapLoginTest
 *
 * @coversDefaultClass \RtCamp\GoogleLogin\Modules\OneTapLogin
 *
 * @package RtCamp\GoogleLogin\Tests\Unit\Modules
 */
class OneTapLoginTest extends TestCase {

	use PrivateAccess;

	/**
	 * Object in test.
	 *
	 * @var Testee
	 */
	private $testee;

	/**
	 * @var Settings
	 */
	private $settingsMock;

	/**
	 * @var TokenVerifier
	 */
	private $tokenVerifierMock;

	/**
	 * @var GoogleClient
	 */
	private $ghClientMock;

	/**
	 * @var Authenticator
	 */
	private $authenticatorMock;

	/**
	 * Run before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		$this->settingsMock 	 = $this->createMock( Settings::class );
		$this->tokenVerifierMock = $this->createMock( TokenVerifier::class );
		$this->ghClientMock      = $this->createMock( GoogleClient::class );
		$this->authenticatorMock = $this->createMock( Authenticator::class );

		$this->testee = new Testee($this->settingsMock, $this->tokenVerifierMock, $this->ghClientMock, $this->authenticatorMock);
	}

	/**
	 * @covers ::name
	 */
	public function testName() {
		$this->assertSame( 'one_tap_login', $this->testee->name() );
	}

	public function testImplementsModuleInterface() {
		$this->assertTrue( $this->testee instanceof ModuleInterface );
	}

	/**
	 * @covers ::init
	 */
	public function testInit() {
		$this->set_private_property(
			$this->testee,
			'settings',
			(object) [ 'one_tap_login' => true, 'one_tap_login_screen' => 'sitewide', ]
		);

		WP_Mock::expectActionAdded(
			'login_enqueue_scripts',
			[ $this->testee, 'one_tap_scripts' ]
		);

		WP_Mock::expectActionAdded(
			'login_footer',
			[ $this->testee, 'one_tap_prompt' ]
		);

		WP_Mock::expectActionAdded(
			'wp_ajax_nopriv_validate_id_token',
			[ $this->testee, 'validate_token' ]
		);

		WP_Mock::expectActionAdded(
			'rtcamp.id_token_verified',
			[ $this->testee, 'authenticate' ]
		);

		WP_Mock::expectActionAdded(
			'init',
			function () { }
		);

		$this->testee->init();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::one_tap_prompt
	 */
	public function testOneTapPrompt() {
		$this->wpMockFunction( 'wp_login_url', [], 1, '#demo_url#' );

		ob_start();
		$this->testee->one_tap_prompt();
		$html = ob_get_contents();
		ob_end_clean();

		$this->assertIsString( $html );

		$this->assertStringContainsString( '#demo_url#', $html );
	}

	/**
	 * @covers ::one_tap_scripts
	 */
	public function testOneTapScripts() {
		$this->wpMockFunction(
			'RtCamp\GoogleLogin\plugin',
			[],
			3,
			(object) [ 'path' => '/', 'url' => 'https://example.com' ]
		);

		$this->wpMockFunction( 'get_option', [ 'home', '' ], 1, 'https://example.com' );

		$this->wpMockFunction(
			'wp_add_inline_script',
			[
				'login-with-google-one-tap-js',
				WP_Mock\Functions::type('string'),
				'before',
			],
			1
		);

		ob_start();
		$this->testee->one_tap_scripts();
		$printed = ob_get_contents();
		ob_end_clean();

		$this->assertEmpty($printed);
	}

	/**
	 * @covers ::validate_token
	 */
	public function testValidateTokenError() {
		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_POST,
				'token',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( 'abc' );

		$this->tokenVerifierMock->expects( $this->once() )
			->method( 'verify_token' )
			->with( 'abc' )
			->willReturn( false );

		$this->wpMockFunction(
			'__',
			[ 'Cannot verify the credentials', 'login-with-google' ],
			1,
			'Cannot verify the credentials'
		);

		$this->wpMockFunction(
			'wp_send_json_error',
			[ 'Cannot verify the credentials' ],
			1,
			function () {
				echo '{"error":"Cannot verify the credentials"}';
			}
		);

		ob_start();
		$this->testee->validate_token();
		$json = ob_get_contents();
		ob_end_clean();

		$this->assertSame( '{"error":"Cannot verify the credentials"}', $json );
	}

	/**
	 * @covers ::validate_token
	 */
	public function testValidateToken() {
		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_POST,
				'token',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( 'abc' );

		$this->tokenVerifierMock->expects( $this->once() )
			->method( 'verify_token' )
			->with( 'abc' )
			->willReturn( true );

		$this->wpMockFunction(
			'wp_send_json_error',
			[ WP_Mock\Functions::type('string') ],
			1,
			function () {
				echo '{"error":"died"}';
			}
		);

		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_POST,
				'state',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( 'eyJwcm92aWRlciI6Imdvb2dsZSIsInJlZGlyZWN0X3RvIjoiIn0=' );

		$this->wpMockFunction( 'wp_send_json_success', [ [ 'redirect' => '' ] ], 1, function () {
			// Prevent executing "die" function.
			throw new Exception('died');
		} );

		ob_start();
		$this->testee->validate_token();
		$json = ob_get_contents();
		ob_end_clean();

		$this->assertSame( '{"error":"died"}' , $json );
	}

	/**
	 * @covers ::authenticate
	 */
	public function testAuthenticateNullUser() {
		$this->expectException( Exception::class );
		$this->testee->authenticate();
	}

	/**
	 * @covers ::authenticate
	 */
	public function testAuthenticate() {
		$user = (object) [
			'name'  => 'Test',
			'email' => 'test@example.com',
		];

		$userMock = Mockery::mock( 'WP_User' );

		$this->tokenVerifierMock->expects( $this->once() )
			->method( 'current_user' )
			->willReturn( $user );

		$this->authenticatorMock->expects( $this->once() )
			->method( 'authenticate' )
			->willReturn( $userMock );

		$this->testee->authenticate();
	}
}
