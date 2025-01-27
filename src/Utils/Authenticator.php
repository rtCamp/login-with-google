<?php
/**
 * Authenticator class.
 *
 * This will authenticate the user. Also responsible for registration
 * in case it is enabled in the settings.
 *
 * @package RtCamp\GoogleLogin
 * @since 1.1.1
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Utils;

use WP_User;
use stdClass;
use Exception;
use Throwable;
use InvalidArgumentException;
use RtCamp\GoogleLogin\Modules\Settings;

/**
 * Class Authenticator
 *
 * @package RtCamp\GoogleLogin\Utils
 */
class Authenticator {
	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Authenticator constructor.
	 *
	 * @param Settings $settings Settings instance.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Authenticate the user.
	 *
	 * If registration setting is on, user will be created if
	 * that user does not exist in the application.
	 *
	 * @param stdClass $user User data object returned by Google.
	 *
	 * @return WP_User
	 * @throws InvalidArgumentException For invalid registrations.
	 */
	public function authenticate( stdClass $user ): WP_User {
		if ( ! property_exists( $user, 'email' ) ) {
			throw new InvalidArgumentException( __( 'Email needs to be present for the user.', 'login-with-google' ) );
		}

		if ( email_exists( $user->email ) ) {
			$user_wp = get_user_by( 'email', $user->email );

			/**
			 * Fires once the user has been authenticated.
			 *
			 * @since 1.3.0
			 *
			 * @param WP_User $user_wp WP User data object.
			 * @param stdClass $user User data object returned by Google.
			 */
			do_action( 'rtcamp.google_user_logged_in', $user_wp, $user );

			return $user_wp;
		}

		/**
		 * Check if we need to register the user.
		 *
		 * @param stdClass $user User object from google.
		 * @since 1.0.0
		 */
		return apply_filters( 'rtcamp.google_register_user', $this->maybe_create_username( $user ) );
	}

	/**
	 * Register the new user if setting is on for registration.
	 *
	 * @param stdClass $user User object from google.
	 *
	 * @return WP_User|null
	 * @throws Throwable Invalid email registration.
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
						'first_name' => $user->given_name ?? '',
						'last_name'  => $user->family_name ?? '',
					]
				);

				/**
				 * Fires once the user has been registered successfully.
				 */
				do_action( 'rtcamp.google_user_created', $uid, $user );

				return get_user_by( 'id', $uid );
			}

			/* translators: %s is replaced with email ID of user trying to register */
			throw new Exception( sprintf( __( 'Cannot register with this email: %s', 'login-with-google' ), $user->email ) );

		} catch ( Throwable $e ) {

			throw $e;
		}

	}

	/**
	 * Set auth cookies for WordPress login.
	 *
	 * @param WP_User $user WP User object.
	 *
	 * @return void
	 */
	public function set_auth_cookies( WP_User $user ) {
		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID );
	}

	/**
	 * Assign the `login` property to user object
	 * if it doesn't exists.
	 *
	 * @param stdClass $user User object.
	 *
	 * @return stdClass
	 */
	private function maybe_create_username( stdClass $user ): stdClass {
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
