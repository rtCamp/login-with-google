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

use function RtCamp\GoogleLogin\plugin;

/**
 * Class Settings.
 *
 * @property string|null whitelisted_domains
 * @property string|null client_id
 * @property string|null client_secret
 * @property bool|null registration_enabled
 * @property bool|null one_tap_login
 * @property string    one_tap_login_screen
 * @property int|null  cookie_expiry
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
		'WP_GOOGLE_ONE_TAP_LOGIN'           => 'one_tap_login',
		'WP_GOOGLE_ONE_TAP_LOGIN_SCREEN'    => 'one_tap_login_screen',
		'WP_GOOGLE_COOKIE_EXPIRY'           => 'cookie_expiry',
	];

	/**
	 * Getter method.
	 *
	 * @param string $name Name of option to fetch.
	 */
	public function __get( string $name ) {
		if ( in_array( $name, $this->getters, true ) ) {
			$constant_name = array_search( $name, $this->getters, true );

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
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ]  );
		if ( ! empty( $this->settings->cookie_expiry ) && is_numeric( $this->settings->cookie_expiry ) ) {
			add_filter( 'auth_cookie_expiration', [ $this, 'modify_cookie_expiry' ], 10, 3 );
		}
	}

	/**
	 * Modify cookie expiry.
	 *
	 * @param int  $expiration Current cookie expiry.
	 * @param int  $user_id    User ID.
	 * @param bool $remember   Whether to remember the user login. Default false.
	 *
	 * @return int
	 */
	public function modify_cookie_expiry( int $expiration, int $user_id, bool $remember ): int {
		if ( ! is_numeric( $this->cookie_expiry ) ) {
			return $expiration;
		}

		if ( $remember ) {
			return $this->cookie_expiry * DAY_IN_SECONDS + $expiration;
		}

		return $this->cookie_expiry * HOUR_IN_SECONDS;
	}

	/**
	 * Enqueue scripts and styles for admin.
	 *
	 * @param string $hook_suffix Current page hook.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts( string $hook_suffix ): void {

		if ( 'settings_page_login-with-google' !== $hook_suffix ) {
			return;
		}

		$filename = ( defined( 'WP_SCRIPT_DEBUG' ) && true === WP_SCRIPT_DEBUG ) ? 'settings.min.js' : 'settings.js';

		wp_register_script(
			'login-with-google-settings',
			trailingslashit( plugin()->url ) . 'assets/build/js/' . $filename,
			[
				'wp-i18n',
			],
			filemtime( trailingslashit( plugin()->path ) . 'assets/build/js/settings.js' ),
			true
		);

		wp_enqueue_script( 'login-with-google-settings' );

		wp_register_style(
			'login-with-google-settings',
			trailingslashit( plugin()->url ) . 'assets/build/css/settings.css',
			[],
			filemtime( trailingslashit( plugin()->path ) . 'assets/build/css/settings.css' )
		);

		wp_enqueue_style( 'login-with-google-settings' );
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
			__( 'Create New User', 'login-with-google' ),
			[ $this, 'user_registration' ],
			'login-with-google',
			'wp_google_login_section',
			[ 'label_for' => 'user-registration' ]
		);

		add_settings_field(
			'wp_google_one_tap_login',
			__( 'Enable One Tap Login', 'login-with-google' ),
			[ $this, 'one_tap_login' ],
			'login-with-google',
			'wp_google_login_section',
			[ 'label_for' => 'one-tap-login', ]
		);

		add_settings_field(
			'wp_google_one_tap_login_screen',
			__( 'One Tap Login Locations', 'login-with-google' ),
			[ $this, 'one_tap_login_screens' ],
			'login-with-google',
			'wp_google_login_section',
			[
				'label_for' => 'one-tap-login-screen',
				'class'     => 'one-tap-login-screen-row',
			]
		);

		add_settings_field(
			'wp_google_whitelisted_domain',
			__( 'Whitelisted Domains', 'login-with-google' ),
			[ $this, 'whitelisted_domains' ],
			'login-with-google',
			'wp_google_login_section',
			[ 'label_for' => 'whitelisted-domains' ]
		);

		add_settings_field(
			'wp_google_cookie_expiry',
			__( 'Auto logout after', 'login-with-google' ),
			[ $this, 'cookie_expiry_field' ],
			'login-with-google',
			'wp_google_login_section',
			[
				'label_for' => 'cookie-expiry',
				'class'     => 'cookie-expiry-row',
			]
		);
	}

	/**
	 * Render cookie expiry field.
	 *
	 * @return void
	 */
	public function cookie_expiry_field(): void {
		$days            = 0;
		$remaining_hours = 0;
		$hours           = ! is_numeric( $this->cookie_expiry ) ? '' : (int) $this->cookie_expiry;

		if ( ! empty( $hours ) ) {
			$days            = $hours / 24;
			$days            = (int) floor( $days );
			$remaining_hours = (int) $hours % 24;
		}


		$warning_classes = 'warning';
		if ( $days < 14 || ( 14 === $days && 0 === $remaining_hours ) ) {
			$warning_classes .= ' hidden';
		}

		$human_readable_cookie_expiry_classes = 'human-readable-cookie-expiry';

		if ( empty( $hours ) ) {
			$human_readable_cookie_expiry_classes .= ' hidden';
		}

		?>
		<div class='cookie_expiry_settings_wrapper'>
			<input type='number' inputmode='numeric' name='wp_google_login_settings[cookie_expiry]' id='cookie-expiry' value='<?php echo esc_attr( $hours ); ?>' autocomplete='off' />

			<p class='<?php echo esc_attr( $human_readable_cookie_expiry_classes ) ?>'>
				<?php echo esc_html( sprintf( __( 'User will auto logout after', 'login-with-google' ), $days, $remaining_hours ) ); ?>

				<span class="days">
					<?php
					if ( ! empty( $days ) ) {
						echo esc_html( sprintf( _n( ' %d day', ' %d days', $days, 'login-with-google' ), number_format_i18n( $days ) ) );
					}
					?>
				</span>

				<span class="hours">
					<?php
					if ( ! empty( $remaining_hours ) ) {
						echo esc_html( sprintf( _n( ' %d hour', ' %d hours', $remaining_hours, 'login-with-google' ), number_format_i18n( $remaining_hours) ) );
					}
					?>
				</span>
				<?php
				?>
			</p>
		</div>
		<p class='description'>
			<?php echo esc_html( __( 'Time in hours after which user will be logged out automatically.', 'login-with-google' ) ); ?>
			<br>
			<?php echo esc_html( __( 'If you want your user to get logged out after "2 days" just add "48"', 'login-with-google' ) ); ?>
		</p>

		<p class='<?php echo esc_attr( $warning_classes ); ?>'>
			<?php
			echo esc_html( __( 'Warning: Cookie expiry is set to more than 14 days. This is not recommended.', 'login-with-google' ) );
			?>
		</p>

		<?php
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
	 * Toggle One Tap Login functionality.
	 *
	 * @return void
	 */
	public function one_tap_login(): void {
		?>
		<label style='display:block;margin-top:6px;'><input <?php $this->disabled( 'one_tap_login' ); ?>
					type='checkbox'
					name='wp_google_login_settings[one_tap_login]'
					id="one-tap-login" <?php echo esc_attr( checked( $this->one_tap_login ) ); ?>
					value='1'>
			<?php esc_html_e( 'One Tap Login', 'login-with-google' ); ?>
		</label>
		<?php
	}

	/**
	 * One tap login screens.
	 *
	 * It can be enabled only for wp-login.php OR sitewide.
	 *
	 * @return void
	 */
	public function one_tap_login_screens(): void {
		$default = $this->one_tap_login_screen ?? '';
		?>
		<label style='display:block;margin-top:6px;'><input <?php $this->disabled( 'one_tap_login' ); ?>
					type='radio'
					name='wp_google_login_settings[one_tap_login_screen]'
					id="one-tap-login-screen-login" <?php echo esc_attr( checked( $this->one_tap_login_screen, $default ) ); ?>
					value='login'>
			<?php esc_html_e( 'Enable One Tap Login Only on Login Screen', 'login-with-google' ); ?>
		</label>
		<label style='display:block;margin-top:6px;'><input <?php $this->disabled( 'one_tap_login' ); ?>
					type='radio'
					name='wp_google_login_settings[one_tap_login_screen]'
					id="one-tap-login-screen-sitewide" <?php echo esc_attr( checked( $this->one_tap_login_screen, 'sitewide' ) ); ?>
					value='sitewide'>
			<?php esc_html_e( 'Enable One Tap Login Site-wide', 'login-with-google' ); ?>
		</label>
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
