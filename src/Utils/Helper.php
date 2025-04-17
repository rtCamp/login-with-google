<?php
/**
 * Helper class for all helper function.
 *
 * This class has been taken from Login with Google plugin.
 *
 * @package RtCamp\GoogleLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Utils;

/**
 * Class Helper
 */
class Helper {

	/**
	 * URL to be redirected to post successful login.
	 * 
	 * @var string
	 */
	public static $redirection_url = '';

	/**
	 * To render or return output of template.
	 *
	 * @param string $template_path Template path.
	 * @param array  $variables     array of variables that needed on template.
	 * @param bool   $should_echo   Whether you need to echo to return HTML markup.
	 *
	 * @return string
	 */
	public static function render_template( $template_path, $variables = [], $should_echo = true ) {

		$validate_file = validate_file( $template_path );
		// Function validate_file returns 2 for Windows drive path, so we check that as well.
		if ( empty( $template_path ) || ! file_exists( $template_path ) || ( 0 !== $validate_file && 2 !== $validate_file ) ) {
			return '';
		}

		if ( ! empty( $variables ) ) {
			// This will needed for provide variables to the template.
			// Will skips those variables, those already defined.
			extract( $variables, EXTR_SKIP ); // phpcs:ignore
		}

		if ( true === $should_echo ) {

			// Load template and output the data.
			require $template_path; // phpcs:ignore

			return ''; // Job done, bail out.
		}

		ob_start();

		// Load template output in buffer.
		require $template_path; // phpcs:ignore

		return ob_get_clean();
	}

	/**
	 * This method is an improved version of PHP's filter_input() and
	 * works well on PHP Cli as well which PHP default method does not.
	 *
	 * Reference: https://bugs.php.net/bug.php?id=49184
	 *
	 * @param int    $type          One of INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV.
	 * @param string $variable_name Name of a variable to get.
	 * @param int    $filter        The ID of the filter to apply.
	 * @param mixed  $options       filter to apply.
	 *
	 * @return mixed Value of the requested variable on success, FALSE if the filter fails, or NULL if the
	 *  variable_name variable is not set.
	 */
	public static function filter_input( $type, $variable_name, $filter = FILTER_DEFAULT, $options = null ) {

		if ( php_sapi_name() !== 'cli' ) {

			/**
			 * We can not have code coverage since.
			 * Since this will only execute when sapi is "fpm-fcgi".
			 * While Unit test case run on "cli"
			 */
			// @codeCoverageIgnoreStart

			/**
			 * Code is not running on PHP Cli and we are in clear.
			 * Use the PHP method and bail out.
			 */
			switch ( $filter ) {
				case FILTER_SANITIZE_FULL_SPECIAL_CHARS:
					$sanitized_variable = filter_input( $type, $variable_name, $filter );
					break;
				default:
					$sanitized_variable = filter_input( $type, $variable_name, $filter, $options );
					break;
			}

			return $sanitized_variable;
			// @codeCoverageIgnoreEnd
		}

		/**
		 * Code is running on PHP Cli and INPUT_SERVER returns NULL
		 * even for set vars when run on Cli
		 * See: https://bugs.php.net/bug.php?id=49184
		 *
		 * This is a workaround for that bug till its resolved in PHP binary
		 * which doesn't look to be anytime soon. This is a friggin' 10 year old bug.
		 */

		$input = '';

		$allowed_html_tags = wp_kses_allowed_html( 'post' );

		/**
		 * Marking the switch() block below to be ignored by PHPCS
		 * because PHPCS squawks on using superglobals like $_POST or $_GET
		 * directly but it can't be helped in this case as this code
		 * is running on Cli.
		 */

		// @codingStandardsIgnoreStart

		switch ( $type ) {

			case INPUT_GET:
				if ( ! isset( $_GET[ $variable_name ] ) ) {
					return null;
				}

				$input = wp_kses( $_GET[ $variable_name ], $allowed_html_tags );
				break;

			case INPUT_POST:
				if ( ! isset( $_POST[ $variable_name ] ) ) {
					return null;
				}

				$input = wp_kses( $_POST[ $variable_name ], $allowed_html_tags );
				break;

			case INPUT_COOKIE:
				if ( ! isset( $_COOKIE[ $variable_name ] ) ) {
					return null;
				}

				$input = wp_kses( $_COOKIE[ $variable_name ], $allowed_html_tags );
				break;

			case INPUT_SERVER:
				if ( ! isset( $_SERVER[ $variable_name ] ) ) {
					return null;
				}

				$input = wp_kses( $_SERVER[ $variable_name ], $allowed_html_tags );
				break;

			case INPUT_ENV:
				if ( ! isset( $_ENV[ $variable_name ] ) ) {
					return null;
				}

				$input = wp_kses( $_ENV[ $variable_name ], $allowed_html_tags );
				break;

			default:
				return null;
				break;

		}

		// @codingStandardsIgnoreEnd

		return filter_var( $input, $filter );
	}

	/**
	 * Checks if username exists, if it does, creates a
	 * unique username by appending digits.
	 *
	 * @param string $username Username.
	 *
	 * @return string
	 */
	public static function unique_username( string $username ): string {
		$uname = $username;
		$count = 1;

		while ( username_exists( $uname ) ) {
			$uname = $uname . '' . $count;
		}

		return $uname;
	}

	/**
	 * Get the redirection URL.
	 *
	 * @return string
	 */
	public static function get_redirect_url(): string {
		global $pagenow;

		$redirect_to = '';

		if ( 'wp-login.php' === $pagenow ) {
			$redirect_to = filter_input( INPUT_GET, 'redirect_to', FILTER_VALIDATE_URL );
			
			// In case no query parameter is available.
			if ( is_null( $redirect_to ) ) {
				$redirect_to = '';
			}
		} else {
			$redirect_to = get_permalink();
		}

		if ( '' === $redirect_to ) {
			$redirect_to = apply_filters( 'rtcamp.google_default_redirect', admin_url() );
		}

		return $redirect_to;
	}

	/**
	 * Wrapper function to update the state variable with the redirection url.
	 * 
	 * @param string $redirect_to Contains the redirection url.
	 * 
	 * @return void
	 */
	public static function set_redirect_state_filter( $redirect_to ) {
		if ( empty( $redirect_to ) ) {
			return;
		}

		self::$redirection_url = $redirect_to;

		add_filter( 'rtcamp.google_login_state', [ __CLASS__, 'update_redirect_state' ] );
	}

	/**
	 * Updating the state variable to set the dynamic url.
	 * 
	 * @param array $state Contains the state array.
	 * 
	 * @return array
	 */
	public static function update_redirect_state( array $state ): array {
		if ( is_null( self::$redirection_url ) ) {
			return $state;
		}

		$state['redirect_to'] = self::$redirection_url;

		return $state;
	}

	/**
	 * Removes the filter for state redirection URL updation.
	 * 
	 * @return void
	 */
	public static function remove_redirect_state_filter() {
		remove_filter( 'rtcamp.google_login_state', [ __CLASS__, 'update_redirect_state' ] );
	}
}
