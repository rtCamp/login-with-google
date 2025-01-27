<?php
/**
 * This file is part of the github login package.
 *
 * (c) rtCamp <contact@rtcamp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

$vendor = dirname( __DIR__, 2 ) . '/vendor/';

if (!file_exists($vendor . 'autoload.php')) {
	die('Please install via Composer before running tests.');
}

require_once __DIR__ . '/stubs/hooks.php';
require_once $vendor . 'autoload.php';
require_once __DIR__ . '/TestCase.php';
WP_Mock::setUsePatchwork(true);
WP_Mock::bootstrap();
unset($vendor);

if ( ! defined( 'GH_PLUGIN_DIR' ) ) {
	define( 'GH_PLUGIN_DIR', dirname( __DIR__, 2 ) );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS );
}

if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS );
}

if ( ! defined( 'MONTH_IN_SECONDS' ) ) {
	define( 'MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS );
}

if ( ! defined( 'YEAR_IN_SECONDS' ) ) {
	define( 'YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS );
}
