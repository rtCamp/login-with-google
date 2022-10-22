/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * One Tap Login.
 *
 * @param {Object} response Response from Google.
 */
window.LoginWithGoogleDataCallBack = function (response) {
	const { ajaxurl, homeurl, state } = TempAccessOneTap;

	fetch(ajaxurl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
		},
		body: new URLSearchParams({
			action: 'validate_id_token',
			token: response.credential,
			state,
		}),
	})
		.then((res) => res.json())
		.then((res) => {
			if (!res.success) {
				// eslint-disable-next-line no-alert
				alert(res.data);
				return;
			}

			try {
				const getRedirectTo = new URL(res.data.redirect);
				const getHomeUrl = new URL(homeurl);

				if (getRedirectTo.host !== getHomeUrl.host) {
					throw new URIError(
						__('Invalid URL for Redirection', 'login-with-google')
					);
				}
			} catch (e) {
				// eslint-disable-next-line no-alert
				alert(e.message);
				return;
			}

			window.location = res.data.redirect;
		});
};
