/**
 * Webpack mix file.
 *
 * @package login-with-google
 */

let mix = require( 'laravel-mix' );

mix.options( {
	processCssUrls: false
} );

mix.copy( 'assets/src/images', 'assets/build/images' )
	.copy( 'assets/src/js/onetap.js', 'assets/build/js' )
	.minify( 'assets/build/js/onetap.js' )
	.js( 'assets/src/js/login.js', 'assets/build/js' )
	.sass( 'assets/src/scss/button/style.scss', 'assets/build/css/button' );
