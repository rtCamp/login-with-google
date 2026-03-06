/**
 * Extends @wordpress/scripts default webpack config for non-block assets.
 *
 * Entry points:
 *  - login  → assets/build/js/login.js  + style-login.css (from SCSS import)
 *  - onetap → assets/build/js/onetap.js
 *
 * Images referenced via url() in SCSS are automatically copied and versioned
 * by the default @wordpress/scripts asset handling.
 *
 * Block assets are built separately via:
 *   wp-scripts build --source-path=./assets/src/blocks --output-path=./assets/build/blocks
 *
 * @package login-with-google
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		login: path.resolve( __dirname, 'assets/src/js/login.js' ),
		onetap: path.resolve( __dirname, 'assets/src/js/onetap.js' ),
	},
	output: {
		...( defaultConfig.output || {} ),
		path: path.resolve( __dirname, 'assets/build/js' ),
		filename: '[name].js',
		clean: false,
	},
};
