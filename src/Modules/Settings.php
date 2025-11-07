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
 * Class Settings
 *
 * @package RtCamp\GoogleLogin\Modules
 */
class Settings implements ModuleInterface {

	/**
	 * Options array.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * List of getters for settings.
	 *
	 * @var array
	 */
	private $getters = [
		'WP_GOOGLE_LOGIN_CLIENT_ID'         => 'client_id',
		'WP_GOOGLE_LOGIN_SECRET'            => 'client_secret',
		'WP_GOOGLE_LOGIN_USER_REGISTRATION' => 'registration_enabled',
		'WP_GOOGLE_LOGIN_WHITELIST_DOMAINS' => 'whitelisted_domains',
		'WP_GOOGLE_ONE_TAP_LOGIN'           => 'one_tap_login',
		'WP_GOOGLE_ONE_TAP_LOGIN_SCREEN'    => 'one_tap_login_screen',
	];

	/**
	 * Magic getter to access settings as properties.
	 *
	 * @param string $name Name of the property.
	 *
	 * @return mixed
	 */
	public function __get( string $name ) {
		if ( in_array( $name, $this->getters, true ) ) {
			$constant_name = array_search( $name, $this->getters, true );
			return defined( $constant_name ) ? constant( $constant_name ) : ( $this->options[ $name ] ?? '' );
		}
		return null;
	}

	/**
	 * Name of the module.
	 *
	 * @return string
	 */
	public function name(): string {
		return 'settings';
	}

