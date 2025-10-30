<?php
/**
 * Main plugin class.
 *
 * Setup and bootstrap everything from here.
 *
 * @package RtCamp\GoogleLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin;

use RtCamp\GoogleLogin\Interfaces\Container as ContainerInterface;

/**
 * Class Plugin.
 *
 * @package RtCamp\GoogleLogin
 */
class Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.4.2';

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Plugin's url.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Template directory path.
	 *
	 * @var string
	 */
	public $template_dir;

	/**
	 * Assets directory path.
	 *
	 * @var string
	 */
	public $assets_dir;

	/**
	 * DI Container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * List of active modules.
	 *
	 * @var string[]
	 */
	public $active_modules = [
		'settings',
		'login_flow',
		'assets',
		'shortcode',
		'one_tap_login',
		'google_login_block',
	];

	/**
	 * Plugin constructor.
	 *
	 * @param ContainerInterface $container Container instance.
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Run the plugin
	 *
	 * @return void
	 */
	public function run(): void {
		$this->path         = dirname( __DIR__ );
		$this->url          = plugin_dir_url( trailingslashit( dirname( __DIR__ ) ) . 'login-with-google.php' );
		$this->template_dir = trailingslashit( $this->path ) . 'templates/';
		$this->assets_dir   = trailingslashit( $this->path ) . 'assets/';

		/**
		 * Filter out active modules before modules are initialized.
		 *
		 * @param array $active_modules Active modules list.
		 *
		 * @since 1.0.0
		 */
		$this->active_modules = apply_filters( 'rtcamp.google_login_modules', $this->active_modules );

		$this->container()->define_services();
		$this->activate_modules();

		add_action( 'init', [ $this, 'load_translations' ] );

		add_action( 'plugin_action_links_' . plugin_basename( $this->path ) . '/login-with-google.php', [ $this, 'add_plugin_action_links' ] );

		add_action( 'get_avatar_url', [ $this, 'return_avatar_url' ], 10, 3 );
	}

	/**
	 *  Load the plugin translation if available.
	 *
	 * @return void
	 */
	public function load_translations(): void {
		load_plugin_textdomain( 'login-with-google', false, basename( plugin()->path ) . '/languages/' . get_locale() );
	}

	/**
	 * Return container object
	 *
	 * @return ContainerInterface
	 */
	public function container(): ContainerInterface {
		return $this->container;
	}

	/**
	 * Activate individual modules.
	 *
	 * @return void
	 */
	private function activate_modules(): void {
		foreach ( $this->active_modules as $module ) {
			$module_instance = $this->container()->get( $module );
			$module_instance->init();
		}
	}

	/**
	 * Add settings link to plugin actions
	 *
	 * @param  array $actions Plugin actions.
	 * @return array
	 */
	public function add_plugin_action_links( $actions ) {
		$new_actions = [];

		$new_actions['settings'] = sprintf(
			/* translators: %1$s: Setting name, %2$s: URL for settings page link. */
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'options-general.php?page=login-with-google' ) ),
			esc_html__( 'Settings', 'login-with-google' )
		);

		return array_merge( $new_actions, $actions );
	}

	/**
	 * Return the stored profile picture during the account creation.
	 *
	 * @param string $url The URL of the avatar.
	 * @param mixed  $id_or_email The avatar to retrieve. Accepts a user ID, Gravatar SHA-256 or MD5 hash, user email, WP_User object, WP_Post object, or WP_Comment object.
	 * @param array  $args Arguments passed to get_avatar_data() , after processing.
	 *
	 * @return string The URL of the avatar.
	 */
	public function return_avatar_url( $url, $id_or_email, $args ): string {
		/**
		 * Filter to bypass the use of saved profile picture for avatar.
		 *
		 * @since n.e.x.t
		 *
		 * @param boolean $use_saved_profile_picture_for_avatar Whether to bypass the use the saved profile picture for avatar or not.
		 */
		$use_avatar_url = apply_filters( 'rtcamp.google_use_saved_profile_picture_for_avatar', true );

		if ( ! $use_avatar_url ) {
			return $url;
		}

		$wp_user = null;
		if ( is_int( $id_or_email ) ) {
			$wp_user = get_user_by( 'id', $id_or_email );
		} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$wp_user = get_user_by( 'email', $id_or_email );
		}

		if ( $wp_user ) {
			$width  = isset( $args['width'] ) ? absint( $args['width'] ) : 64;
			$height = isset( $args['height'] ) ? absint( $args['height'] ) : 64;

			$profile_picture_id = get_user_meta( $wp_user->ID, 'rtlwg_profile_picture_id', true );

			if ( ! empty( $profile_picture_id ) ) {
				$url = wp_get_attachment_image_url( $profile_picture_id, [ $width, $height ] );
			}
		}

		return $url;
	}
}
