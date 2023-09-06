<?php
/**
 * Plugin Name: Login with Google
 * Description: Allow users to login/register via Google.
 * Version: 1.3.2
 * Author: rtCamp
 * Author URI: https://rtcamp.com
 * Text Domain: login-with-google
 * Domain Path: /languages
 * License: GPLv2+
 * Requires at least: 5.5
 * Requires PHP: 7.4
 *
 * @package RtCamp\GoogleLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin;

use Pimple\Container as PimpleContainer;

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

$hooks = [
	'admin_notices',
	'network_admin_notices',
];

/**
 * PHP 7.4+ is required in order to use the plugin.
 */
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	foreach ( $hooks as $hook ) {
		add_action(
			$hook,
			function () {
				$message = __(
					'Login with google Plugin requires PHP version 7.4 or higher. <br />Please ask your server administrator to update your environment to latest PHP version',
					'login-with-google'
				);

				printf(
					'<div class="notice notice-error"><span class="notice-title">%1$s</span><p>%2$s</p></div>',
					esc_html__(
						'The plugin Login with google has been deactivated',
						'login-with-google'
					),
					wp_kses( $message, [ 'br' => true ] )
				);

				deactivate_plugins( plugin_basename( __FILE__ ) );
			}
		);
	}

	return;
}

/**
 * Autoload the dependencies.
 *
 * @return bool
 */
function autoload(): bool {
	static $done;
	if ( is_bool( $done ) ) {
		return $done;
	}

	if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
		$done = true;

		return true;
	}
	$done = false;

	return false;
}

/**
 * Do not do anything if composer install
 * is not run.
 */
if ( ! autoload() ) {
	return;
}

/**
 * Return the container instance.
 */
function container(): Container {
	static $container;

	if ( null !== $container ) {
		return $container;
	}

	$container = new Container( new PimpleContainer() );

	return $container;
}

/**
 * Return the Plugin instance.
 *
 * @return Plugin
 */
function plugin(): Plugin {
	static $plugin;

	if ( null !== $plugin ) {
		return $plugin;
	}

	$plugin = new Plugin( container() );
	return $plugin;
}

/**
 * Let the magic happen by
 * running the plugin.
 */
add_action(
	'plugins_loaded',
	function() {
		plugin()->run();
	},
	100
);