	/**
	 * Initializes the settings module.
	 */
	public function init(): void {
		$this->options = get_option( 'wp_google_login_settings', [] );

		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'settings_page' ] );

		if ( is_multisite() ) {
			add_action( 'network_admin_menu', [ $this, 'register_network_settings_page' ] );
			add_action( 'network_admin_menu', [ $this, 'register_network_settings' ] );
			add_action( 'network_admin_edit_wp_google_login_network_settings', [ $this, 'save_network_settings' ] );
		}
	}

	/**
	 * Retrieves the Google OAuth Client ID, always from the network settings in multisite.
	 *
	 * @return string
	 */
	public function get_client_id() {
		if ( is_multisite() ) {
			$network_settings = get_site_option( 'wp_google_login_network_settings', [] );
			return $network_settings['client_id'] ?? '';
		}
		return $this->client_id;
	}

	/**
	 * Retrieves the Google OAuth Client Secret, always from the network settings in multisite.
	 *
	 * @return string
	 */
	public function get_client_secret() {
		if ( is_multisite() ) {
			$network_settings = get_site_option( 'wp_google_login_network_settings', [] );
			return $network_settings['client_secret'] ?? '';
		}
		return $this->client_secret;
	}

	/**
	 * Registers the network settings page for multisite installations.
	 *
	 * @return void
	 */
	public function register_network_settings_page(): void {
		add_submenu_page(
			'settings.php',
			__( 'Login with Google Network Settings', 'login-with-google' ),
			__( 'Login with Google', 'login-with-google' ),
			'manage_network_options',
			'login-with-google-network',
			[ $this, 'output_network_settings' ]
		);
	}

	/**
	 * Outputs the network settings page HTML.
	 */
	public function output_network_settings(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['updated'] ) ) {
			echo '<div id="message" class="updated notice is-dismissible"><p>' . esc_html__( 'Settings saved.', 'login-with-google' ) . '</p></div>';
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Login with Google Network Settings', 'login-with-google' ); ?></h1>
			<form method="post" action="edit.php?action=wp_google_login_network_settings">
				<?php
				wp_nonce_field( 'wp_google_login_network-options' );
				settings_fields( 'wp_google_login_network' );
				do_settings_sections( 'login-with-google-network' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registers the network settings for multisite installations.
	 *
	 * @return void
	 */
	public function register_network_settings(): void {
		if ( ! is_multisite() || ! is_network_admin() ) {
			return;
		}

		register_setting( 'wp_google_login_network', 'wp_google_login_network_settings' );

		add_settings_section(
			'wp_google_login_network_section',
			__( 'Log in with Google Network Settings', 'login-with-google' ),
			function () {},
			'login-with-google-network'
		);

		add_settings_field(
			'wp_google_login_client_id',
			__( 'Client ID', 'login-with-google' ),
			[ $this, 'client_id_field' ],
			'login-with-google-network',
			'wp_google_login_network_section',
			[
				'context'   => 'network',
				'label_for' => 'client-id',
			]
		);

		add_settings_field(
			'wp_google_login_client_secret',
			__( 'Client Secret', 'login-with-google' ),
			[ $this, 'client_secret_field' ],
			'login-with-google-network',
			'wp_google_login_network_section',
			[
				'context'   => 'network',
				'label_for' => 'client-secret',
			]
		);

		add_settings_field(
			'wp_google_login_apply_globally',
			__( 'Apply settings to all sites in the network', 'login-with-google' ),
			[ $this, 'apply_globally_field' ],
			'login-with-google-network',
			'wp_google_login_network_section',
			[
				'context'   => 'network',
				'label_for' => 'apply-globally',
			]
		);

		add_settings_field(
			'wp_google_allow_registration',
			__( 'Create New User', 'login-with-google' ),
			[ $this, 'user_registration' ],
			'login-with-google-network',
			'wp_google_login_network_section',
			[
				'context'   => 'network',
				'label_for' => 'user-registration',
			]
		);

		add_settings_field(
			'wp_google_one_tap_login',
			__( 'Enable One Tap Login', 'login-with-google' ),
			[ $this, 'one_tap_login' ],
			'login-with-google-network',
			'wp_google_login_network_section',
			[
				'context'   => 'network',
				'label_for' => 'one-tap-login',
			]
		);

		add_settings_field(
			'wp_google_one_tap_login_screen',
			__( 'One Tap Login Locations', 'login-with-google' ),
			[ $this, 'one_tap_login_screens' ],
			'login-with-google-network',
			'wp_google_login_network_section',
			[
				'context'   => 'network',
				'label_for' => 'one-tap-login-screen',
			]
		);

		add_settings_field(
			'wp_google_whitelisted_domain',
			__( 'Whitelisted Domains', 'login-with-google' ),
			[ $this, 'whitelisted_domains' ],
			'login-with-google-network',
			'wp_google_login_network_section',
			[
				'context'   => 'network',
				'label_for' => 'whitelisted-domains',
			]
		);
	}

	/**
	 * Saves the network settings for multisite installations.
	 *
	 * @return void
	 */
	public function save_network_settings() {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage network options.', 'login-with-google' ) );
		}

		check_admin_referer( 'wp_google_login_network-options' );

		$defaults = [
			'apply_globally'       => 0,
			'one_tap_login'        => 0,
			'registration_enabled' => 0,
			'one_tap_login_screen' => 'login',
			'whitelisted_domains'  => '',
			'client_id'            => '',
			'client_secret'        => '',
		];

		$settings = $_POST['wp_google_login_network_settings'] ?? []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// Sanitizing the settings array below.

		// Sanitize each field.
		$sanitized_settings = [
			'apply_globally'       => isset( $settings['apply_globally'] ) ? 1 : 0,
			'one_tap_login'        => isset( $settings['one_tap_login'] ) ? 1 : 0,
			'registration_enabled' => isset( $settings['registration_enabled'] ) ? 1 : 0,
			'one_tap_login_screen' => isset( $settings['one_tap_login_screen'] ) ? sanitize_text_field( $settings['one_tap_login_screen'] ) : 'login',
			'whitelisted_domains'  => isset( $settings['whitelisted_domains'] ) ? sanitize_text_field( $settings['whitelisted_domains'] ) : '',
			'client_id'            => isset( $settings['client_id'] ) ? sanitize_text_field( $settings['client_id'] ) : '',
			'client_secret'        => isset( $settings['client_secret'] ) ? sanitize_text_field( $settings['client_secret'] ) : '',
		];

		$settings = array_merge( $defaults, $sanitized_settings );

		update_site_option( 'wp_google_login_network_settings', $settings );

		wp_cache_delete( 'wp_google_login_network_settings', 'site-options' );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'login-with-google-network',
					'updated' => 'true',
				],
				network_admin_url( 'settings.php' ) 
			) 
		);
		exit;
	}

	/**
	 * Registers the settings for the single site installations.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		// Only allow registration in network admin for network settings.
		if ( is_multisite() && is_network_admin() ) {
			return;
		}

		register_setting( 'wp_google_login', 'wp_google_login_settings' );

		add_settings_section(
			'wp_google_login_section',
			__( 'Log in with Google Settings', 'login-with-google' ),
			function () {},
			'login-with-google'
		);

		// Only show client_id/client_secret on single site installs.
		if ( ! is_multisite() ) {
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
		}

		// For multisite subsites, check if apply_globally is enabled.
		$readonly         = false;
		$network_settings = [];
		if ( is_multisite() && ! is_network_admin() ) {
			$network_settings = get_site_option( 'wp_google_login_network_settings', [] );
			$readonly         = ! empty( $network_settings['apply_globally'] );
		}

		add_settings_field(
			'wp_google_allow_registration',
			__( 'Create New User', 'login-with-google' ),
			[ $this, 'user_registration' ],
			'login-with-google',
			'wp_google_login_section',
			[
				'label_for'        => 'user-registration',
				'readonly'         => $readonly,
				'network_settings' => $network_settings,
			]
		);

		add_settings_field(
			'wp_google_one_tap_login',
			__( 'Enable One Tap Login', 'login-with-google' ),
			[ $this, 'one_tap_login' ],
			'login-with-google',
			'wp_google_login_section',
			[
				'label_for'        => 'one-tap-login',
				'readonly'         => $readonly,
				'network_settings' => $network_settings,
			]
		);

		add_settings_field(
			'wp_google_one_tap_login_screen',
			__( 'One Tap Login Locations', 'login-with-google' ),
			[ $this, 'one_tap_login_screens' ],
			'login-with-google',
			'wp_google_login_section',
			[
				'label_for'        => 'one-tap-login-screen',
				'readonly'         => $readonly,
				'network_settings' => $network_settings,
			]
		);

		add_settings_field(
			'wp_google_whitelisted_domain',
			__( 'Whitelisted Domains', 'login-with-google' ),
			[ $this, 'whitelisted_domains' ],
			'login-with-google',
			'wp_google_login_section',
			[
				'label_for'        => 'whitelisted-domains',
				'readonly'         => $readonly,
				'network_settings' => $network_settings,
			]
		);
	}

	/**
	 * Renders the input field for the Client ID setting.
	 *
	 * @param array $args Additional arguments for the field.
	 *
	 * @return void
	 */
	public function client_id_field( $args = [] ): void {
		$is_network = isset( $args['context'] ) && 'network' === $args['context'];
		$value      = $is_network
			? ( get_site_option( 'wp_google_login_network_settings' )['client_id'] ?? '' )
			: $this->get_client_id(); // Use the robust getter!
		$name       = $is_network ? 'wp_google_login_network_settings[client_id]' : 'wp_google_login_settings[client_id]';
		?>

		<input type='text' name='<?php echo esc_attr( $name ); ?>' id="client-id" value='<?php echo esc_attr( $value ); ?>' autocomplete="off" />
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
	 * Renders the input field for the Client Secret setting.
	 *
	 * @param array $args Additional arguments for the field.
	 *
	 * @return void
	 */
	public function client_secret_field( $args = [] ): void {
		$is_network = isset( $args['context'] ) && 'network' === $args['context'];
		$value      = $is_network
			? ( get_site_option( 'wp_google_login_network_settings' )['client_secret'] ?? '' )
			: $this->get_client_secret();
		$name       = $is_network ? 'wp_google_login_network_settings[client_secret]' : 'wp_google_login_settings[client_secret]';
		?>
		<input type='password' name='<?php echo esc_attr( $name ); ?>' id="client-secret" value='<?php echo esc_attr( $value ); ?>' autocomplete="off" />
		<?php
	}

	/**
	 * Renders the input field for the "Apply Globally" setting.
	 *
	 * @param array $args Additional arguments for the field.
	 *
	 * @return void
	 */
	public function apply_globally_field( $args = [] ): void {
		$network_options = get_site_option( 'wp_google_login_network_settings', [] );
		?>
		<input type="checkbox" name="wp_google_login_network_settings[apply_globally]" id="apply-globally" value="1" <?php checked( ! empty( $network_options['apply_globally'] ) ); ?> />
		<?php esc_html_e( 'Apply all settings to all sites in the network', 'login-with-google' ); ?>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				function toggleGlobalRows() {
					var applyGloballyEnabled = $("#apply-globally").is(":checked");
					var selectors = [
						"#one-tap-login",
						"#one-tap-login-screen",
						"#user-registration",
						"#whitelisted-domains"
					];
					selectors.forEach(function(sel){
						var $row = $(sel).closest("tr");
						if(applyGloballyEnabled) {
							$row.show();
						} else {
							$row.hide();
						}
					});
				}
				$("#apply-globally").on('change', toggleGlobalRows);
				toggleGlobalRows();
			});
		</script>
		<?php
	}

	/**
	 * Renders the input field for the User Registration setting.
	 *
	 * @param array $args Additional arguments for the field.
	 *
	 * @return void
	 */
	public function user_registration( $args = [] ): void {
		$is_network       = isset( $args['context'] ) && 'network' === $args['context'];
		$readonly         = ! empty( $args['readonly'] );
		$network_settings = $args['network_settings'] ?? [];

		if ( $is_network ) {
			$checked = ! empty( get_site_option( 'wp_google_login_network_settings', [] )['registration_enabled'] );
			?>
			<label style='display:block;margin-top:6px;'>
				<input type='checkbox'
					name='wp_google_login_network_settings[registration_enabled]'
					id="user-registration" <?php checked( $checked ); ?>
					value='1'>
				<?php esc_html_e( 'Create a new user account if it does not exist already', 'login-with-google' ); ?>
			</label>
			<p class="description">
				<?php
				echo wp_kses_post(
					sprintf(
						// translators: %s is replaced with URL of membership settings page.
						__( 'If this setting is checked, a new user will be created even if <a target="_blank" href="%1s">membership setting</a> is off.', 'login-with-google' ),
						'network/settings.php'
					)
				);
				?>
			</p>
			<?php
			return;
		}

		if ( $readonly ) {
			$checked = ! empty( $network_settings['registration_enabled'] );
			?>
			<input type="checkbox" disabled <?php checked( $checked ); ?> />
			<span><?php esc_html_e( 'Managed globally by network admin.', 'login-with-google' ); ?></span>
			<?php
			return;
		}
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
					// translators: %s is replaced with URL of membership settings page.
					__( 'If this setting is checked, a new user will be created even if <a target="_blank" href="%1s">membership setting</a> is off.', 'login-with-google' ),
					is_multisite() ? 'network/settings.php' : 'options-general.php'
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Renders the input field for the One Tap Login setting.
	 *
	 * @param array $args Additional arguments for the field.
	 *
	 * @return void
	 */
	public function one_tap_login( $args = [] ): void {
		$is_network       = isset( $args['context'] ) && 'network' === $args['context'];
		$readonly         = ! empty( $args['readonly'] );
		$network_settings = $args['network_settings'] ?? [];

		if ( $is_network ) {
			$checked = ! empty( get_site_option( 'wp_google_login_network_settings', [] )['one_tap_login'] );
			?>
			<label style='display:block;margin-top:6px;'><input
					type='checkbox'
					name='wp_google_login_network_settings[one_tap_login]'
					id="one-tap-login" <?php checked( $checked ); ?>
					value='1'>
				<?php esc_html_e( 'One Tap Login', 'login-with-google' ); ?>
			</label>
			<?php
			return;
		}

		if ( $readonly ) {
			$checked = ! empty( $network_settings['one_tap_login'] );
			?>
			<input type="checkbox" disabled <?php checked( $checked ); ?> />
			<span><?php esc_html_e( 'Managed globally by network admin.', 'login-with-google' ); ?></span>
			<?php
			return;
		}

		$checked = $this->one_tap_login;
		?>
		<label style='display:block;margin-top:6px;'><input <?php $this->disabled( 'one_tap_login' ); ?>
					type='checkbox'
					name='wp_google_login_settings[one_tap_login]'
					id="one-tap-login" <?php echo esc_attr( checked( $checked ) ); ?>
					value='1'>
			<?php esc_html_e( 'One Tap Login', 'login-with-google' ); ?>
		</label>
		<?php
	}

	/**
	 * Renders the input field for the One Tap Login Screens setting.
	 *
	 * @param array $args Additional arguments for the field.
	 *
	 * @return void
	 */
	public function one_tap_login_screens( $args = [] ): void {
		$is_network       = isset( $args['context'] ) && 'network' === $args['context'];
		$readonly         = ! empty( $args['readonly'] );
		$network_settings = $args['network_settings'] ?? [];

		if ( $is_network ) {
			$settings = get_site_option( 'wp_google_login_network_settings', [] );
			$value    = $settings['one_tap_login_screen'] ?? '';
			$name     = 'wp_google_login_network_settings[one_tap_login_screen]';
		} else {
			$value = $this->one_tap_login_screen ?? '';
			$name  = 'wp_google_login_settings[one_tap_login_screen]';
		}

		?>
		<div id="one-tap-login-locations-container"
		<?php
		if ( $readonly ) {
			echo ' style="pointer-events:none;opacity:0.7;"';}
		?>
		>
			<label for="one-tap-login-screen" style='display:block;margin-top:6px;'>
				<input type='radio'
					name='<?php echo esc_attr( $name ); ?>'
					id="one-tap-login-screen"
					<?php checked( $value, 'login' ); ?>
					value='login'
					<?php
					if ( $readonly ) {
						echo 'disabled';}
					?>
					>
				<?php esc_html_e( 'Enable One Tap Login Only on Login Screen', 'login-with-google' ); ?>
			</label>
			<label for="one-tap-login-screen-sitewide" style='display:block;margin-top:6px;'>
				<input type='radio'
					name='<?php echo esc_attr( $name ); ?>'
					id="one-tap-login-screen-sitewide"
					<?php checked( $value, 'sitewide' ); ?>
					value='sitewide'
					<?php
					if ( $readonly ) {
						echo 'disabled';}
					?>
					>
				<?php esc_html_e( 'Enable One Tap Login Site-wide', 'login-with-google' ); ?>
			</label>
			<?php if ( $readonly ) : ?>
				<span><?php esc_html_e( 'Managed globally by network admin.', 'login-with-google' ); ?></span>
			<?php endif; ?>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				function toggleOneTapLocationsRow() {
					// Find the parent <tr> of the container and hide/show it
					var $row = $("#one-tap-login-locations-container").closest("tr");
					var applyGlobally = $("#apply-globally").length ? $("#apply-globally").is(":checked") : true;
					var oneTapEnabled = $("#one-tap-login").is(":checked");
					if ((applyGlobally && oneTapEnabled) || $("#one-tap-login-locations-container").css('pointer-events') === 'none') {
						$row.show();
					} else {
						$row.hide();
					}
				}
				$("#apply-globally, #one-tap-login").on('change', toggleOneTapLocationsRow);
				toggleOneTapLocationsRow();
			});
		</script>
		<?php
	}

	/**
	 * Renders the input field for the Whitelisted Domains setting.
	 *
	 * @param array $args Additional arguments for the field.
	 *
	 * @return void
	 */
	public function whitelisted_domains( $args = [] ): void {
		$is_network       = isset( $args['context'] ) && 'network' === $args['context'];
		$readonly         = ! empty( $args['readonly'] );
		$network_settings = $args['network_settings'] ?? [];
		$value            = $is_network
			? ( get_site_option( 'wp_google_login_network_settings', [] )['whitelisted_domains'] ?? '' )
			: ( $readonly ? ( $network_settings['whitelisted_domains'] ?? '' ) : $this->whitelisted_domains );
		$name             = $is_network ? 'wp_google_login_network_settings[whitelisted_domains]' : 'wp_google_login_settings[whitelisted_domains]';

		if ( $readonly ) {
			?>
			<input type='text' disabled value='<?php echo esc_attr( $value ); ?>' autocomplete="off" />
			<span><?php esc_html_e( 'Managed globally by network admin.', 'login-with-google' ); ?></span>
			<p class="description">
				<?php echo esc_html( __( 'Add each domain comma separated', 'login-with-google' ) ); ?>
			</p>
			<?php
			return;
		}
		?>
		<input type='text'
			name='<?php echo esc_attr( $name ); ?>'
			id="whitelisted-domains"
			value='<?php echo esc_attr( $value ); ?>'
			autocomplete="off" />
		<p class="description">
			<?php esc_html_e( 'Add each domain comma separated', 'login-with-google' ); ?>
		</p>
		<?php
	}

	/**
	 * Adds the settings page to the WordPress admin menu.
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
	 * Outputs the settings page HTML.
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
	 * Outputs 'disabled' attribute if the setting is defined as a constant.
	 *
	 * @param string $id The setting identifier.
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