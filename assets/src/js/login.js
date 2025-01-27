/**
 * JS for Login and Register page.
 *
 * @package login-with-google
 */

const wpGoogleLogin = {

	/**
	 * Init method.
	 *
	 * @return void
	 */
	init() {
		document.addEventListener( 'DOMContentLoaded', this.onContentLoaded );
	},

	/**
	 * Callback function when content is load.
	 * To render the google login button at after login form.
	 *
	 * Set cookie if "Login with Google" button displayed to bypass page cache
	 * Do not set on wp login or registration page.
	 *
	 * @return void
	 */
	onContentLoaded() {

		// Form either can be login or register form.
		this.form = document.getElementById( 'loginform' ) || document.getElementById( 'registerform' );

		// Set cookie if "Login with Google" button displayed to bypass page cache
		// Do not set on wp login or registration page.
		if ( document.querySelector( '.wp_google_login' ) && null === this.form ) {
			document.cookie = 'vip-go-cb=1;wp-login-with-google=1;path=' + encodeURI(window.location.pathname) + ';';
		}

		if ( null === this.form ) {
			return;
		}

		this.googleLoginButton = this.form.querySelector( '.wp_google_login' );
		this.googleLoginButton.classList.remove( 'hidden' );
		// HTML is cloned from existing HTML node.
		this.form.append( this.googleLoginButton );
	}

};

wpGoogleLogin.init();
