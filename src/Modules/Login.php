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
			return $user;
		}

		if ( empty( $decoded_state['nonce'] ) || ! wp_verify_nonce( $decoded_state['nonce'], 'login_with_google' ) ) {
			return $user;
		}

		try {
			$this->gh_client->set_access_token( $code );
			$user = $this->gh_client->user();
			$user = $this->authenticator->authenticate( $user );

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

			throw new Exception( __( 'Could not authenticate the user, please try again.', 'login-with-google' ) );

		} catch ( Throwable $e ) {
			return new WP_Error( 'google_login_failed', $e->getMessage() );
		}
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
		/**
		 * Filter the default redirect URL in case redirect_to param is not available.
		 * Default to admin URL.
		 *
		 * @param string $admin_url Admin URL address.
		 */
		$state['redirect_to'] = $redirect_to ?? apply_filters( 'rtcamp.google_default_redirect', admin_url() );

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

		if ( ( $state instanceof stdClass ) && ! empty( $state->provider ) && 'google' === $state->provider && ! empty( $state->redirect_to ) ) {
			wp_safe_redirect( $state->redirect_to );
			exit;
		}
	}
}
