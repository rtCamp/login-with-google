<?php
/**
 * This file is part of the github login package.
 *
 * (c) rtCamp <contact@rtcamp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare( strict_types=1 );

$vendor = dirname( __DIR__, 2 ) . '/vendor/';

if ( ! file_exists( $vendor . 'autoload.php' ) ) {
	die( 'Please install via Composer before running tests.' );
}

require_once __DIR__ . '/stubs/hooks.php';
require_once $vendor . 'autoload.php';
require_once __DIR__ . '/TestCase.php';

WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();

unset( $vendor );

if ( ! defined( 'GH_PLUGIN_DIR' ) ) {
	define( 'GH_PLUGIN_DIR', dirname( __DIR__, 2 ) );
}
