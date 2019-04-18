<?php
/**
 * Main plugin class.
 *
 * @author  Dhaval Parekh <dmparekh007@gmail.com>
 *
 * @package wp-google-login
 */

namespace WP_Google_Login\Inc;

use WP_Google_Login\Inc\Traits\Singleton;

/**
 * Class Plugin
 */
class Plugin {

	use Singleton;

	/**
	 * Plugin constructor.
	 */
	protected function __construct() {
		// Init plugin here.
	}

}