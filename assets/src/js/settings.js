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
		this.updateCookieExpiry = this.updateCookieExpiry.bind( this );
		this.disallowNonNumbers = this.disallowNonNumbers.bind( this );
		this.addEventListeners  = this.addEventListeners.bind( this );
		this.getDaysAndHours    = this.getDaysAndHours.bind( this );
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
		this.humanReadableCookieExpiry = this.cookieExpiryRow.querySelector( '.human-readable-cookie-expiry' );

		if ( this.humanReadableCookieExpiry ) {
			this.humanReadableDays = this.humanReadableCookieExpiry.querySelector( '.human-readable-cookie-expiry .days' );
			this.humanReadableHours = this.humanReadableCookieExpiry.querySelector( '.human-readable-cookie-expiry .hours' );
		}

		this.addEventListeners();
	},

	/**
	 * Add event listeners.
	 *
	 * @return void
	 */
	addEventListeners() {
		this.cookieExpiry.addEventListener( 'input', this.updateCookieExpiry );
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
	 * Disallow anything else than numbers.
	 */
	updateCookieExpiry() {
		this.updateCookieWarning();
		this.updateDaysAndHours();
	},

	/**
	 * Update days and hours.
	 *
	 * @return void
	 */
	updateDaysAndHours() {
		const cookieExpiry = parseInt( this.cookieExpiry.value );
		const { days, hours } = this.getDaysAndHours( cookieExpiry );
		const { _n, sprintf } = wp.i18n;

		if ( ! days && ! hours ) {
			this.humanReadableCookieExpiry.classList.add( 'hidden' );
		} else {
			this.humanReadableCookieExpiry.classList.remove( 'hidden' );
		}

		if ( this.humanReadableDays ) {
			this.humanReadableDays.textContent = ( days ) ? sprintf( _n( '%s day', '%s days', days, 'login-with-google' ), days ) : '';
		}

		if ( this.humanReadableHours ) {
			this.humanReadableHours.textContent = ( hours ) ? sprintf( _n( '%s hour', '%s hours', hours, 'login-with-google' ), hours ) : '';
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

		const cookieExpiry = parseInt( this.cookieExpiry.value );
		const { days, hours } = this.getDaysAndHours( cookieExpiry );

		if ( ( days > 14 ) || ( 14 === days && hours > 0 ) ) {
			this.cookieExpiryWarning.classList.remove( 'hidden' );
		} else {
			this.cookieExpiryWarning.classList.add( 'hidden' );
		}

	},

	/**
	 * Convert hours to human-readable format.
	 *
	 * @param {number} hours
	 */
	getDaysAndHours( hours ) {
		return {
			days: Math.floor( hours / 24 ),
			hours: hours % 24,
		}
	}
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
