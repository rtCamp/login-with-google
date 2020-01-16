<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Wp_Google_Login
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {

	define( 'WP_GOOGLE_LOGIN_CLIENT_ID', '1' );
	define( 'WP_GOOGLE_LOGIN_SECRET', '1' );

	require_once dirname( dirname( __FILE__ ) ) . '/wp-google-login.php';
	require_once dirname( __FILE__ ) . '/class-utility.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require_once $_tests_dir . '/includes/bootstrap.php';
