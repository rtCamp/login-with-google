<?php
/**
 * Test cases for Security Review Findings (01, 02, 03, 04, L1, L2, L3).
 *
 * @package RtCamp\GoogleLogin\Tests\Unit
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Tests\Unit;

use WP_Mock;
use Mockery;
use WP_User;
use WP_Error;
use stdClass;
use Exception;
use InvalidArgumentException;
use RtCamp\GoogleLogin\Tests\TestCase;
use RtCamp\GoogleLogin\Utils\Authenticator;
use RtCamp\GoogleLogin\Utils\GoogleClient;
use RtCamp\GoogleLogin\Utils\Helper;
use RtCamp\GoogleLogin\Modules\Settings;
use RtCamp\GoogleLogin\Modules\OneTapLogin;
use RtCamp\GoogleLogin\Utils\TokenVerifier;

/**
 * Class SecurityReviewFixesTest
 */
class SecurityReviewFixesTest extends TestCase {

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		unset( $_COOKIE[ Helper::STATE_COOKIE ] );
	}

	/**
	 * Test Finding L2: ABSPATH guard is present in template.
	 */
	public function testTemplateHasAbspathGuard(): void {
		$template_path = GH_PLUGIN_DIR . '/templates/google-login-button.php';
		$this->assertFileExists( $template_path );
		$template_content = file_get_contents( $template_path );
		$this->assertStringContainsString( "defined( 'ABSPATH' ) || exit;", $template_content );
	}

	/**
	 * Test Finding 03: GoogleClient state() includes state_token bound to cookie.
	 */
	public function testGoogleClientStateIncludesStateToken(): void {
		WP_Mock::userFunction( 'is_ssl' )
			->andReturn( false );

		$_COOKIE[ Helper::STATE_COOKIE ] = 'test_secret_token_123';

		WP_Mock::userFunction( 'wp_create_nonce' )
			->with( 'login_with_google' )
			->andReturn( 'test_nonce_abc' );

		WP_Mock::onFilter( 'rtcamp.google_login_state' )
			->with( Mockery::type( 'array' ) )
			->reply(
				function( array $state ) {
					return $state;
				}
			);

		$client = new GoogleClient( [] );
		$state  = $client->state();

		$decoded = json_decode( base64_decode( $state ), true );

		$this->assertSame( 'google', $decoded['provider'] );
		$this->assertSame( 'test_nonce_abc', $decoded['nonce'] );
		$this->assertSame( 'test_secret_token_123', $decoded['state_token'] );
	}

	/**
	 * Test Finding 03: Helper state token verification matches correctly on HTTP.
	 */
	public function testHelperStateTokenVerification(): void {
		WP_Mock::userFunction( 'is_ssl' )
			->andReturn( false );

		$this->assertFalse( Helper::verify_state_token( 'some_token' ) );

		$_COOKIE[ Helper::STATE_COOKIE ] = 'valid_token_xyz';
		$this->assertTrue( Helper::verify_state_token( 'valid_token_xyz' ) );
		$this->assertFalse( Helper::verify_state_token( 'wrong_token' ) );
	}

	/**
	 * Test Finding 03 / Comment 4: Helper state token verification uses __Host- on HTTPS with no fallback.
	 */
	public function testHelperStateTokenVerificationOnHttps(): void {
		WP_Mock::userFunction( 'is_ssl' )
			->andReturn( true );

		$cookie_name = Helper::get_state_cookie_name();
		$this->assertSame( '__Host-lwg_state_token', $cookie_name );

		$_COOKIE[ $cookie_name ] = 'https_token_123';
		$this->assertTrue( Helper::verify_state_token( 'https_token_123' ) );
		$this->assertFalse( Helper::verify_state_token( 'wrong_token' ) );

		// Ensure fallback to plain lwg_state_token fails on HTTPS.
		$_COOKIE[ Helper::STATE_COOKIE ] = 'plain_attacker_cookie';
		unset( $_COOKIE[ $cookie_name ] );
		$this->assertFalse( Helper::verify_state_token( 'plain_attacker_cookie' ) );
	}

	/**
	 * Test Finding 01: Whitelisted domains enforced for existing users.
	 */
	public function testAuthenticateEnforcesDomainAllowlistOnExistingUsers(): void {
		$settings = Mockery::mock( Settings::class );
		$settings->whitelisted_domains = 'rtcamp.com';

		$user = new stdClass();
		$user->email          = 'hacker@attacker.com';
		$user->email_verified = true;

		$authenticator = new Authenticator( $settings );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Login is restricted to allowed email domains.' );

		$authenticator->authenticate( $user );
	}

	/**
	 * Test Finding 01: Google sub mismatch throws exception on existing user.
	 */
	public function testAuthenticateRejectsMismatchedGoogleSub(): void {
		$settings = Mockery::mock( Settings::class );
		$settings->whitelisted_domains = '';

		$user = new stdClass();
		$user->email          = 'john@example.com';
		$user->email_verified = true;
		$user->sub            = 'google-attacker-sub';

		$wp_user     = Mockery::mock( WP_User::class );
		$wp_user->ID = 42;

		WP_Mock::userFunction( 'email_exists' )
			->with( 'john@example.com' )
			->andReturn( 42 );

		WP_Mock::userFunction( 'get_user_by' )
			->with( 'email', 'john@example.com' )
			->andReturn( $wp_user );

		WP_Mock::userFunction( 'get_user_meta' )
			->with( 42, 'google_oauth_sub', true )
			->andReturn( 'google-original-sub' );

		$authenticator = new Authenticator( $settings );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Google account does not match the account linked with this user.' );

		$authenticator->authenticate( $user );
	}

	/**
	 * Test Finding L3: Register throws exception when wp_insert_user returns WP_Error.
	 */
	public function testRegisterThrowsWhenInsertUserReturnsWpError(): void {
		$settings = Mockery::mock( Settings::class );
		$settings->registration_enabled = true;
		$settings->whitelisted_domains  = '';

		$user = new stdClass();
		$user->login = 'newuser';
		$user->email = 'newuser@example.com';

		WP_Mock::userFunction( 'wp_generate_password' )
			->andReturn( 'strong_password_123' );

		WP_Mock::userFunction( 'username_exists' )
			->andReturn( false );

		$wp_error = Mockery::mock( WP_Error::class );
		$wp_error->shouldReceive( 'get_error_message' )
			->andReturn( 'Database connection failed' );

		WP_Mock::userFunction( 'wp_insert_user' )
			->andReturn( $wp_error );

		$authenticator = new Authenticator( $settings );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Database connection failed' );

		$authenticator->register( $user );
	}

	/**
	 * Test Finding 02: OneTap authenticate triggers wp_authenticate_user filter and aborts on WP_Error.
	 */
	public function testOneTapAuthenticateHonorsWpAuthenticateUserError(): void {
		$settings      = Mockery::mock( Settings::class );
		$verifier      = Mockery::mock( TokenVerifier::class );
		$client        = Mockery::mock( GoogleClient::class );
		$authenticator = Mockery::mock( Authenticator::class );

		$google_user = new stdClass();
		$google_user->email = 'test@example.com';

		$wp_user             = Mockery::mock( WP_User::class );
		$wp_user->user_login = 'testuser';

		$verifier->shouldReceive( 'current_user' )
			->andReturn( $google_user );

		$authenticator->shouldReceive( 'authenticate' )
			->with( $google_user )
			->andReturn( $wp_user );

		$auth_error = Mockery::mock( WP_Error::class );
		$auth_error->shouldReceive( 'get_error_message' )
			->andReturn( 'Two-Factor Authentication required' );

		WP_Mock::onFilter( 'authenticate' )
			->with( $wp_user, 'testuser', '' )
			->reply( $wp_user );

		WP_Mock::onFilter( 'wp_authenticate_user' )
			->with( $wp_user, '' )
			->reply( $auth_error );

		$one_tap = new OneTapLogin( $settings, $verifier, $client, $authenticator );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Two-Factor Authentication required' );

		$one_tap->authenticate();
	}

	/**
	 * Test Finding 02: OneTap authenticate triggers authenticate filter and aborts on WP_Error (e.g. spam check).
	 */
	public function testOneTapAuthenticateHonorsAuthenticateError(): void {
		$settings      = Mockery::mock( Settings::class );
		$verifier      = Mockery::mock( TokenVerifier::class );
		$client        = Mockery::mock( GoogleClient::class );
		$authenticator = Mockery::mock( Authenticator::class );

		$google_user = new stdClass();
		$google_user->email = 'spam@example.com';

		$wp_user             = Mockery::mock( WP_User::class );
		$wp_user->user_login = 'spamuser';

		$verifier->shouldReceive( 'current_user' )
			->andReturn( $google_user );

		$authenticator->shouldReceive( 'authenticate' )
			->with( $google_user )
			->andReturn( $wp_user );

		$spam_error = Mockery::mock( WP_Error::class );
		$spam_error->shouldReceive( 'get_error_message' )
			->andReturn( 'Your account has been marked as a spammer.' );

		WP_Mock::onFilter( 'authenticate' )
			->with( $wp_user, 'spamuser', '' )
			->reply( $spam_error );

		$one_tap = new OneTapLogin( $settings, $verifier, $client, $authenticator );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Your account has been marked as a spammer.' );

		$one_tap->authenticate();
	}

	/**
	 * Test Finding 02: OneTap authenticate fires wp_login on successful login.
	 */
	public function testOneTapAuthenticateFiresWpLoginAction(): void {
		$settings      = Mockery::mock( Settings::class );
		$verifier      = Mockery::mock( TokenVerifier::class );
		$client        = Mockery::mock( GoogleClient::class );
		$authenticator = Mockery::mock( Authenticator::class );

		$google_user = new stdClass();
		$wp_user     = Mockery::mock( WP_User::class );
		$wp_user->user_login = 'valid_user';

		$verifier->shouldReceive( 'current_user' )
			->andReturn( $google_user );

		$authenticator->shouldReceive( 'authenticate' )
			->with( $google_user )
			->andReturn( $wp_user );

		WP_Mock::onFilter( 'authenticate' )
			->with( $wp_user, 'valid_user', '' )
			->reply( $wp_user );

		WP_Mock::onFilter( 'wp_authenticate_user' )
			->with( $wp_user, '' )
			->reply( $wp_user );

		$authenticator->shouldReceive( 'set_auth_cookies' )
			->with( $wp_user )
			->once();

		WP_Mock::expectAction( 'wp_login', 'valid_user', $wp_user );

		$one_tap = new OneTapLogin( $settings, $verifier, $client, $authenticator );
		$one_tap->authenticate();

		$this->assertConditionsMet();
	}

	/**
	 * Test Comment 1: Verify shortcode and block authorization URL contains state_token.
	 */
	public function testShortcodeAndBlockFlowIncludesStateTokenInAuthorizationUrl(): void {
		WP_Mock::userFunction( 'is_ssl' )
			->andReturn( false );

		$_COOKIE[ Helper::STATE_COOKIE ] = 'shortcode_test_token_123';

		WP_Mock::userFunction( 'wp_create_nonce' )
			->with( 'login_with_google' )
			->andReturn( 'mock_nonce_123' );

		WP_Mock::onFilter( 'rtcamp.google_login_state' )
			->with( Mockery::type( 'array' ) )
			->reply(
				function( array $state ) {
					return $state;
				}
			);

		WP_Mock::onFilter( 'rtcamp.google_scope' )
			->with( Mockery::any() )
			->reply( [ 'openid', 'email', 'profile' ] );

		WP_Mock::onFilter( 'rtcamp.google_redirect_url' )
			->with( Mockery::any() )
			->reply( 'https://example.com/callback' );

		WP_Mock::onFilter( 'rtcamp.google_client_args' )
			->with( Mockery::any() )
			->reply( [] );

		$client = new GoogleClient(
			[
				'client_id'     => 'test-client-id.apps.googleusercontent.com',
				'client_secret' => 'test-secret',
				'redirect_uri'  => 'https://example.com/callback',
			]
		);

		$auth_url = $client->authorization_url();
		$this->assertNotEmpty( $auth_url );

		$query = [];
		parse_str( (string) parse_url( $auth_url, PHP_URL_QUERY ), $query );

		$this->assertArrayHasKey( 'state', $query );
		$decoded_state = json_decode( base64_decode( $query['state'] ), true );

		$this->assertSame( 'google', $decoded_state['provider'] );
		$this->assertSame( 'mock_nonce_123', $decoded_state['nonce'] );
		$this->assertSame( 'shortcode_test_token_123', $decoded_state['state_token'] );
	}

	/**
	 * Test Comment 1: get_cookie_options sets httponly to false so client JS can synchronize state.
	 */
	public function testHelperCookieOptionsAllowsJsAccess(): void {
		WP_Mock::userFunction( 'is_ssl' )
			->andReturn( true );

		$method  = new \ReflectionMethod( Helper::class, 'get_cookie_options' );
		$options = $method->invoke( null, 12345 );

		$this->assertFalse( $options['httponly'] );
		$this->assertTrue( $options['secure'] );
		$this->assertSame( '/', $options['path'] );
		$this->assertSame( 'Lax', $options['samesite'] );
	}

	/**
	 * Test Comment 3: Template contains data-cookie-name attribute.
	 */
	public function testTemplateContainsDataCookieName(): void {
		$template_path = GH_PLUGIN_DIR . '/templates/google-login-button.php';
		$content       = file_get_contents( $template_path );

		$this->assertStringContainsString( 'data-cookie-name="<?php echo esc_attr( Helper::get_state_cookie_name() ); ?>"', $content );
	}

	/**
	 * Test Comment 5: get_or_create_state_token does not send Set-Cookie header on front-end GET requests.
	 */
	public function testHelperGetOrCreateStateTokenPreservesEdgeCaching(): void {
		WP_Mock::userFunction( 'is_ssl' )
			->andReturn( false );

		WP_Mock::userFunction( 'did_action' )
			->with( 'login_init' )
			->andReturn( 0 );

		WP_Mock::userFunction( 'is_admin' )
			->andReturn( false );

		WP_Mock::userFunction( 'wp_generate_password' )
			->with( 32, false )
			->andReturn( 'random_frontend_token_1234567890' );

		$token = Helper::get_or_create_state_token();

		$this->assertSame( 'random_frontend_token_1234567890', $token );
		$this->assertArrayNotHasKey( Helper::STATE_COOKIE, $_COOKIE );
	}

	/**
	 * Test Comment 6: Settings sanitize_settings clears client secret when clear_client_secret is checked.
	 */
	public function testSettingsSanitizeClearsClientSecret(): void {
		$settings = new Settings();

		$ref_options = new \ReflectionProperty( Settings::class, 'options' );
		$ref_options->setValue( $settings, [ 'client_secret' => 'old_secret_value' ] );

		$sanitized = $settings->sanitize_settings(
			[
				'client_secret'       => '',
				'clear_client_secret' => '1',
			]
		);

		$this->assertSame( '', $sanitized['client_secret'] );
		$this->assertArrayNotHasKey( 'clear_client_secret', $sanitized );
	}

	/**
	 * Test Comment 6: Settings sanitize_settings preserves client secret when left blank.
	 */
	public function testSettingsSanitizePreservesClientSecretWhenBlank(): void {
		$settings = new Settings();

		$ref_options = new \ReflectionProperty( Settings::class, 'options' );
		$ref_options->setValue( $settings, [ 'client_secret' => 'existing_secret_value' ] );

		$sanitized = $settings->sanitize_settings(
			[
				'client_secret' => '',
			]
		);

		$this->assertSame( 'existing_secret_value', $sanitized['client_secret'] );
	}

	/**
	 * Test Comment 8: OneTap authenticate redirects 2FA user with redirect_to and notice query args.
	 */
	public function testOneTapAuthenticateTwoFactorRedirectIncludesArgs(): void {
		$settings      = Mockery::mock( Settings::class );
		$verifier      = Mockery::mock( TokenVerifier::class );
		$client        = Mockery::mock( GoogleClient::class );
		$authenticator = Mockery::mock( Authenticator::class );

		$google_user = new stdClass();
		$wp_user     = Mockery::mock( WP_User::class );
		$wp_user->ID = 99;
		$wp_user->user_login = 'twofactor_user';

		$verifier->shouldReceive( 'current_user' )
			->andReturn( $google_user );

		$authenticator->shouldReceive( 'authenticate' )
			->with( $google_user )
			->andReturn( $wp_user );

		WP_Mock::onFilter( 'authenticate' )
			->with( $wp_user, 'twofactor_user', '' )
			->reply( $wp_user );

		WP_Mock::onFilter( 'wp_authenticate_user' )
			->with( $wp_user, '' )
			->reply( $wp_user );

		if ( ! class_exists( 'Two_Factor_Core' ) ) {
			eval( 'class Two_Factor_Core { public static function is_user_using_two_factor( $user_id ) { return true; } }' );
		}

		WP_Mock::userFunction( 'admin_url' )
			->andReturn( 'https://example.com/wp-admin/' );

		WP_Mock::userFunction( 'home_url' )
			->andReturn( 'https://example.com/' );

		WP_Mock::onFilter( 'rtcamp.google_default_redirect' )
			->with( Mockery::any() )
			->reply( 'https://example.com/wp-admin/' );

		WP_Mock::userFunction( 'wp_login_url' )
			->andReturn( 'https://example.com/wp-login.php' );

		WP_Mock::userFunction( 'add_query_arg' )
			->with(
				Mockery::on(
					function( array $args ) {
						return isset( $args['login_with_google_2fa'] ) && '1' === $args['login_with_google_2fa']
							&& isset( $args['redirect_to'] );
					}
				),
				'https://example.com/wp-login.php'
			)
			->andReturn( 'https://example.com/wp-login.php?login_with_google_2fa=1&redirect_to=' . urlencode( 'https://example.com/wp-admin/' ) );

		WP_Mock::userFunction( 'wp_send_json_success' )
			->with( [ 'redirect' => 'https://example.com/wp-login.php?login_with_google_2fa=1&redirect_to=' . urlencode( 'https://example.com/wp-admin/' ) ] )
			->once();

		$one_tap = new OneTapLogin( $settings, $verifier, $client, $authenticator );
		$one_tap->authenticate();

		$this->assertConditionsMet();
	}
}

