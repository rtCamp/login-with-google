<?php
/**
 * Template for google login button.
 *
 * @author  Dhaval Parekh <dmparekh007@gmail.com>
 *
 * @package wp-google-login
 */

$button_text = ( ! empty( $button_text ) ) ? $button_text : __( 'Login with Google', 'wp-google-login' );

?>
<div class="wp_google_login hidden">
	<div class="wp_google_login__divider"><span>or</span></div>
	<div class="wp_google_login__button-container">
		<a class="wp_google_login__button" href="#">
			<span class="wp_google_login__google-icon"></span>
			<?php echo esc_html( $button_text ); ?>
		</a>
	</div>
</div>
