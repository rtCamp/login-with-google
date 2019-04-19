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

		// Form either can be login or register form.
		this.form = document.getElementById( 'loginform' ) || document.getElementById( 'registerform' );

		if ( null === this.form ) {
			return;
		}

		this.googleLoginButton = this.form.querySelector( '.wp_google_login' ).cloneNode( true );
		this.googleLoginButton.classList.remove( 'hidden' );

		// if ( 'registerform' === this.form.getAttribute( 'id' ) ) {
		// 	this.googleLoginButton.querySelector('.wp_google_login__button').innerText = 'Signup with Google';
		// }

		this.form.append( this.googleLoginButton );
	}

};

wpGoogleLogin.init();
