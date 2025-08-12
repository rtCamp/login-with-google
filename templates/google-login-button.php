<?php
/**
 * Template for google login button.
 *
 * @package RtCamp\GithubLogin
 * @since 1.0.0
 * @var array<string, string> $variables
 */

use RtCamp\GoogleLogin\Utils\Helper;

if (!empty($variables['custom_btn_text'])) {
	$button_text = esc_html($variables['custom_btn_text']);
} else {
	$button_text = $variables['button_text'] ?? __( 'Login with Google', 'login-with-google' );
}

if (empty($variables['login_url'])) {
	return;
}

$button_url = $variables['login_url'];

if ( is_user_logged_in() ) {
	$button_text  = __( 'Log out', 'login-with-google' );
	$redirect_url = Helper::get_redirect_url();
	$button_url   = wp_logout_url( $redirect_url );
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
