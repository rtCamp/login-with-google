<?php
/**
 * Login class.
 *
 * This will manage the login flow, which includes adding the
 * google login button on wp-login page, authorizing the user,
 * authenticating user and redirecting him to admin.
 *
 * @package RtCamp\GoogleLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Modules;

use WP_User;
use WP_Error;
use stdClass;
use Throwable;
use Exception;
use RtCamp\GoogleLogin\Utils\Helper;
use RtCamp\GoogleLogin\Utils\GoogleClient;
use RtCamp\GoogleLogin\Utils\Authenticator;
use RtCamp\GoogleLogin\Interfaces\Module as ModuleInterface;
use function RtCamp\GoogleLogin\plugin;

/**
 * Class Login.
 *
 * @package RtCamp\GoogleLogin\Modules
 */
class Login implements ModuleInterface {
	/**
	 * Google client instance.
	 *
	 * @var GoogleClient
	 */
	private $gh_client;

	/**
	 * Authenticator instance.
	 *
	 * @var Authenticator
	 */
	private $authenticator;

	/**
	 * Flag for determining whether the user has been authenticated
	 * from plugin.
	 *
	 * @var bool
	 */
	private $authenticated = false;

	/**
	 * Login constructor.
	 *
	 * @param GoogleClient  $client GH Client object.
	 * @param Authenticator $authenticator Settings object.
	 */
	public function __construct( GoogleClient $client, Authenticator $authenticator ) {
		$this->gh_client     = $client;
		$this->authenticator = $authenticator;
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public function name(): string {
		return 'login_flow';
	}

	/**
	 * Initialize login flow.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'login_form', [ $this, 'login_button' ] );
		// Priority is 20 because of issue: https://core.trac.wordpress.org/ticket/46748.
		add_action( 'authenticate', [ $this, 'authenticate' ], 20 );
		add_action( 'rtcamp.google_register_user', [ $this->authenticator, 'register' ] );
		add_action( 'rtcamp.google_redirect_url', [ $this, 'redirect_url' ] );
		add_action( 'rtcamp.google_user_created', [ $this, 'user_meta' ] );
		add_filter( 'rtcamp.google_login_state', [ $this, 'state_redirect' ] );
		add_action( 'wp_login', [ $this, 'login_redirect' ] );
	}

	/**
	 * Add the login button to login form.
	 *
	 * @return void
	 */
	public function login_button(): void {
		$template  = trailingslashit( plugin()->template_dir ) . 'google-login-button.php';
		$login_url = plugin()->container()->get( 'gh_client' )->authorization_url();

		Helper::render_template(
			$template,
			[
				'login_url' => $login_url,
			]
		);
	}

	/**
	 * Authenticate the user.
	 *
	 * @param WP_User|null $user User object. Default is null.
	 *
	 * @return WP_User|WP_Error
	 * @throws Exception During authentication.
	 */
	public function authenticate( $user = null ) {
		if ( $user instanceof WP_User ) {
			return $user;
		}

		$code = Helper::filter_input( INPUT_GET, 'code', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! $code ) {
			return $user;
		}

		$state         = Helper::filter_input( INPUT_GET, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$decoded_state = $state ? (array) ( json_decode( base64_decode( $state ) ) ) : null;    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( ! is_array( $decoded_state ) || empty( $decoded_state['provider'] ) || 'google' !== $decoded_state['provider'] ) {
			$this->log_failed_attempt( 'Invalid provider or state' );
			return $user;
		}

		if ( empty( $decoded_state['nonce'] ) || ! wp_verify_nonce( $decoded_state['nonce'], 'login_with_google' ) ) {
			$this->log_failed_attempt( 'Invalid nonce' );
			return $user;
		}

		try {
			$this->gh_client->set_access_token( $code );
			$google_user = $this->gh_client->user();
			
			// Handle developer mode
			if ( get_option( 'google_login_dev_mode' ) ) {
				// Log raw user data
				error_log( 'Google Login Debug - Raw User Data: ' . print_r( $google_user, true ) );
				
				// Add debug information to the login page
				add_action( 'login_footer', function() use ( $google_user ) {
					echo '<div class="google-login-debug" style="margin: 20px; padding: 20px; background: #f1f1f1; border: 1px solid #ddd;">';
					echo '<h3>' . esc_html__( 'Google Login Debug Information', 'login-with-google' ) . '</h3>';
					echo '<pre style="white-space: pre-wrap; word-wrap: break-word;">';
					echo esc_html( print_r( $google_user, true ) );
					echo '</pre>';
					echo '</div>';
				});
			}
			
			// Check if the email domain is allowed
			$allowed_domains = get_option( 'google_login_allowed_domains' );
			if ( ! empty( $allowed_domains ) ) {
				$email_domain = substr( strrchr( $google_user->email, '@' ), 1 );
				$allowed_domains = array_map( 'trim', explode( ',', $allowed_domains ) );
				
				if ( ! in_array( $email_domain, $allowed_domains, true ) ) {
					$this->log_failed_attempt( 'Domain not allowed: ' . $email_domain );
					return new WP_Error(
						'domain_not_allowed',
						__( 'Your email domain is not allowed to login to this site.', 'login-with-google' )
					);
				}
			}
			
			$user = $this->authenticator->authenticate( $google_user );

			if ( $user instanceof WP_User ) {
				$this->authenticated = true;

				/**
				 * Fires once the user has been authenticated via Google OAuth.
				 *
				 * @since 1.3.0
				 *
				 * @param WP_User $user WP User object.
				 */
				do_action( 'rtcamp.google_user_authenticated', $user );

				return $user;
			}

			$this->log_failed_attempt( 'Could not authenticate user' );
			throw new Exception( __( 'Could not authenticate the user, please try again.', 'login-with-google' ) );

		} catch ( Throwable $e ) {
			$this->log_failed_attempt( $e->getMessage() );
			return new WP_Error( 'google_login_failed', $e->getMessage() );
		}
	}

	/**
	 * Log failed login attempts
	 *
	 * @param string $reason Reason for the failed attempt.
	 * @return void
	 */
	private function log_failed_attempt( string $reason ): void {
		$logs = get_option( 'google_login_logs', [] );
		$log_entry = [
			'timestamp' => current_time( 'mysql' ),
			'reason' => $reason,
		];
		
		// Keep only the last 100 entries
		array_unshift( $logs, $log_entry );
		$logs = array_slice( $logs, 0, 100 );
		
		update_option( 'google_login_logs', $logs );
	}

	/**
	 * Add extra meta information about user.
	 *
	 * @param int $uid  User ID.
	 *
	 * @return void
	 */
	public function user_meta( int $uid ) {
		add_user_meta( $uid, 'oauth_user', 1, true );
		add_user_meta( $uid, 'oauth_provider', 'google', true );
	}

	/**
	 * Redirect URL.
	 *
	 * This is useful when redirect URL is present when
	 * trying to login to wp-admin.
	 *
	 * @param string $url Redirect URL address.
	 *
	 * @return string
	 */
	public function redirect_url( string $url ): string {

		return remove_query_arg( 'redirect_to', $url );
	}

	/**
	 * Add redirect_to location in state.
	 *
	 * @param array $state State data.
	 *
	 * @return array
	 */
	public function state_redirect( array $state ): array {
		$redirect_to = Helper::filter_input( INPUT_GET, 'redirect_to', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		
		// Get the default redirect URL from settings
		$settings = plugin()->container()->get( 'settings' );
		$default_redirect = $settings->redirect_url;
		
		/**
		 * Filter the default redirect URL in case redirect_to param is not available.
		 * Default to admin URL.
		 *
		 * @param string $admin_url Admin URL address.
		 */
		$state['redirect_to'] = $redirect_to ?? ( $default_redirect ?: apply_filters( 'rtcamp.google_default_redirect', admin_url() ) );

		return $state;
	}

	/**
	 * Add a redirect once user has been authenticated successfully.
	 *
	 * @return void
	 */
	public function login_redirect(): void {
		$state = Helper::filter_input( INPUT_GET, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! $state || ! $this->authenticated ) {
			return;
		}

		$state = base64_decode( $state );
		$state = $state ? json_decode( $state ) : null;

		if ( ( $state instanceof stdClass ) && ! empty( $state->provider ) && 'google' === $state->provider ) {
			// Check for the global redirect URL option first
			$global_redirect = get_option( 'google_login_redirect_url' );
			if ( ! empty( $global_redirect ) ) {
				wp_safe_redirect( $global_redirect, 302, 'Login with Google' );
				exit;
			}

			// If no global redirect is set, use the state redirect
			if ( ! empty( $state->redirect_to ) ) {
				wp_safe_redirect( $state->redirect_to, 302, 'Login with Google' );
				exit;
			}
		}
	}
}
