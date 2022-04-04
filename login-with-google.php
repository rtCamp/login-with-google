<?php
/**
 * Plugin Name: Log in with Google
 * Plugin URI:  https://github.com/rtCamp/login-with-google
 * Description: Allow users to log in with Google on the WordPress login screen.
 * Version:     1.0.14
 * Author:      rtCamp
 * Author URI:  https://rtcamp.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: login-with-google
 *
 * @package login-with-google
 */

define( 'WP_GOOGLE_LOGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WP_GOOGLE_LOGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'WP_GOOGLE_LOGIN_PLUGIN_NAME', plugin_basename( __FILE__ ) );
define( 'WP_GOOGLE_LOGIN_VERSION', '1.0.14' );

$vendor_autoload = sprintf( '%s/vendor/autoload.php', WP_GOOGLE_LOGIN_PATH );

// Missing vendor autoload file or invalid file path.
$validate_file = validate_file( $vendor_autoload );
// Function validate_file returns 2 for Windows drive path, so we check that as well.
if ( empty( $vendor_autoload ) || ! file_exists( $vendor_autoload ) || ( 0 !== $validate_file && 2 !== $validate_file ) ) {
	return;
}


// We already making sure that file is exists and valid.
require_once plugin_dir_path( __FILE__ ) . 'autoloader.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/functions.php';

\WP_Google_Login\Inc\Plugin::get_instance();
