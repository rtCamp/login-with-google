/**
 * Webpack mix file.
 *
 * @package login-with-google
 */

const mix = require( 'laravel-mix' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require('path');

mix.options( {
	processCssUrls: false
} );

mix.copy( 'src/images', 'build/images' )
	.copy( 'src/js/onetap.js', 'build/js' )
	.minify( 'build/js/onetap.js' )
	.js( 'src/js/login.js', 'build/js' )
	.sass( 'src/scss/login.scss', 'build/css' )
	.webpackConfig(defaultConfig);
