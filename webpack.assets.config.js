/**
 * Extends @wordpress/scripts default webpack config for non-block assets.
 *
 * @package login-with-google
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const path = require( 'path' );

/**
 * Export a function so webpack passes argv, letting us read argv.mode instead
 * of process.env.NODE_ENV. wp-scripts build always sets NODE_ENV=production
 * regardless of --mode=development, so process.env.NODE_ENV is unreliable.
 */
module.exports = ( _env, argv ) => {
	const isProduction = argv.mode !== 'development';

	const plugins = defaultConfig.plugins
		.filter( ( plugin ) => ! ( plugin instanceof MiniCssExtractPlugin ) )
		.filter( ( plugin ) => plugin.constructor.name !== 'RtlCssPlugin' )
		.concat(
			new MiniCssExtractPlugin( {
				filename: 'css/[name].css',
			} )
		);

	const moduleConfig = {
		...defaultConfig.module,
		rules: defaultConfig.module.rules.map( ( rule ) => {
			if ( isProduction || ! rule.use || ! Array.isArray( rule.use ) ) {
				return rule;
			}
			return {
				...rule,
				use: rule.use.map( ( loader ) => {
					if (
						! loader.options ||
						! ( 'sourceMap' in loader.options )
					) {
						return loader;
					}
					return {
						...loader,
						options: { ...loader.options, sourceMap: true },
					};
				} ),
			};
		} ),
	};

	return {
		...defaultConfig,
		entry: {
			login: path.resolve( __dirname, 'assets/src/js/login.js' ),
			onetap: path.resolve( __dirname, 'assets/src/js/onetap.js' ),
		},
		output: {
			...( defaultConfig.output || {} ),
			path: path.resolve( __dirname, 'assets/build' ),
			filename: 'js/[name].js',
			clean: false,
		},
		devtool: isProduction ? false : 'source-map',
		module: moduleConfig,
		plugins,
	};
};
