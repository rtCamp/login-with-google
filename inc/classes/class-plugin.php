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
	 * Instance of Google_Auth class.
	 *
	 * @var \WP_Google_Login\Inc\Google_Auth
	 */
	protected $_google_auth = false;

	/**
	 * Plugin constructor.
	 *
	 * @codeCoverageIgnore
	 */
	protected function __construct() {

		$this->_google_auth = Google_Auth::get_instance();

		$this->_setup_hooks();
	}

	/**
	 * To setup actions/filters.
	 *
	 * @return void
	 */
	protected function _setup_hooks() {

		/**
		 * Actions
		 */
		add_action( 'login_enqueue_scripts', [ $this, 'login_enqueue_scripts' ] );
		add_action( 'login_form', [ $this, 'add_google_login_button' ] );
		add_action( 'register_form', [ $this, 'add_google_login_button' ] );

	}

	/**
	 * To enqueue style and script for login page.
	 *
	 * @return void
	 */
	public function login_enqueue_scripts() {

		wp_enqueue_script( 'wp_google_login_script', sprintf( '%s/assets/build/js/login.js', WP_GOOGLE_LOGIN_URL ), [], WP_GOOGLE_LOGIN_VERSION );
		wp_enqueue_style( 'wp_google_login_style', sprintf( '%s/assets/build/css/login.css', WP_GOOGLE_LOGIN_URL ), [], WP_GOOGLE_LOGIN_VERSION );

	}

	/**
	 * To render google login button.
	 *
	 * @return void
	 */
	public function add_google_login_button() {

		$template_path = sprintf( '%s/template/google-login-button.php', WP_GOOGLE_LOGIN_PATH );
		$login_url     = $this->_google_auth->get_login_url();

		Helper::render_template( $template_path, [
			'login_url' => $login_url,
		] );
	}

}