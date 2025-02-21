<?php
/**
 * Test for Authenticator class.
 *
 * @package RtCamp\GoogleLogin
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Tests\Unit\Utils;

use WP_Mock;
use Mockery;
use RtCamp\GoogleLogin\Utils\Helper;
use RtCamp\GoogleLogin\Modules\Settings;
use RtCamp\GoogleLogin\Tests\TestCase;
use RtCamp\GoogleLogin\Utils\Authenticator as Testee;

/**
 * Class AuthenticatorTest
 *
 * @coversDefaultClass \RtCamp\GoogleLogin\Utils\Authenticator
 *
 * @package RtCamp\GoogleLogin
 */
class AuthenticatorTest extends TestCase {

	/**
	 * Object under test.
	 *
	 * @var Testee
	 */
	private $testee;

	/**
	 * @var Settings
	 */
	private $settingsMock;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$this->settingsMock = $this->createMock( Settings::class );
		$this->testee       = new Testee( $this->settingsMock );
	}

	/**
	 * @covers ::__construct
	 */
	public function testInstance() {
		$this->assertInstanceOf( Testee::class, $this->testee );
	}

	/**
	 * @covers ::authenticate
	 */
	public function testAuthenticateException() {
		$user = [
			'name' => 'Test'
		];

		$user = (object) $user;

		$this->expectException( \InvalidArgumentException::class );
		$this->testee->authenticate( $user );
	}

	/**
	 * @covers ::authenticate
	 */
	public function testAuthentiCateReturnsUserObject() {
		$user = (object) [
			'name'  => 'Test',
			'email' => 'test@example.com',
		];

		$wp_user = Mockery::mock( \WP_User::class );

		$this->wpMockFunction(
			'email_exists',
			[
				'test@example.com',
			],
			1,
			true
		);

		$this->wpMockFunction(
			'get_user_by',
			[
				'email',
				'test@example.com',
			],
			1,
			$wp_user
		);

		$result = $this->testee->authenticate( $user );
		$this->assertSame( $result, $wp_user );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::authenticate
	 * @covers ::maybe_create_username
	 */
	public function testAuthenticateFilterApplied() {
		$user = (object) [
			'name'  => 'Test',
			'email' => 'test@example.com',
		];

		$this->wpMockFunction(
			'email_exists',
			[
				'test@example.com',
			],
			1,
			false
		);

		$wp_user = Mockery::mock( \WP_User::class );

		$this->wpMockFunction(
			'sanitize_user',
			[
				'test',
				true,
			],
			1,
			'test'
		);

		$helperMock = Mockery::mock( 'alias:' . Helper::class );

		$helperMock->expects( 'unique_username' )
		           ->once()
		           ->withArgs( ['test'] );

		WP_Mock::onFilter( 'rtcamp.google_register_user' )->with( $user )->reply( $wp_user );

		$this->testee->authenticate( $user );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::register
	 */
	public function testRegisterThrowsExceptionForRegistrationDisabled() {
		$this->settingsMock->registration_enabled = false;

		$this->wpMockFunction(
			'get_option',
			[
				'users_can_register',
				false,
			],
			1,
			false
		);

		$this->expectException( \Exception::class );

		$this->testee->register( new \stdClass() );
	}

	/**
	 * @covers ::register
	 */
	public function testRegisterThrowsExceptionForInvalidEmailDomain() {
		$user                                     = (object) [
			'email' => 'test@example.com',
			'login' => 'test',
		];
		$this->settingsMock->registration_enabled = true;
		$this->settingsMock->whitelisted_domains  = 'somedomain.com';

		$this->expectException( \Exception::class );
		$this->testee->register( $user );
	}

	/**
	 * @covers ::register
	 * @covers ::can_register_with_email
	 */
	public function testRegisterReturnsUserObject() {
		$user = (object) [
			'email' => 'test@example.com',
			'login' => 'test',
		];

		$this->settingsMock->registration_enabled = true;
		$this->settingsMock->whitelisted_domains  = 'example.com,somedomain.com';

		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'unique_username' )
		           ->once()
		           ->withArgs( ['test'] )
		           ->andReturn( 'test' );

		$this->wpMockFunction(
			'wp_generate_password',
			[ 18 ],
			1,
			'thisisrandompass'
		);

		$this->wpMockFunction(
			'wp_insert_user',
			[
				[
					'user_login' => 'test',
					'user_pass'  => 'thisisrandompass',
					'user_email' => 'test@example.com',
					'first_name' => '',
					'last_name'  => '',
				]
			],
			1,
			100
		);

		WP_Mock::expectAction( 'rtcamp.google_user_created', 100, $user );

		$wp_user = Mockery::mock( \WP_User::class );

		$this->wpMockFunction(
			'get_user_by',
			[
				'id',
				100,
			],
			1,
			$wp_user
		);

		$received = $this->testee->register( $user );

		$this->assertSame( $wp_user, $received );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::register
	 * @covers ::can_register_with_email
	 */
	public function testRegisterReturnsUserObjectWithFirstNameOnly() {
		$user = (object) [
			'email'      => 'test@example.com',
			'login'      => 'test',
			'given_name' => 'Test',
		];

		$this->settingsMock->registration_enabled = true;
		$this->settingsMock->whitelisted_domains  = 'example.com,somedomain.com';

		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'unique_username' )
		           ->once()
		           ->withArgs( ['test'] )
		           ->andReturn( 'test' );

		$this->wpMockFunction(
			'wp_generate_password',
			[ 18 ],
			1,
			'thisisrandompass'
		);

		$this->wpMockFunction(
			'wp_insert_user',
			[
				[
					'user_login' => 'test',
					'user_pass'  => 'thisisrandompass',
					'user_email' => 'test@example.com',
					'first_name' => 'Test',
					'last_name'  => '',
				]
			],
			1,
			100
		);

		WP_Mock::expectAction( 'rtcamp.google_user_created', 100, $user );

		$wp_user = Mockery::mock( \WP_User::class );

		$this->wpMockFunction(
			'get_user_by',
			[
				'id',
				100,
			],
			1,
			$wp_user
		);

		$received = $this->testee->register( $user );

		$this->assertSame( $wp_user, $received );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::register
	 * @covers ::can_register_with_email
	 */
	public function testRegisterReturnsUserObjectWithFirstLastName() {
		$user = (object) [
			'email'       => 'test@example.com',
			'login'       => 'test',
			'given_name'  => 'Test',
			'family_name' => 'User',
		];

		$this->settingsMock->registration_enabled = true;
		$this->settingsMock->whitelisted_domains  = 'example.com,somedomain.com';

		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'unique_username' )
		           ->once()
		           ->withArgs( ['test'] )
		           ->andReturn( 'test' );

		$this->wpMockFunction(
			'wp_generate_password',
			[ 18 ],
			1,
			'thisisrandompass'
		);

		$this->wpMockFunction(
			'wp_insert_user',
			[
				[
					'user_login' => 'test',
					'user_pass'  => 'thisisrandompass',
					'user_email' => 'test@example.com',
					'first_name' => 'Test',
					'last_name'  => 'User',
				]
			],
			1,
			100
		);

		WP_Mock::expectAction( 'rtcamp.google_user_created', 100, $user );

		$wp_user = Mockery::mock( \WP_User::class );

		$this->wpMockFunction(
			'get_user_by',
			[
				'id',
				100,
			],
			1,
			$wp_user
		);

		$received = $this->testee->register( $user );

		$this->assertSame( $wp_user, $received );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::set_auth_cookies
	 */
	public function testSetAuthCookies() {
		$wp_user             = Mockery::mock( \WP_User::class );
		$wp_user->ID         = 100;
		$wp_user->user_login = 'test';

		$this->wpMockFunction(
			'wp_clear_auth_cookie',
			[],
			1
		);

		$this->wpMockFunction(
			'wp_set_current_user',
			[
				100,
				'test',
			],
			1
		);

		$this->wpMockFunction(
			'wp_set_auth_cookie',
			[
				100,
				false,
			],
			1
		);

		$this->testee->set_auth_cookies( $wp_user );
		$this->assertConditionsMet();
	}
}
