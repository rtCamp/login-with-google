/**
 * Webpack config for non-block assets.
 *
 * Handles:
 * - JS bundle: assets/src/js/login.js → assets/build/js/login.js
 * - SCSS:      assets/src/scss/button/style.scss → assets/build/css/button/style.css
 * - Copy:      assets/src/js/onetap.js → assets/build/js/onetap.js
 * - Minify:    assets/src/js/onetap.js → assets/build/js/onetap.min.js
 * - Images:    assets/src/images/ → assets/build/images/
 *
 * Block assets are built separately via:
 *   wp-scripts build --source-path=./assets/src/blocks --output-path=./assets/build/blocks
 *
 * @package login-with-google
 */

const path = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );

module.exports = ( env, argv ) => {
	const isProduction = process.env.NODE_ENV === 'production' || ( argv && argv.mode === 'production' );

	return {
		mode: isProduction ? 'production' : 'development',
		devtool: isProduction ? false : 'source-map',

		/**
		 * login.js imports the SCSS so both JS and CSS come from a single entry.
		 * MiniCssExtractPlugin splits the CSS out to css/button/style.css.
		 */
		entry: {
			'js/login': path.resolve( __dirname, 'assets/src/js/login.js' ),
		},

		output: {
			path: path.resolve( __dirname, 'assets/build' ),
			filename: '[name].js',
			clean: false, // blocks live in assets/build/blocks — never wipe them
		},

		module: {
			rules: [
				{
					test: /\.(sc|sa|c)ss$/,
					use: [
						MiniCssExtractPlugin.loader,
						{
							loader: 'css-loader',
							options: {
								// Keep url() references as-is (same as laravel-mix processCssUrls:false)
								url: false,
							},
						},
						{
							loader: 'sass-loader',
							options: {
								implementation: require( 'sass' ),
							},
						},
					],
				},
			],
		},

		plugins: [
			// Extract CSS imported in login.js to a dedicated stylesheet
			new MiniCssExtractPlugin( {
				filename: 'css/button/style.css',
			} ),

			new CopyWebpackPlugin( {
				patterns: [
					// Copy images directory
					{
						from: path.resolve( __dirname, 'assets/src/images' ),
						to: path.resolve( __dirname, 'assets/build/images' ),
					},
					// Copy onetap.js as-is (not bundled through webpack)
					{
						from: path.resolve( __dirname, 'assets/src/js/onetap.js' ),
						to: path.resolve( __dirname, 'assets/build/js/onetap.js' ),
					},
					// Minified copy of onetap.js
					{
						from: path.resolve( __dirname, 'assets/src/js/onetap.js' ),
						to: path.resolve( __dirname, 'assets/build/js/onetap.min.js' ),
						transform: async ( content ) => {
							const { minify } = require( 'terser' );
							const result = await minify( content.toString() );
							return Buffer.from( result.code );
						},
					},
				],
			} ),
		],
	};
};
