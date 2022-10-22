/**
 * WordPress dependencies
 */
const defaultConfig = require('@wordpress/scripts/config/.eslintrc.js');

module.exports = {
	...defaultConfig,
	env: {
		browser: true,
		es6: true,
	},
	globals: {
		wp: 'writable',
		TempAccessOneTap: 'readonly', // TempAccessOneTap is set via wp_add_inline_script() in OneTapLogin.php file.
	},
};
