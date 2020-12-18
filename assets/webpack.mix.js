/**
 * Webpack mix file.
 *
 * @package login-with-google
 */

let mix = require( 'laravel-mix' );

mix.options( {
	processCssUrls: false
} );

mix.copy( 'src/images', 'build/images' )
	.js( 'src/js/login.js', 'build/js' )
	.sass( 'src/scss/login.scss', 'build/css' );