<?php
/**
 * Template for google login button.
 *
 * @package RtCamp\GoogleLogin
 * @since 1.0.0
 */

use RtCamp\GoogleLogin\Utils\Helper;

// Variables for rendering the template.
$login_url       = $variables['login_url'] ?? null;
$button_text     = $variables['button_text'] ?? null;
$custom_btn_text = $variables['custom_btn_text'] ?? null;

if ( empty( $login_url ) ) {
	return;
}

if ( is_user_logged_in() ) {
	$button_text = __( 'Log out', 'login-with-google' );
	$button_url  = wp_logout_url( Helper::get_redirect_url() );
} else {
	$button_url = $login_url;

	if ( ! empty( $custom_btn_text ) ) {
		$button_text = $custom_btn_text;
	} elseif ( empty( $button_text ) ) {
		$button_text = __( 'Login with Google', 'login-with-google' );
	}
}
?>
<div class="wp_google_login">
	<div class="wp_google_login__button-container">
		<a class="wp_google_login__button" href="<?php echo esc_url( $button_url ); ?>">
			<span class="wp_google_login__google-icon"></span>
			<?php echo esc_html( $button_text ); ?>
		</a>
	</div>
</div>
