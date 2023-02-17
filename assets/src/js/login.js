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
		const rememberMeCheckbox = document.getElementById( 'remember-google-login' );
		rememberMeCheckbox.addEventListener( 'change', this.rememberMe );
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
	},

	/**
	 * Callback function to detect change in state of remember me checkbox.
	 * 
	 * Update request parameters based on user selection
	 *
	 * @return void
	 */
	rememberMe() {

		const loginWithGoogle = document.getElementsByClassName( 'wp_google_login__button' )[0];

		if( this.checked === true ) {
			window.remember = true;
			var params = loginWithGoogle.getAttribute( 'href' );
			var state  = params.substring( params.indexOf( '&state=' ) + 7, params.indexOf( '&scope' ) );

			/* Decodes state value */
			var decodeState = JSON.parse( atob( state ) );

			/* Add remember parameter to state */
			decodeState['remember'] = true;
			var newState = btoa( JSON.stringify( decodeState ) );
			window.orignalParams = params;
			params = params.replace( state, newState );

			/* Replace hyperlink to new state variable */
			loginWithGoogle.setAttribute( 'href', params );

		}

		if( this.checked === false && window.remember === true ) {
			/* Resets href attribute to orignal state if checked and unchecked again */
			loginWithGoogle.setAttribute( 'href', window.orignalParams );
		}
	}



};

wpGoogleLogin.init();
