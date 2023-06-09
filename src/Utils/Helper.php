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
	 * To render or return output of template.
	 *
	 * @param string $template_path Template path.
	 * @param array  $variables     array of variables that needed on template.
	 * @param bool   $echo          Whether need to echo to return HTML markup.
	 *
	 * @return string
	 */
	public static function render_template( $template_path, $variables = [], $echo = true ) {

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

		if ( true === $echo ) {

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
}
