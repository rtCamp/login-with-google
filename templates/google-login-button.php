<?php
/**
 * Template for google login button.
 *
 * @package RtCamp\GithubLogin
 * @since 1.0.0
 */

if ( isset( $custom_btn_text ) && $custom_btn_text ) {
	$button_text = esc_html( $custom_btn_text );
} else {
	$button_text = ( ! empty( $button_text ) ) ? $button_text : __( 'Log in with Google', 'login-with-google' );
}

if ( empty( $login_url ) ) {
	return;
}

$button_url = $login_url;

if ( is_user_logged_in() ) {
	$button_text = __( 'Log out', 'login-with-google' );
	$button_url   = wp_logout_url( get_permalink() );
}
?>
<div class="wp_google_login">
	<div class="wp_google_login__button-container">
		<a class="wp_google_login__button"
			<?php
			printf( ' href="%s"', esc_url( $button_url ) );
			?>
		>
			<span class="wp_google_login__google-icon"></span>
			<?php echo esc_html( $button_text ); ?>
		</a>
	</div>
</div>
