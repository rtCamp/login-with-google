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
	 * @return void
	 */
	onContentLoaded() {

		this.loginForm = document.getElementById( 'loginform' );
		this.googleLoginButton = this.loginForm.querySelector( '.wp_google_login' ).cloneNode( true );
		this.googleLoginButton.classList.remove( 'hidden' );

		this.loginForm.append( this.googleLoginButton );
	}

};

wpGoogleLogin.init();
