<?php
/**
 * Template for google login button.
 *
 * @package RtCamp\GithubLogin
 * @since 1.0.0
 */

$button_text = ( ! empty( $button_text ) ) ? $button_text : __( 'Log in with Google', 'login-with-google' );

if ( empty( $login_url ) ) {
	return;
}

?>
<div class="wp_google_login">
	<div class="wp_google_login__button-container">
		<a class="wp_google_login__button" href="<?php echo esc_url( $login_url ); ?>">
			<span class="wp_google_login__google-icon"></span>
			<?php echo esc_html( $button_text ); ?>
		</a>
	</div>
</div>
