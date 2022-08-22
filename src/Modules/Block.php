<?php
/**
 * Block class.
 *
 * This is useful for registering custom gutenberg block to
 * add `Login with Google` button in desired place.
 *
 * Particularly useful in FSE.
 *
 * @package RtCamp\GoogleLogin
 * @since 1.2.3
 */

declare( strict_types=1 );

namespace RtCamp\GoogleLogin\Modules;

use RtCamp\GoogleLogin\Utils\Helper;
use RtCamp\GoogleLogin\Utils\GoogleClient;
use RtCamp\GoogleLogin\Interfaces\Module;
use function RtCamp\GoogleLogin\plugin;

/**
 * Class Block.
 *
 * @package RtCamp\GoogleLogin\Modules
 */
class Block implements Module {

	/**
	 * Script handle.
	 *
	 * @var string
	 */
	const SCRIPT_HANDLE = 'google-login-block';

	/**
	 * Assets object.
	 *
	 * @var Assets
	 */
	public $assets;

	/**
	 * Google client.
	 *
	 * @var GoogleClient
	 */
	public $client;

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public function name(): string {
		return 'google_login_block';
	}

	/**
	 * Block constructor.
	 *
	 * @param Assets       $assets Assets object.
	 * @param GoogleClient $client Google client object.
	 */
	public function __construct( Assets $assets, GoogleClient $client ) {
		$this->assets = $assets;
		$this->client = $client;
	}

	/**
	 * Initialization.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action(
			'wp_enqueue_scripts',
			[ $this->assets, 'register_login_styles' ]
		);

		add_action(
			'enqueue_block_editor_assets',
			[ $this, 'enqueue_block_editor_assets' ]
		);

		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		$this->assets->register_login_styles();
		$this->assets->register_script(
			self::SCRIPT_HANDLE,
			'build/js/block-button.js',
			[
				'wp-blocks',
				'wp-element',
				'wp-editor',
				'wp-components',
			],
			filemtime( trailingslashit( plugin()->assets_dir ) . 'build/js/block-button.js' ),
			false
		);

		wp_enqueue_script( self::SCRIPT_HANDLE );
	}

	/**
	 * Register the block.
	 *
	 * @return void
	 */
	public function register(): void {
		register_block_type(
			'google-login/login-button',
			[
				'editor_style'    => $this->assets::LOGIN_BUTTON_STYLE_HANDLE,
				'style'           => $this->assets::LOGIN_BUTTON_STYLE_HANDLE,
				'render_callback' => [ $this, 'render_login_button' ],
				'attributes'      => [
					'buttonText'   => [
						'type' => 'string',
					],
					'forceDisplay' => [
						'type'    => 'boolean',
						'default' => false,
					],
				],
			]
		);
	}

	/**
	 * Render callback for block.
	 *
	 * This will output the Login with Google
	 * button if user is not logged in currently.
	 *
	 * @param string $attributes Block attributes.
	 *
	 * @return string
	 */
	public function render_login_button( $attributes ): string {
		/**
		 * This filter is useful where we want to forcefully display login button,
		 * even when user is already logged-in in system.
		 *
		 * @param bool $display flag to display button. Default false.
		 *
		 * @since 1.2.3
		 */
		$force_display = $attributes['forceDisplay'] ?? false;
		if (  $force_display || ! is_user_logged_in() || apply_filters( 'rtcamp.google_login_button_display', false ) ) {
			$markup = $this->markup(
				[
					'login_url'           => $this->client->authorization_url(),
					'custom_btn_text'     => $attributes['buttonText'] ?? false,
					'force_display_block' => $attributes['forceDisplay'] ?? false,
				]
			);

			ob_start();
			?>
			<div class="wp_google_login">
				<?php echo wp_kses_post( $markup ); ?>
			</div>
			<?php

			return ob_get_clean();
		}

		return '';
	}

	/**
	 * Return markup for login button.
	 *
	 * @param array $args Arguments passed to template.
	 *
	 * @return string
	 */
	private function markup( array $args = [] ): string {
		$args = wp_parse_args(
			$args,
			[
				'login_url'       => '#',
				'custom_btn_text' => '',
				'forceDisplay'    => false,
			]
		);

		$template = trailingslashit( plugin()->template_dir ) . 'google-login-button.php';
		return Helper::render_template( $template, $args, false );
	}
}
