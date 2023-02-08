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
use RtCamp\GoogleLogin\Utils\Authenticator;

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
	private $gh_client_mock;

	/**
	 * @var Settings
	 */
	private $authenticator_mock;

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

		$this->gh_client_mock     = $this->createMock( GoogleClient::class );
		$this->authenticator_mock = $this->createMock( Authenticator::class );

		$this->testee = new Testee( $this->gh_client_mock, $this->authenticator_mock );
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
		WP_Mock::expectActionAdded( 'authenticate', [ $this->testee, 'authenticate' ], 20 );
		WP_Mock::expectActionAdded( 'rtcamp.google_register_user', [ $this->authenticator_mock, 'register' ] );
		WP_Mock::expectActionAdded( 'rtcamp.google_redirect_url', [ $this->testee, 'redirect_url' ] );
		WP_Mock::expectActionAdded( 'rtcamp.google_user_created', [ $this->testee, 'user_meta' ] );
		WP_Mock::expectFilterAdded( 'rtcamp.google_login_state', [ $this->testee, 'state_redirect' ] );
		WP_Mock::expectActionAdded( 'wp_login', [ $this->testee, 'login_redirect' ] );

		$this->testee->init();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::login_button
	 */
	public function testLoginButton() {

		$plugin_mock               = $this->createMock( Plugin::class );
		$plugin_mock->template_dir = 'https://example.com/templates/';

		$container_mock = $this->createMock( Container::class );
		$container_mock->expects( $this->once() )
					->method( 'get' )
					->willReturn( $this->gh_client_mock );

		$plugin_mock->expects( $this->once() )
					->method( 'container' )
					->willReturn( $container_mock );

		$this->gh_client_mock->expects( $this->once() )
							->method( 'authorization_url' )
							->willReturn( 'https://google.com/auth/' );

		$this->wpMockFunction(
			'RtCamp\GoogleLogin\plugin',
			[],
			2,
			$plugin_mock
		);

		WP_Mock::userFunction(
			'trailingslashit',
			[
				'times'      => 1,
				'args'       => [
					'https://example.com/templates/',
				],
				'return_arg' => 0,
			]
		);

		$helper_mock = Mockery::mock( 'alias:' . Helper::class );
		$helper_mock->expects( 'render_template' )->once()->withArgs(
			[
				'https://example.com/templates/google-login-button.php',
				[
					'login_url' => 'https://google.com/auth/',
				],
			]
		);

		$this->testee->login_button();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::authenticate
	 */
	public function testAuthenticationForNoCode() {

		$helper_mock = Mockery::mock( 'alias:' . Helper::class );
		$helper_mock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'code',
				FILTER_SANITIZE_STRING,
			]
		)->andReturn( null );

		$wp_user_mock        = new \stdClass();
		$wp_user_mock->login = 'test';
		$wp_user_mock->email = 'test@unit.com';

		$returned = $this->testee->authenticate( $wp_user_mock );

		$this->assertSame( $returned, $wp_user_mock );
	}

	/**
	 * @covers ::authenticate
	 */
	public function testAuthenticationForAlreadyAuthenticatedUser() {

		$helper_mock = Mockery::mock( 'alias:' . Helper::class );
		$helper_mock->expects( 'filter_input' )->never()->withArgs(
			[
				INPUT_GET,
				'code',
				FILTER_SANITIZE_STRING,
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

		$state = base64_encode( wp_json_encode( $state ) );

		$helper_mock = Mockery::mock( 'alias:' . Helper::class );
		$helper_mock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'code',
				FILTER_SANITIZE_STRING,
			]
		)->andReturn( 'test_code' );

		$helper_mock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'state',
				FILTER_SANITIZE_STRING,
			]
		)->andReturn( $state );

		$wp_user_mock        = new \stdClass();
		$wp_user_mock->login = 'test';
		$wp_user_mock->email = 'test@unit.com';

		$returned = $this->testee->authenticate( $wp_user_mock );

		$this->assertSame( $returned, $wp_user_mock );
	}

	/**
	 * @covers ::authenticate
	 */
	public function testAuthenticationWithForgedState() {

		$helper_mock = Mockery::mock( 'alias:' . Helper::class );
		$helper_mock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'code',
				FILTER_SANITIZE_STRING,
			]
		)->andReturn( 'abc' );

		$helper_mock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'state',
				FILTER_SANITIZE_STRING,
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

		$helper_mock = Mockery::mock( 'alias:' . Helper::class );
		$helper_mock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'code',
				FILTER_SANITIZE_STRING,
			]
		)->andReturn( 'abc' );

		$helper_mock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'state',
				FILTER_SANITIZE_STRING,
			]
		)->andReturn( 'eyJwcm92aWRlciI6Imdvb2dsZSIsIm5vbmNlIjoidGVzdG5vbmNlIn0=' );

		$this->gh_client_mock->expects( $this->never() )
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

		$this->gh_client_mock->expects( $this->once() )
							->method( 'set_access_token' )
							->with( 'abc' );

		$user = (object) [
			'email' => 'fakeemail@domain.com',
		];

		$this->gh_client_mock->expects( $this->once() )
							->method( 'user' )
							->willReturn( $user );


		$user_mock = Mockery::mock( 'WP_User' );
		$this->authenticator_mock->expects( $this->once() )
								->method( 'authenticate' )
								->willReturn( $user_mock );

		$returned = $this->testee->authenticate();
		$this->assertSame( $returned, $user_mock );

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::authenticate
	 */
	public function testAuthenticationCapturesExceptions() {

		$helper_mock = Mockery::mock( 'alias:' . Helper::class );
		$helper_mock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'code',
				FILTER_SANITIZE_STRING,
			]
		)->andReturn( 'abc' );

		$helper_mock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'state',
				FILTER_SANITIZE_STRING,
			]
		)->andReturn( 'eyJwcm92aWRlciI6Imdvb2dsZSIsIm5vbmNlIjoidGVzdG5vbmNlIn0=' );

		$this->gh_client_mock->expects( $this->never() )
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

		$this->gh_client_mock->expects( $this->once() )
							->method( 'set_access_token' )
							->with( 'abc' )
							->willThrowException( new Exception( 'Exception for test' ) );

		Mockery::mock( 'WP_Error' );
		$returned = $this->testee->authenticate();

		$this->assertInstanceOf( 'WP_Error', $returned );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::user_meta
	 */
	public function testUserMeta() {

		$user        = new \stdClass();
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

		$this->testee->user_meta( 20 );
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
	public function testStateRedirectWithRedirectTo() {

		$helper_mock = Mockery::mock( 'alias:' . Helper::class );
		$helper_mock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'redirect_to',
				FILTER_SANITIZE_STRING,
			]
		)->andReturn( 'https://example.com/state-page' );

		$state_data = $this->testee->state_redirect( [] );

		$this->assertIsArray( $state_data );
		$this->assertContains( 'https://example.com/state-page', $state_data );
	}

	/**
	 * @covers ::state_redirect
	 */
	public function testStateRedirectWithoutRedirectTo() {

		$helper_mock = Mockery::mock( 'alias:' . Helper::class );
		$helper_mock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'redirect_to',
				FILTER_SANITIZE_STRING,
			]
		)->andReturn( null );

		$this->wpMockFunction(
			'admin_url',
			[],
			1,
			'https://example.com/login'
		);

		WP_Mock::expectFilter( 'rtcamp.google_default_redirect', 'https://example.com/login' );
		$state_data = $this->testee->state_redirect( [] );
		$this->assertIsArray( $state_data );
		$this->assertContains( 'https://example.com/login', $state_data );
	}

	/**
	 * @covers ::login_redirect
	 */
	public function testLoginRedirectWithNotStateAuthenticated() {

		$helper_mock = Mockery::mock( 'alias:' . Helper::class );
		$helper_mock->expects( 'filter_input' )->once()->withArgs(
			[
				INPUT_GET,
				'state',
				FILTER_SANITIZE_STRING,
			]
		)->andReturn( [] );

		$data = $this->testee->login_redirect();
		$this->assertNull( $data );
	}
}
