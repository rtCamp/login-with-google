<?php
/**
 * Test OneTapLogin module class.
 *
 * @package RtCamp\GoogleLogin\Tests\Unit\Modules
 */

declare( strict_types=1 );

namespace RtCamp\GoogleLogin\Tests\Unit\Modules;

use WP_Mock;
use Mockery;
use Exception;
use RtCamp\GoogleLogin\Tests\TestCase;
use RtCamp\GoogleLogin\Modules\OneTapLogin;
use RtCamp\GoogleLogin\Modules\Settings;
use RtCamp\GoogleLogin\Utils\TokenVerifier;
use RtCamp\GoogleLogin\Utils\GoogleClient;
use RtCamp\GoogleLogin\Utils\Authenticator;
use RtCamp\GoogleLogin\Utils\Helper;

/**
 * Class OneTapLoginTest
 *
 * @coversDefaultClass \RtCamp\GoogleLogin\Modules\OneTapLogin
 */
class OneTapLoginTest extends TestCase {

	/**
	 * Settings mock.
	 *
	 * @var Settings|\Mockery\MockInterface
	 */
	private $settingsMock;

	/**
	 * TokenVerifier mock.
	 *
	 * @var TokenVerifier|\Mockery\MockInterface
	 */
	private $verifierMock;

	/**
	 * GoogleClient mock.
	 *
	 * @var GoogleClient|\Mockery\MockInterface
	 */
	private $clientMock;

	/**
	 * Authenticator mock.
	 *
	 * @var Authenticator|\Mockery\MockInterface
	 */
	private $authenticatorMock;

	/**
	 * OneTapLogin instance.
	 *
	 * @var OneTapLogin
	 */
	private $testee;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->settingsMock      = Mockery::mock( Settings::class );
		$this->verifierMock      = Mockery::mock( TokenVerifier::class );
		$this->clientMock        = Mockery::mock( GoogleClient::class );
		$this->authenticatorMock = Mockery::mock( Authenticator::class );

		$this->testee = new OneTapLogin(
			$this->settingsMock,
			$this->verifierMock,
			$this->clientMock,
			$this->authenticatorMock
		);
	}

	/**
	 * Test validate_token sends json error on missing state.
	 */
	public function testValidateTokenFailsOnMissingState(): void {
		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->shouldReceive( 'filter_input' )
			->with( INPUT_POST, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS )
			->andReturn( null );

		WP_Mock::userFunction( '__' )
			->andReturn( 'Invalid request state. Please reload the page.' );

		WP_Mock::userFunction( 'wp_send_json_error' )
			->once()
			->with( 'Invalid request state. Please reload the page.' );

		$this->testee->validate_token();
		$this->assertConditionsMet();
	}

	/**
	 * Test validate_token sends json error on invalid nonce.
	 */
	public function testValidateTokenFailsOnInvalidNonce(): void {
		$state = base64_encode( (string) json_encode( [
			'provider'    => 'google',
			'nonce'       => 'invalid_nonce',
			'state_token' => 'valid_token',
		] ) );

		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->shouldReceive( 'filter_input' )
			->with( INPUT_POST, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS )
			->andReturn( $state );

		WP_Mock::userFunction( 'wp_verify_nonce' )
			->with( 'invalid_nonce', 'login_with_google' )
			->andReturn( false );

		WP_Mock::userFunction( '__' )
			->andReturn( 'Invalid request state. Please reload the page.' );

		WP_Mock::userFunction( 'wp_send_json_error' )
			->once()
			->with( 'Invalid request state. Please reload the page.' );

		$this->testee->validate_token();
		$this->assertConditionsMet();
	}

	/**
	 * Test validate_token sends json error on invalid state_token.
	 */
	public function testValidateTokenFailsOnInvalidStateToken(): void {
		$state = base64_encode( (string) json_encode( [
			'provider'    => 'google',
			'nonce'       => 'valid_nonce',
			'state_token' => 'invalid_state_token',
		] ) );

		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->shouldReceive( 'filter_input' )
			->with( INPUT_POST, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS )
			->andReturn( $state );

		WP_Mock::userFunction( 'wp_verify_nonce' )
			->with( 'valid_nonce', 'login_with_google' )
			->andReturn( true );

		$helperMock->shouldReceive( 'verify_state_token' )
			->with( 'invalid_state_token' )
			->andReturn( false );

		WP_Mock::userFunction( '__' )
			->andReturn( 'Invalid request state. Please reload the page.' );

		WP_Mock::userFunction( 'wp_send_json_error' )
			->once()
			->with( 'Invalid request state. Please reload the page.' );

		$this->testee->validate_token();
		$this->assertConditionsMet();
	}

	/**
	 * Test validate_token succeeds and clears state cookie when tokens are valid.
	 */
	public function testValidateTokenSucceedsWithValidStateToken(): void {
		$state = base64_encode( (string) json_encode( [
			'provider'    => 'google',
			'nonce'       => 'valid_nonce',
			'state_token' => 'valid_token',
			'redirect_to' => 'https://example.com/dashboard',
		] ) );

		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->shouldReceive( 'filter_input' )
			->with( INPUT_POST, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS )
			->andReturn( $state );

		$helperMock->shouldReceive( 'filter_input' )
			->with( INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS )
			->andReturn( 'google_jwt_credential' );

		WP_Mock::userFunction( 'wp_verify_nonce' )
			->with( 'valid_nonce', 'login_with_google' )
			->andReturn( true );

		$helperMock->shouldReceive( 'verify_state_token' )
			->with( 'valid_token' )
			->andReturn( true );

		$this->verifierMock->shouldReceive( 'verify_token' )
			->with( 'google_jwt_credential' )
			->andReturn( true );

		$helperMock->shouldReceive( 'clear_state_cookie' )
			->once()
			->andReturnNull();

		WP_Mock::expectAction( 'rtcamp.id_token_verified' );

		WP_Mock::userFunction( 'admin_url' )
			->andReturn( 'https://example.com/wp-admin/' );

		WP_Mock::onFilter( 'rtcamp.google_default_redirect' )
			->with( 'https://example.com/wp-admin/' )
			->reply( 'https://example.com/wp-admin/' );

		WP_Mock::userFunction( 'wp_send_json_success' )
			->once()
			->with( [ 'redirect' => 'https://example.com/dashboard' ] )
			->andReturnNull();

		$this->testee->validate_token();
		$this->assertConditionsMet();
	}
}
