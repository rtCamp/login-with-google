<?php
/**
 * Test login module class.
 */

declare( strict_types=1 );

namespace RtCamp\GoogleLogin\Tests\Unit\Modules;

use Exception;
use RtCamp\GoogleLogin\Container;
use RtCamp\GoogleLogin\Plugin;
use WP_Mock;
use Mockery;
use RtCamp\GoogleLogin\Utils\Helper;
use RtCamp\GoogleLogin\Utils\GoogleClient;
use RtCamp\GoogleLogin\Modules\Settings;
use RtCamp\GoogleLogin\Modules\Login as Testee;
use RtCamp\GoogleLogin\Tests\TestCase;
use RtCamp\GoogleLogin\Interfaces\Module as ModuleInterface;

/**
 * Class LoginTest
 *
 * @coversDefaultClass \RtCamp\GoogleLogin\Modules\Login
 *
 * @package RtCamp\GoogleLogin\Tests\Unit\Modules
 */
class LoginTest extends TestCase {
	/**
	 * @var GoogleClient
	 */
	private $ghClientMock;

	/**
	 * @var Settings
	 */
	private $settingsMock;

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
		$this->ghClientMock = $this->createMock( GoogleClient::class );
		$this->settingsMock = $this->createMock( Settings::class );

		$this->testee = new Testee( $this->ghClientMock, $this->settingsMock );
	}

	/**
	 * @covers ::name
	 */
	public function testName() {
		$this->assertSame( 'login_flow', $this->testee->name() );
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
		WP_Mock::expectActionAdded( 'login_form', [ $this->testee, 'login_button' ] );
		WP_Mock::expectActionAdded( 'authenticate', [ $this->testee, 'authenticate' ] );
		WP_Mock::expectActionAdded( 'rtcamp.register_user', [ $this->testee, 'register' ] );
		WP_Mock::expectActionAdded( 'rtcamp.google_redirect_url', [ $this->testee, 'redirect_url' ] );
		WP_Mock::expectActionAdded( 'rtcamp.google_user_created', [ $this->testee, 'user_meta' ], 10, 2 );
		WP_Mock::expectFilterAdded( 'rtcamp.google_user_profile', [ $this->testee, 'user_login' ] );
		WP_Mock::expectFilterAdded( 'rtcamp.google_login_state', [ $this->testee, 'state_redirect' ] );
		WP_Mock::expectActionAdded( 'wp_login', [ $this->testee, 'login_redirect' ] );

		$this->testee->init();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::login_button
	 */
	public function testLoginButton() {
		$pluginMock               = $this->createMock( Plugin::class );
		$pluginMock->template_dir = 'https://example.com/templates/';

		$containerMock = $this->createMock( Container::class );
		$containerMock->expects( $this->once() )
		              ->method( 'get' )
		              ->willReturn( $this->ghClientMock );

		$pluginMock->expects( $this->once() )
		           ->method( 'container' )
		           ->willReturn( $containerMock );

		$this->ghClientMock->expects( $this->once() )
		                   ->method( 'authorization_url' )
		                   ->willReturn( 'https://google.com/auth/' );

		$this->wpMockFunction(
			'RtCamp\GoogleLogin\plugin',
			[],
			2,
			$pluginMock
		);

		WP_Mock::userFunction(
			'trailingslashit',
			[
				'times'      => 1,
				'args'       => [
					'https://example.com/templates/'
				],
				'return_arg' => 0
			]
		);

		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'render_template' )->once()->withArgs(
			[
				'https://example.com/templates/google-login-button.php',
				[
					'login_url' => 'https://google.com/auth/',
				]
			]
		);

		$this->testee->login_button();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::authenticate
	 */
	public function testAuthenticationForNoCode() {
		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'code',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( null );

		$wp_user_mock        = new \stdClass();
		$wp_user_mock->login = 'test';
		$wp_user_mock->email = 'test@unit.com';

		$returned     = $this->testee->authenticate( $wp_user_mock );

		$this->assertSame( $returned, $wp_user_mock );
	}

	/**
	 * @covers ::authenticate
	 */
	public function testAuthenticationForAlreadyAuthenticatedUser() {
		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'filter_input' )->never()->withArgs(
			[
				INPUT_GET,
				'code',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( null );

		$wp_user_mock = Mockery::mock( 'WP_User' );
		$returned     = $this->testee->authenticate( $wp_user_mock );

		$this->assertSame( $returned, $wp_user_mock );
	}

	/**
	 * @covers ::authenticate
	 */
	public function testAuthenticationForDifferentProvider() {
		$state = [
			'nonce'    => '1234',
			'provider' => 'some_other',
		];

		$state = base64_encode( json_encode( $state ) );

		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'code',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( 'test_code' );

		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'state',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( $state );

		$wp_user_mock        = new \stdClass();
		$wp_user_mock->login = 'test';
		$wp_user_mock->email = 'test@unit.com';

		$returned     = $this->testee->authenticate( $wp_user_mock );

		$this->assertSame( $returned, $wp_user_mock );
	}

	/**
	 * @covers ::authenticate
	 */
	public function testAuthenticationWithForgedState() {
		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'code',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( 'abc' );

		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'state',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( 'eyJwcm92aWRlciI6ImdpdGh1YiJ9' );

		$returned = $this->testee->authenticate();

		$this->assertSame( null, $returned );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::authenticate
	 */
	public function testAuthenticationWhenUserExists() {
		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'code',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( 'abc' );

		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'state',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( 'eyJwcm92aWRlciI6Imdvb2dsZSIsIm5vbmNlIjoidGVzdG5vbmNlIn0=' );

		$this->ghClientMock->expects( $this->never() )
		                   ->method( 'state' )
		                   ->willReturn( 'eyJwcm92aWRlciI6Imdvb2dsZSIsIm5vbmNlIjoidGVzdG5vbmNlIn0=' );

		$this->wpMockFunction(
			'wp_verify_nonce',
			[
				'testnonce',
				'login_with_google',
			],
			1,
			true
		);

		$this->ghClientMock->expects( $this->once() )
		                   ->method( 'set_access_token' )
		                   ->with( 'abc' );

		$user = (object) [
			'email' => 'fakeemail@domain.com',
		];

		$this->ghClientMock->expects( $this->once() )
		                   ->method( 'user' )
		                   ->willReturn( $user );

		WP_Mock::expectFilter( 'rtcamp.google_user_profile', $user );

		$this->wpMockFunction(
			'email_exists',
			[
				'fakeemail@domain.com'
			],
			1,
			true
		);

		$userMock = Mockery::mock( 'WP_User' );

		$this->wpMockFunction(
			'get_user_by',
			[
				'email',
				'fakeemail@domain.com'
			],
			1,
			$userMock
		);

		$returned = $this->testee->authenticate();
		$this->assertSame( $returned, $userMock );

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::authenticate
	 */
	public function testAuthenticationWhenUserDoesNotExist() {
		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'code',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( 'abc' );

		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'state',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( 'eyJwcm92aWRlciI6Imdvb2dsZSIsIm5vbmNlIjoidGVzdG5vbmNlIn0=' );

		$this->ghClientMock->expects( $this->never() )
		                   ->method( 'state' )
		                   ->willReturn( 'eyJwcm92aWRlciI6Imdvb2dsZSIsIm5vbmNlIjoidGVzdG5vbmNlIn0=' );

		$this->wpMockFunction(
			'wp_verify_nonce',
			[
				'testnonce',
				'login_with_google',
			],
			1,
			true
		);

		$this->ghClientMock->expects( $this->once() )
		                   ->method( 'set_access_token' )
		                   ->with( 'abc' );

		$user = (object) [
			'email' => 'fakeemail@domain.com',
		];

		$this->ghClientMock->expects( $this->once() )
		                   ->method( 'user' )
		                   ->willReturn( $user );

		WP_Mock::expectFilter( 'rtcamp.google_user_profile', $user );

		$this->wpMockFunction(
			'email_exists',
			[
				'fakeemail@domain.com'
			],
			1,
			false
		);

		$userMock = Mockery::mock( 'WP_User' );

		$this->wpMockFunction(
			'get_user_by',
			[
				'email',
				'fakeemail@domain.com'
			],
			0,
			$userMock
		);

		WP_Mock::expectFilter( 'rtcamp.register_user', $user );

		$returned = $this->testee->authenticate();
		$this->assertSame( $returned, $user );

		$this->assertConditionsMet();
	}

	/**
	 * @group failing
	 * @covers ::authenticate
	 */
	public function testAuthenticationCapturesExceptions() {
		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'code',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( 'abc' );

		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'state',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( 'eyJwcm92aWRlciI6Imdvb2dsZSIsIm5vbmNlIjoidGVzdG5vbmNlIn0=' );

		$this->ghClientMock->expects( $this->never() )
		                   ->method( 'state' )
		                   ->willReturn( 'eyJwcm92aWRlciI6Imdvb2dsZSIsIm5vbmNlIjoidGVzdG5vbmNlIn0=' );

		$this->wpMockFunction(
			'wp_verify_nonce',
			[
				'testnonce',
				'login_with_google',
			],
			1,
			true
		);

		$this->ghClientMock->expects( $this->once() )
		                   ->method( 'set_access_token' )
		                   ->with( 'abc' )
		                   ->willThrowException( new Exception( 'Exception for test' ) );

		Mockery::mock( 'WP_Error' );
		$returned = $this->testee->authenticate();

		$this->assertInstanceOf( 'WP_Error', $returned );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::register
	 */
	public function testRegisterThrowsExceptionWhenRegistrationIsDisabled() {
		$this->settingsMock->registration_enabled = false;

		$this->wpMockFunction(
			'get_option',
			[],
			1,
			false
		);

		$user = new \stdClass();

		$this->expectException( Exception::class );
		$this->testee->register( $user );
	}

	/**
	 * @covers ::register
	 * @covers ::can_register_with_email
	 */
	public function testRegisterWithWhitelistedDomains() {
		$this->settingsMock->registration_enabled = true;
		$this->settingsMock->whitelisted_domains  = [
			'example.com',
			'domain.com',
		];

		$this->settingsMock->whitelisted_domains = implode( PHP_EOL, $this->settingsMock->whitelisted_domains );

		$this->wpMockFunction(
			'get_option',
			[],
			0,
			false
		);

		$user = new \stdClass();
		$user->email = 'login@example.com';
		$user->login = 'login';

		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'unique_username' )->once()->withArgs(
			[
				'login',
			]
		)->andReturn( 'login122' );

		$this->wpMockFunction(
			'wp_generate_password',
			[
				18,
			],
			1,
			'123456'
		);

		$this->wpMockFunction(
			'wp_insert_user',
			[
				[
					'user_login' => 'login122',
					'user_pass'  => '123456',
					'user_email' => 'login@example.com',
				],
			],
			1,
			20
		);

		WP_Mock::expectAction( 'rtcamp.google_user_created', 20, $user );

		$wp_user = Mockery::mock( 'WP_User' );

		$this->wpMockFunction(
			'get_user_by',
			[
				'id',
				20,
			],
			1,
			$wp_user
		);

		$userObject = $this->testee->register( $user );

		$this->assertSame( $wp_user, $userObject );
	}

	/**
	 * @covers ::register
	 * @covers ::can_register_with_email
	 */
	public function testRegisterThrowsExceptionForInvalidDomains() {
		$this->settingsMock->registration_enabled = true;
		$this->settingsMock->whitelisted_domains  = [
			'example.com',
			'domain.com',
		];

		$this->settingsMock->whitelisted_domains = implode( PHP_EOL, $this->settingsMock->whitelisted_domains );

		$this->wpMockFunction(
			'get_option',
			[],
			0,
			false
		);

		$user = new \stdClass();
		$user->email = 'login@other.com';
		$user->login = 'login';

		$this->expectException( Exception::class );
		$this->testee->register( $user );
	}

	/**
	 * @covers ::user_meta
	 */
	public function testUserMeta() {
		$user = new \stdClass();
		$user->login = 'login';

		$this->wpMockFunction(
			'add_user_meta',
			[
				20,
				'oauth_user',
				1,
				true,
			],
			1,
			true
		);

		$this->wpMockFunction(
			'add_user_meta',
			[
				20,
				'oauth_provider',
				'google',
				true,
			],
			1,
			true
		);

		$this->testee->user_meta( 20, $user );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::redirect_url
	 */
	public function testRedirectURLRetuensWithQueryParam() {
		$url = 'https://example.com/?redirect_to=https://example.com/wp-admin';

		$this->wpMockFunction(
			'remove_query_arg',
			[
				'redirect_to',
				$url,
			],
			1,
			'https://example.com/'
		);

		$redirect = $this->testee->redirect_url( $url );
		$this->assertSame( 'https://example.com/', $redirect );
	}

	/**
	 * @covers ::state_redirect
	 */
	public function testStateRedirect() {
		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'redirect_to',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( 'https://example.com/state-page' );

		$state_data = $this->testee->state_redirect( [] );

		$this->assertIsArray( $state_data );
		$this->assertContains( 'https://example.com/state-page', $state_data );
	}

	/**
	 * @covers ::login_redirect
	 */
	public function testLoginRedirectWithNotStateAuthenticated() {
		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'state',
				FILTER_SANITIZE_STRING
			]
		)->andReturn( [] );

		$data = $this->testee->login_redirect();
		$this->assertNull( $data );
	}

	/**
	 * @covers ::user_login
	 */
	public function testUserLoginWithoutDefault() {
		$user = new \stdClass();
		$user->url = 'https://example.com';

		$expected = $this->testee->user_login( $user );
		$this->assertSame( $expected->url, 'https://example.com' );

		$login = ! empty( $expected->login ) ?? false;
		$this->assertfalse( $login );
	}

	/**
	 * @covers ::user_login
	 */
	public function testUserLoginWithDefault() {
		$user = new \stdClass();
		$user->email = 'someone@example.com';

		$this->wpMockFunction(
			'sanitize_user',
			[
				'someone',
				true,
			],
			1,
			'someone'
		);

		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'unique_username' )->once()->withArgs(
			[
				'someone',
			]
		)->andReturn( 'someone1' );

		$expected = $this->testee->user_login( $user );

		$this->assertEquals( $expected->login, 'someone1' );
	}
}
