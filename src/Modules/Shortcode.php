<?php
/**
 * Shortcode Class.
 *
 * @package RtCamp\GoogleLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Modules;

use RtCamp\GoogleLogin\Interfaces\Module as ModuleInterface;
use RtCamp\GoogleLogin\Utils\Helper;
use RtCamp\GoogleLogin\Utils\GoogleClient;
use function RtCamp\GoogleLogin\plugin;

/**
 * Class Shortcode
 *
 * @package RtCamp\GoogleLogin
 */
class Shortcode implements ModuleInterface {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'google_login';

	/**
	 * Redirect URL.
	 *
	 * @var string
	 */
	public $redirect_uri;

	/**
	 * Google client instance.
	 *
	 * @var GoogleClient
	 */
	private $gh_client;

	/**
	 * Assets object.
	 *
	 * @var Assets
	 */
	private $assets;

	/**
	 * Shortcode constructor.
	 *
	 * @param GoogleClient $client GH Client object.
	 * @param Assets       $assets Assets object.
	 */
	public function __construct( GoogleClient $client, Assets $assets ) {
		$this->gh_client = $client;
		$this->assets    = $assets;
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public function name(): string {
		return 'shortcode';
	}

	/**
	 * Initialization actions.
	 */
	public function init(): void {
		add_shortcode( self::TAG, [ $this, 'callback' ] );
		add_filter( 'do_shortcode_tag', [ $this, 'scan_shortcode' ], 10, 3 );
	}

	/**
	 * Callback function for shortcode rendering.
	 *
	 * @param array $attrs Shortcode attributes.
	 *
	 * @return string
	 */
	public function callback( $attrs = [] ): string {
		$attrs = shortcode_atts(
			[
				'button_text'   => __( 'Login with google', 'login-with-google' ),
				'force_display' => 'no',
				'redirect_to'   => get_permalink(),
			],
			$attrs,
			self::TAG
		);

		if ( ! $this->should_display( $attrs ) ) {
			return '';
		}

		$this->redirect_uri = $attrs['redirect_to'];

		add_filter( 'rtcamp.google_redirect_url', [ $this, 'redirect_url' ] );
		add_filter( 'rtcamp.google_login_state', [ $this, 'state_redirect' ] );

		$attrs['login_url'] = $this->gh_client->authorization_url();

		remove_filter( 'rtcamp.google_login_state', [ $this, 'state_redirect' ] );
		remove_filter( 'rtcamp.google_redirect_url', [ $this, 'redirect_url' ] );
		$template = trailingslashit( plugin()->template_dir ) . 'google-login-button.php';

		return Helper::render_template( $template, $attrs, false );
	}

	/**
	 * Check if the current single post or page contains
	 * shortcode. If it does, enqueue the relevant style.
	 *
	 * @param string       $output Shortcode output.
	 * @param string       $tag Shortcode tag being processed.
	 * @param array|string $attrs Shortcode attributes.
	 *
	 * @return string
	 */
	public function scan_shortcode( string $output, string $tag, $attrs ): string {
		if ( ( ! is_single() && ! is_page() ) || self::TAG !== $tag || ! $this->should_display( (array) $attrs ) ) {
			return $output;
		}

		$this->assets->enqueue_login_styles();

		return $output;
	}


	/**
	 * Filter redirect URL as per shortcode param.
	 *
	 * @param string $url Login URL.
	 *
	 * @return string
	 */
	public function redirect_url( string $url ): string {

		return remove_query_arg( 'redirect_to', $url );
	}

	/**
	 * Add redirect_to location in state.
	 *
	 * @param array $state State data.
	 *
	 * @return array
	 */
	public function state_redirect( array $state ): array {
		if ( is_null( $this->redirect_uri ) ) {
			return $state;
		}

		$state['redirect_to'] = $this->redirect_uri;

		return $state;
	}

	/**
	 * Determines whether to process the shortcode.
	 *
	 * @param array $attrs Shortcode attributes.
	 *
	 * @return bool
	 */
	private function should_display( array $attrs ): bool {
		if ( ! is_user_logged_in() || ( ! empty( $attrs['force_display'] ) && 'yes' === (string) $attrs['force_display'] ) ) {
			return true;
		}

		return false;
	}
}
