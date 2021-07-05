<?php
/**
 * One Tap Login Class.
 *
 * This class will be responsible for handling
 * Google's one tap login for web functioning.
 *
 * @package RtCamp\GoogleLogin\Modules
 * @since 1.0.16
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Modules;

use RtCamp\GoogleLogin\Interfaces\Module;
use function RtCamp\GoogleLogin\plugin;

/**
 * Class OneTapLogin
 *
 * @package RtCamp\GoogleLogin\Modules
 */
class OneTapLogin implements Module {

	/**
	 * Settings Module.
	 *
	 * @var Settings
	 */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public function name(): string {
		return 'one_tap_login';
	}

	/**
	 * Module Initialization activity.
	 *
	 * Everything will happen if and only if one tap is active in settings.
	 */
	public function init(): void {
		if ( $this->settings->one_tap_login ) {
			add_action( 'login_enqueue_scripts', [ $this, 'one_tap_scripts' ] );
			add_action( 'login_footer', [ $this, 'one_tap_prompt' ] );
		}
	}

	/**
	 * Show one tap prompt markup.
	 *
	 * @return void
	 */
	public function one_tap_prompt(): void {?>
		<div id="g_id_onload"
		     data-client_id="<?php echo esc_html( $this->settings->client_id ); ?>"
		     data-login_uri="<?php echo admin_url(); ?>"
		     data-callback="LoginWithGoogleDataCallBack"
		</div>
		<?php
	}

	public function one_tap_scripts() {
	    wp_enqueue_script(
	            'login-with-google-one-tap',
            'https://accounts.google.com/gsi/client'
        );

		wp_enqueue_script(
			'login-with-google-one-tap-js',
			trailingslashit( plugin()->url ) . 'assets/build/js/onetap.js'
		);
    }
}
