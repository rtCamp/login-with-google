/**
 * JS for Settings page.
 *
 * @package login-with-google
 */

/**
 * Settings page JS.
 */
const WpGoogleSettings = {

	/**
	 * Init method.
	 *
	 * @return void
	 */
	init() {
		OneTapLoginSettings.init();
		LoginCookieExpiry.init();
	},
}

const LoginCookieExpiry = {
	/**
	 * Init method.
	 *
	 * @return void
	 */
	init() {
		this.disallowNonNumbers = this.disallowNonNumbers.bind( this );
		this.addEventListeners  = this.addEventListeners.bind( this );
		this.updateCookieWarning = this.updateCookieWarning.bind( this );

		this.cookieExpiryRow = document.querySelector( '.cookie-expiry-row' );
		if ( ! this.cookieExpiryRow ) {
			return;
		}

		this.cookieExpiry = this.cookieExpiryRow.querySelector( '#cookie-expiry' );

		if ( ! this.cookieExpiry ) {
			return;
		}

		this.cookieExpiryWarning = this.cookieExpiryRow.querySelector( '.warning' );

		this.addEventListeners();
	},

	/**
	 * Add event listeners.
	 *
	 * @return void
	 */
	addEventListeners() {
		this.cookieExpiry.addEventListener( 'input', this.updateCookieWarning );
		this.cookieExpiry.addEventListener( 'keypress', this.disallowNonNumbers );
	},

	/**
	 * Disallow anything else than numbers.
	 *
	 * @param {object} event
	 */
	disallowNonNumbers( event ) {
		if ( event.key.length === 1 && ! event.key.match( /[0-9]/ ) ) {
			event.preventDefault();
		}
	},

	/**
	 * Update cookie expiry warning.
	 *
	 * @return void
	 */
	updateCookieWarning() {

		if ( ! this.cookieExpiryWarning ) {
			return;
		}

		const days = parseInt( this.cookieExpiry.value );

		if ( ( days > 14 ) ) {
			this.cookieExpiryWarning.classList.remove( 'hidden' );
		} else {
			this.cookieExpiryWarning.classList.add( 'hidden' );
		}
	},
}

/**
 * One Tap Login Settings.
 */
const OneTapLoginSettings = {

	/**
	 * Init method.
	 *
	 * @return void
	 */
	init() {

		this.setInitialState = this.setInitialState.bind( this );
		this.addEventListeners = this.addEventListeners.bind( this );
		this.toggleOneTapLoginScreenRow = this.toggleOneTapLoginScreenRow.bind( this );

		this.oneTapLoginCheckBox = document.querySelector( '#one-tap-login' );

		if ( ! this.oneTapLoginCheckBox ) {
			return;
		}

		this.oneTapLoginScreenRow = document.querySelector( '.one-tap-login-screen-row' );

		if ( ! this.oneTapLoginScreenRow ) {
			return;
		}

		this.addEventListeners();
		this.setInitialState();

	},

	/**
	 * Add event listeners.
	 *
	 * @return void
	 */
	addEventListeners() {
		this.oneTapLoginCheckBox.addEventListener( 'change', this.toggleOneTapLoginScreenRow );
	},

	/**
	 * Set initial state.
	 *
	 * @return void
	 */
	setInitialState() {
		if ( this.oneTapLoginCheckBox.checked ) {
			this.oneTapLoginScreenRow.classList.remove( 'hidden' );
		} else {
			this.oneTapLoginScreenRow.classList.add( 'hidden' );
		}
	},

	/**
	 * Toggle one tap login screen row.
	 *
	 * @return void
	 */
	toggleOneTapLoginScreenRow() {
		this.oneTapLoginScreenRow.classList.toggle( 'hidden' );
	}
}

document.addEventListener( 'DOMContentLoaded', () => {
	WpGoogleSettings.init();
} );
