<?php
/**
 * Plugin Name: WP Google Login
 * Plugin URI:  https://github.com/rtCamp/wp-google-login
 * Description: Minimal plugin which allows WP user to login with google.
 * Version:     0.1
 * Author:      rtCamp
 * Author URI:  https://rtcamp.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-google-login
 */

define( 'WP_GOOGLE_LOGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WP_GOOGLE_LOGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'WP_GOOGLE_LOGIN_VERSION', '0.1' );

$vendor_autoload = sprintf( '%s/vendor/autoload.php', WP_GOOGLE_LOGIN_PATH );

// Missing vendor autoload file or invalid file path.
if ( empty( $vendor_autoload ) || ! file_exists( $vendor_autoload ) || 0 !== validate_file( $vendor_autoload ) ) {
	return;
}

require_once( $vendor_autoload );
require_once( sprintf( '%s/autoloader.php', WP_GOOGLE_LOGIN_PATH ) );

\WP_Google_Login\Inc\Plugin::get_instance();

