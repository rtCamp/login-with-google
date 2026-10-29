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
		document.addEventListener( 'click', this.onButtonClick );
	},

	/**
	 * Synchronize state token in cookie and URL on button click.
	 *
	 * @param {MouseEvent} event Click event.
	 * @return {void}
	 */
	onButtonClick( event ) {
		const button = event.target ? event.target.closest( '.wp_google_login__button' ) : null;
		if ( ! button || ! button.href ) {
			return;
		}

		try {
			const url = new URL( button.href );
			const stateParam = url.searchParams.get( 'state' );
			if ( stateParam ) {
				const decoded = JSON.parse( atob( stateParam ) );
				const isHttps = window.location.protocol === 'https:';
				const cookieName = button.getAttribute( 'data-cookie-name' ) || ( isHttps ? '__Host-lwg_state_token' : 'lwg_state_token' );

				const match = document.cookie.match( new RegExp( '(^|;\\s*)' + cookieName + '=([^;]*)' ) );
				let stateToken = match ? match[2] : '';

				if ( ! stateToken ) {
					const randomBytes = new Uint8Array( 16 );
					window.crypto.getRandomValues( randomBytes );
					stateToken = Array.from( randomBytes, ( byte ) => ( '0' + ( byte & 0xFF ).toString( 16 ) ).slice( -2 ) ).join( '' );
					const secureFlag = ( isHttps || cookieName.indexOf( '__Host-' ) === 0 ) ? '; Secure' : '';
					document.cookie = `${cookieName}=${stateToken}; Path=/; max-age=3600; SameSite=Lax${secureFlag}`;
				}

				decoded.state_token = stateToken;
				url.searchParams.set( 'state', btoa( JSON.stringify( decoded ) ) );
				button.href = url.toString();
			}
		} catch ( err ) {
			// Fail gracefully.
		}
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
