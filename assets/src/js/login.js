/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

/**
 * Login with Google.
 */
domReady(() => {
	const form =
		document.getElementById('loginform') ||
		document.getElementById('registerform');

	// Set cookie if "Login with Google" button displayed to bypass page cache
	// Do not set on wp login or registration page.
	if (document.querySelector('.wp_google_login') && null === form) {
		document.cookie =
			'vip-go-cb=1;wp-login-with-google=1;path=' +
			encodeURI(window.location.pathname) +
			';';
	}

	if (null === form) {
		return;
	}

	const googleLoginButton = form.querySelector('.wp_google_login');
	googleLoginButton.classList.remove('hidden');
	// HTML is cloned from existing HTML node.
	form.append(googleLoginButton);
});
