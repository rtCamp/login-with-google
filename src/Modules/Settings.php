<?php
/**
 * Register the settings under settings page and also
 * provide the interface to retrieve the settings.
 *
 * @package RtCamp\GoogleLogin
 * @since 1.0.0
 * @author rtCamp <contact@rtcamp.com>
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Modules;

use RtCamp\GoogleLogin\Interfaces\Module as ModuleInterface;

/**
 * Class Settings.
 *
 * @property string|null whitelisted_domains
 * @property string|null client_id
 * @property string|null client_secret
 * @property bool|null registration_enabled
 *
 * @package RtCamp\GoogleLogin\Modules
 */
class Settings implements ModuleInterface {

	/**
	 * Settings values.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * Getters for settings values.
	 *
	 * @var string[]
	 */
	private $getters = [
		'WP_GOOGLE_LOGIN_CLIENT_ID'         => 'client_id',
		'WP_GOOGLE_LOGIN_SECRET'            => 'client_secret',
		'WP_GOOGLE_LOGIN_USER_REGISTRATION' => 'registration_enabled',
		'WP_GOOGLE_LOGIN_WHITELIST_DOMAINS' => 'whitelisted_domains',
	];

	/**
	 * Getter method.
	 *
	 * @param string $name Name of option to fetch.
	 */
	public function __get( string $name ) {
		if ( in_array( $name, $this->getters, true ) ) {
			$constant_name = array_search( $name, $this->getters );

			return defined( $constant_name ) ? constant( $constant_name ) : ( $this->options[ $name ] ?? '' );
		}

		return null;
	}

	/**
	 * Return module name.
	 *
	 * @return string
	 */
	public function name(): string {
		return 'settings';
	}

	/**
	 * Initialization of module.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->options = get_option( 'wp_google_login_settings', [] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'settings_page' ] );
	}

	/**
	 * Register the settings, section and fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting( 'wp_google_login', 'wp_google_login_settings' );

		add_settings_section(
			'wp_google_login_section',
			__( 'Log in with Google Settings', 'login-with-google' ),
			function () {
			},
			'login-with-google'
		);

		add_settings_field(
			'wp_google_login_client_id',
			__( 'Client ID', 'login-with-google' ),
			[ $this, 'client_id_field' ],
			'login-with-google',
			'wp_google_login_section',
			[ 'label_for' => 'client-id' ]
		);

		add_settings_field(
			'wp_google_login_client_secret',
			__( 'Client Secret', 'login-with-google' ),
			[ $this, 'client_secret_field' ],
			'login-with-google',
			'wp_google_login_section',
			[ 'label_for' => 'client-secret' ]
		);

		add_settings_field(
			'wp_google_allow_registration',
			__( 'Create new user', 'login-with-google' ),
			[ $this, 'user_registration' ],
			'login-with-google',
			'wp_google_login_section',
			[ 'label_for' => 'user-registration' ]
		);

		add_settings_field(
			'wp_google_whitelisted_domain',
			__( 'Whitelisted Domains', 'login-with-google' ),
			[ $this, 'whitelisted_domains' ],
			'login-with-google',
			'wp_google_login_section',
			[ 'label_for' => 'whitelisted-domains' ]
		);
	}

	/**
	 * Render client ID field.
	 *
	 * @return void
	 */
	public function client_id_field(): void { ?>
		<input type='text' name='wp_google_login_settings[client_id]' id="client-id" value='<?php echo esc_attr( $this->client_id ); ?>' autocomplete="off" <?php $this->disabled( 'client_id' ); ?> />
		<p class="description">
			<?php
			echo wp_kses_post(
				sprintf(
					'<p>%1s <a target="_blank" href="%2s">%3s</a>.</p>',
					esc_html__( 'Create oAuth Client ID and Client Secret at', 'login-with-google' ),
					'https://console.developers.google.com/apis/dashboard',
					'console.developers.google.com'
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render client secret field.
	 *
	 * @return void
	 */
	public function client_secret_field(): void {
		?>
		<input type='password' name='wp_google_login_settings[client_secret]' id="client-secret" value='<?php echo esc_attr( $this->client_secret ); ?>' autocomplete="off" <?php $this->disabled( 'client_secret' ); ?> />
		<?php
	}

	/**
	 * User registration field.
	 *
	 * This will tell us whether or not to create the user
	 * if the user does not exist on WP application.
	 *
	 * This is irrespective of registration flag present in Settings > General
	 *
	 * @return void
	 */
	public function user_registration(): void {
		?>
		<label style='display:block;margin-top:6px;'><input <?php $this->disabled( 'registration_enabled' ); ?> type='checkbox'
															name='wp_google_login_settings[registration_enabled]'
															id="user-registration" <?php echo esc_attr( checked( $this->registration_enabled ) ); ?>
															value='1'>
			<?php esc_html_e( 'Create a new user account if it does not exist already', 'login-with-google' ); ?>
		</label>
		<p class="description">
			<?php
			echo wp_kses_post(
				sprintf(
				/* translators: %1s will be replaced by page link */
					__( 'If this setting is checked, a new user will be created even if <a target="_blank" href="%1s">membership setting</a> is off.', 'login-with-google' ),
					is_multisite() ? 'network/settings.php' : 'options-general.php'
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Whitelisted domains for registration.
	 *
	 * Only emails belonging to these domains would be preferred
	 * for registration.
	 *
	 * If left blank, all domains would be allowed.
	 *
	 * @return void
	 */
	public function whitelisted_domains(): void {
		?>
        <input <?php $this->disabled( 'whitelisted_domains' ); ?> type='text' name='wp_google_login_settings[whitelisted_domains]' id="whitelisted-domains" value='<?php echo esc_attr( $this->whitelisted_domains ); ?>' autocomplete="off" />
        <p class="description">
			<?php echo esc_html( __( 'Add each domain comma separated', 'login-with-google' ) ); ?>
        </p>
        <?php
	}

	/**
	 * Add settings sub-menu page in admin menu.
	 *
	 * @return void
	 */
	public function settings_page(): void {
		add_options_page(
			__( 'Login with Google settings', 'login-with-google' ),
			__( 'Login with Google', 'login-with-google' ),
			'manage_options',
			'login-with-google',
			[ $this, 'output' ]
		);
	}

	/**
	 * Output the plugin settings.
	 *
	 * @return void
	 */
	public function output(): void {
		?>
		<div class="wrap">
		<form action='options.php' method='post'>
			<?php
			settings_fields( 'wp_google_login' );
			do_settings_sections( 'login-with-google' );
			submit_button();
			?>
		</form>
		</div>
		<?php
	}

	/**
	 * Outputs the disabled attribute if field needs to
     * be disabled.
     *
     * @param string $id Input ID.
     *
     * @return void
	 */
	private function disabled( string $id ): void {
	    if ( empty( $id ) ) {
	        return;
	    }

	    $constant_name = array_search( $id, $this->getters, true );

	    if ( false !== $constant_name ) {
	        if ( defined( $constant_name ) ) {
	            echo esc_attr( 'disabled="disabled"' );
            }
        }
    }
}
