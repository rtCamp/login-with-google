<?php
/**
 * Helper class for all helper function.
 *
 * @author  Dhaval Parekh <dmparekh007@gmail.com>
 *
 * @package wp-google-login
 */

namespace WP_Google_Login\Inc;

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

		if ( empty( $template_path ) || ! file_exists( $template_path ) || 0 !== validate_file( $template_path ) ) {
			return '';
		}

		if ( ! empty( $variables ) ) {
			// Will skips those variables, those already defined.
			extract( $variables, EXTR_SKIP );
		}


		if ( true === $echo ) {

			// Load template and output the data.
			require $template_path;

			return ''; // Job done, bail out.
		}

		ob_start();
		require $template_path; // Load template output in buffer.

		return ob_get_clean();

	}
}