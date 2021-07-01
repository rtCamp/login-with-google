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

use stdClass;
use RtCamp\GoogleLogin\Utils\GoogleClient;
use RtCamp\GoogleLogin\Utils\Helper;
use WP_User;
use WP_Error;
use Exception;
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
	 * Settings object.
	 *
	 * @var Settings
	 */
	private $settings;

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
	 * @param GoogleClient $client GH Client object.
	 * @param Settings     $settings Settings object.
	 */
	public function __construct( GoogleClient $client, Settings $settings ) {
		$this->gh_client = $client;
		$this->settings  = $settings;
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
		add_action( 'authenticate', [ $this, 'authenticate' ] );
		add_action( 'rtcamp.google_register_user', [ $this, 'register' ] );
		add_action( 'rtcamp.google_redirect_url', [ $this, 'redirect_url' ] );
		add_action( 'rtcamp.google_user_created', [ $this, 'user_meta' ], 10, 2 );
		add_filter( 'rtcamp.google_user_profile', [ $this, 'user_login' ] );
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
	 */
	public function authenticate( $user = null ) {
		if ( $user instanceof WP_User ) {
			return $user;
		}

		$code = Helper::filter_input( INPUT_GET, 'code', FILTER_SANITIZE_STRING );

		if ( ! $code ) {
			return $user;
		}

		$state          = Helper::filter_input( INPUT_GET, 'state', FILTER_SANITIZE_STRING );
		$decoded_state  = $state ? (array) ( json_decode( base64_decode( $state ) ) ) : null;

		if ( ! is_array( $decoded_state ) || empty( $decoded_state['provider'] ) || 'google' !== $decoded_state['provider'] ) {
			return $user;
		}

		if ( empty( $decoded_state['nonce'] ) || ! wp_verify_nonce( $decoded_state['nonce'], 'login_with_google' ) ) {
			return $user;
		}

		try {
			$this->gh_client->set_access_token( $code );
			$user = $this->gh_client->user();
			$user = apply_filters( 'rtcamp.google_user_profile', $user );

			if ( email_exists( $user->email ) ) {
				$this->authenticated = true;
				return get_user_by( 'email', $user->email );
			}

			/**
			 * Check if we need to register the user.
			 *
			 * @param stdClass $user User object from google.
			 * @since 1.0.0
			 */
			return apply_filters( 'rtcamp.google_register_user', $user );

		} catch ( \Throwable $e ) {
			return new WP_Error( 'google_login_failed', $e->getMessage() );
		}
	}

	/**
	 * Register the new user if setting is on for registration.
	 *
	 * @param stdClass $user User object from google.
	 *
	 * @return WP_User|null
	 * @throws \Throwable Invalid email registration.
	 * @throws Exception Registration is off.
	 */
	public function register( stdClass $user ): ?WP_User {
		$register = true === (bool) $this->settings->registration_enabled || (bool) get_option( 'users_can_register', false );

		if ( ! $register ) {
			throw new Exception( __( 'Registration is not allowed.', 'login-with-google' ) );
		}

		try {
			$whitelisted_domains = $this->settings->whitelisted_domains;
			if ( empty( $whitelisted_domains ) || $this->can_register_with_email( $user->email ) ) {
				$uid = wp_insert_user(
					[
						'user_login' => Helper::unique_username( $user->login ),
						'user_pass'  => wp_generate_password( 18 ),
						'user_email' => $user->email,
					]
				);

				if ( $uid ) {
					$this->authenticated = true;
				}

				/**
				 * Fires once the user has been registered successfully.
				 */
				do_action( 'rtcamp.google_user_created', $uid, $user );

				return get_user_by( 'id', $uid );
			}

			/* translators: %s is replaced with email ID of user trying to register */
			throw new Exception( sprintf( __( 'Cannot register with this email: %s', 'login-with-google' ), $user->email ) );

		} catch ( \Throwable $e ) {

			throw $e;
		}

	}

	/**
	 * Add extra meta information about user.
	 *
	 * @param int      $uid  User ID.
	 * @param stdClass $user User object.
	 *
	 * @return void
	 */
	public function user_meta( int $uid, stdClass $user ) {
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
		$redirect_to          = Helper::filter_input( INPUT_GET, 'redirect_to', FILTER_SANITIZE_STRING );
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
		$state = Helper::filter_input( INPUT_GET, 'state', FILTER_SANITIZE_STRING );

		if ( ! $state || ! $this->authenticated ) {
			return;
		}

		$state = base64_decode( $state );
		$state = $state ? json_decode( $state ) : null;

		if ( ( $state instanceof stdClass ) && ! empty( $state->provider ) && 'google' === $state->provider && ! empty ( $state->redirect_to ) ) {
			wp_safe_redirect( $state->redirect_to );
			exit;
		}
	}

	/**
	 * Assign the `login` property to user object
	 * if it doesn't exists.
	 *
	 * @param stdClass $user User object.
	 *
	 * @return stdClass
	 */
	public function user_login( stdClass $user ): stdClass {
		if ( property_exists( $user, 'login' ) || ! property_exists( $user, 'email' ) ) {
			return $user;
		}

		$email       = $user->email;
		$user_login  = sanitize_user( current( explode( '@', $email ) ), true );
		$user_login  = Helper::unique_username( $user_login );
		$user->login = $user_login;

		return $user;
	}

	/**
	 * Check if given email can be used for registration.
	 *
	 * @param string $email Email ID.
	 *
	 * @return bool
	 */
	private function can_register_with_email( string $email ): bool {
		$whitelisted_domains = explode( ',', $this->settings->whitelisted_domains );
		$whitelisted_domains = array_map( 'strtolower', $whitelisted_domains );
		$whitelisted_domains = array_map( 'trim', $whitelisted_domains );
		$email_parts         = explode( '@', $email );
		$email_parts         = array_map( 'strtolower', $email_parts );

		return in_array( $email_parts[1], $whitelisted_domains, true );
	}
}
