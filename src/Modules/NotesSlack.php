<?php
/**
 * Optional Slack direct messages for native WordPress Notes.
 *
 * @package RtCamp\GoogleLogin
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Modules;

use RtCamp\GoogleLogin\Interfaces\Module as ModuleInterface;
use RtCamp\GoogleLogin\Utils\SlackClient;
use WP_Comment;
use WP_Error;
use WP_User;
use function RtCamp\GoogleLogin\plugin;

/**
 * Notes notification module. It does not depend on how a user signed in.
 */
class NotesSlack implements ModuleInterface {
	private const OPTION        = 'wp_google_login_notes_slack';
	private const CRON_HOOK     = 'rtcamp_google_notes_slack_dispatch';
	private const USER_OVERRIDE = '_rtcamp_google_slack_user_id';
	private const USER_MATCH    = '_rtcamp_google_slack_match';

	/**
	 * Saved connection and notification settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Return the module identifier.
	 *
	 * @return string
	 */
	public function name(): string {
		return 'notes_slack';
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->settings = get_option( self::OPTION, [] );
		add_action( 'wp_insert_comment', [ $this, 'queue_note' ], 10, 2 );
		add_action( self::CRON_HOOK, [ $this, 'dispatch_note' ], 10, 2 );
		add_action( 'admin_post_rtcamp_google_notes_slack_save', [ $this, 'save_settings' ] );
		add_action( 'admin_post_rtcamp_google_notes_slack_disconnect', [ $this, 'disconnect' ] );
		add_action( 'admin_post_rtcamp_google_notes_slack_test', [ $this, 'send_test' ] );
		add_action( 'admin_post_rtcamp_google_notes_slack_match', [ $this, 'match_people' ] );
		add_action( 'edit_user_profile', [ $this, 'render_user_field' ] );
		add_action( 'show_user_profile', [ $this, 'render_user_field' ] );
		add_action( 'edit_user_profile_update', [ $this, 'save_user_field' ] );
		add_action( 'personal_options_update', [ $this, 'save_user_field' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_note_link' ] );
	}

	/**
	 * A token constant keeps the credential out of WordPress options.
	 *
	 * @return string
	 */
	private function token(): string {
		if ( defined( 'WP_GOOGLE_LOGIN_SLACK_BOT_TOKEN' ) ) {
			return (string) constant( 'WP_GOOGLE_LOGIN_SLACK_BOT_TOKEN' );
		}

		return (string) ( $this->settings['token'] ?? '' );
	}

	/**
	 * Whether Slack delivery is active.
	 *
	 * @return bool
	 */
	private function is_enabled(): bool {
		return ! empty( $this->settings['enabled'] ) && '' !== $this->token();
	}

	/**
	 * Schedule delivery outside the Note save request.
	 *
	 * @param int        $comment_id Comment ID.
	 * @param WP_Comment $comment Comment.
	 * @return void
	 */
	public function queue_note( int $comment_id, WP_Comment $comment ): void {
		if ( ! $this->is_enabled() || 'note' !== $comment->comment_type ) {
			return;
		}

		wp_schedule_single_event( time() + 5, self::CRON_HOOK, [ $comment_id, 0 ] );
	}

	/**
	 * Send one Note to its eligible recipients, retrying transient errors.
	 *
	 * @param int $comment_id Note ID.
	 * @param int $attempt Retry number.
	 * @return void
	 */
	public function dispatch_note( int $comment_id, int $attempt = 0 ): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$comment = get_comment( $comment_id );
		if ( ! $comment instanceof WP_Comment || 'note' !== $comment->comment_type || in_array( $comment->comment_approved, [ 'trash', 'spam' ], true ) ) {
			return;
		}

		$post = get_post( $comment->comment_post_ID );
		if ( ! $post ) {
			return;
		}

		$client = new SlackClient( $this->token() );
		$retry  = false;
		foreach ( $this->recipient_ids( $comment ) as $user_id ) {
			if ( get_comment_meta( $comment_id, '_rtcamp_slack_sent_' . $user_id, true ) ) {
				continue;
			}

			$user = get_userdata( $user_id );
			if ( ! $user ) {
				continue;
			}

			$slack_id = $this->slack_user_id( $user, $client );
			if ( is_wp_error( $slack_id ) ) {
				if ( 'slack_users_not_found' !== $slack_id->get_error_code() ) {
					$retry = true;
				}
				$this->record_error( $slack_id );
				continue;
			}

			$dm = $client->open_dm( $slack_id );
			if ( is_wp_error( $dm ) || empty( $dm['channel']['id'] ) ) {
				$retry = true;
				$this->record_error( is_wp_error( $dm ) ? $dm : new WP_Error( 'slack_no_dm' ) );
				continue;
			}

			$sent = $client->post_message( $dm['channel']['id'], $this->message( $comment ) );
			if ( is_wp_error( $sent ) ) {
				$retry = true;
				$this->record_error( $sent );
				continue;
			}

			add_comment_meta( $comment_id, '_rtcamp_slack_sent_' . $user_id, time(), true );
		}

		if ( $retry && $attempt < 2 ) {
			wp_schedule_single_event( time() + ( 60 * ( $attempt + 1 ) ), self::CRON_HOOK, [ $comment_id, $attempt + 1 ] );
		}
	}

	/**
	 * Follow the built-in Notes audience and include the original Note author on replies.
	 *
	 * @param WP_Comment $comment Note.
	 * @return int[]
	 */
	public function recipient_ids( WP_Comment $comment ): array {
		$post = get_post( $comment->comment_post_ID );
		if ( ! $post ) {
			return [];
		}

		$ids = [];
		if ( ! empty( $this->settings['notify_post_author'] ?? 1 ) ) {
			$ids[] = (int) $post->post_author;
		}
		if ( ! empty( $this->settings['notify_mentions'] ?? 1 ) && function_exists( 'wp_get_note_mentioned_user_ids' ) ) {
			$ids = array_merge( $ids, wp_get_note_mentioned_user_ids( $comment->comment_content ) );
		}
		if ( ! empty( $this->settings['notify_thread_author'] ?? 1 ) && $comment->comment_parent ) {
			$parent = get_comment( $this->root_note_id( $comment ) );
			if ( $parent instanceof WP_Comment && 'note' === $parent->comment_type ) {
				$ids[] = (int) $parent->user_id;
			}
		}

		/**
		 * Filter Note recipients before the permission check.
		 *
		 * @param int[]      $ids Recipient WordPress user IDs.
		 * @param WP_Comment $comment The new Note.
		 */
		$ids = apply_filters( 'rtcamp.google_notes_slack_recipient_ids', $ids, $comment );
		if ( ! is_array( $ids ) ) {
			return [];
		}

		$ids = array_unique( array_map( 'intval', $ids ) );
		return array_values(
			array_filter(
				$ids,
				static function ( int $id ) use ( $comment ): bool {
					return $id > 0 && $id !== (int) $comment->user_id && user_can( $id, 'edit_comment', $comment->comment_ID );
				}
			)
		);
	}

	/**
	 * Return the manual override or a verified email match for this workspace.
	 *
	 * @param WP_User     $user WordPress user.
	 * @param SlackClient $client Slack client.
	 * @return string|WP_Error
	 */
	private function slack_user_id( WP_User $user, SlackClient $client ) {
		$override = (string) get_user_meta( $user->ID, self::USER_OVERRIDE, true );
		if ( '' !== $override ) {
			return $override;
		}

		$match = get_user_meta( $user->ID, self::USER_MATCH, true );
		if ( is_array( $match ) && ( $match['email'] ?? '' ) === $user->user_email && ( $match['team_id'] ?? '' ) === ( $this->settings['team_id'] ?? '' ) && ! empty( $match['id'] ) ) {
			return $match['id'];
		}

		$found = $client->user_by_email( $user->user_email );
		if ( is_wp_error( $found ) ) {
			update_user_meta(
				$user->ID,
				self::USER_MATCH,
				[
					'email'   => $user->user_email,
					'team_id' => $this->settings['team_id'] ?? '',
					'error'   => $found->get_error_code(),
				]
			);
			return $found;
		}
		if ( empty( $found['user']['id'] ) ) {
			return new WP_Error( 'slack_users_not_found' );
		}

		$id = (string) $found['user']['id'];
		update_user_meta(
			$user->ID,
			self::USER_MATCH,
			[
				'id'      => $id,
				'email'   => $user->user_email,
				'team_id' => $this->settings['team_id'] ?? '',
			]
		);
		return $id;
	}

	/**
	 * Slack message. The editor link uses a plugin-owned Note query parameter.
	 *
	 * @param WP_Comment $comment Note.
	 * @return string
	 */
	public function message( WP_Comment $comment ): string {
		$post     = get_post( $comment->comment_post_ID );
		$root_id  = $this->root_note_id( $comment );
		$edit_url = add_query_arg( 'lwg_note', $root_id, admin_url( 'post.php?post=' . (int) $comment->comment_post_ID . '&action=edit' ) );
		$title    = $post ? get_the_title( $post ) : __( 'a post', 'login-with-google' );
		$author   = $comment->comment_author ? $comment->comment_author : __( 'Someone', 'login-with-google' );
		$body     = wp_specialchars_decode( wp_strip_all_tags( $comment->comment_content ), ENT_QUOTES );
		$body     = trim( $body );
		$body     = function_exists( 'mb_substr' ) ? mb_substr( $body, 0, 2800 ) : substr( $body, 0, 2800 );
		$verb     = $comment->comment_parent ? __( 'replied to a Note', 'login-with-google' ) : __( 'left a Note', 'login-with-google' );

		return sprintf(
			'*%1$s %2$s on %3$s*' . "\n" . '> %4$s' . "\n" . '%5$s',
			$this->slack_escape( $author ),
			$verb,
			$this->slack_escape( $title ),
			str_replace( "\n", "\n> ", $this->slack_escape( $body ) ),
			'<' . esc_url_raw( $edit_url ) . '|' . __( 'Open Note', 'login-with-google' ) . '>'
		);
	}

	/**
	 * Escape text for Slack's mrkdwn format.
	 *
	 * @param string $value Text.
	 * @return string
	 */
	private function slack_escape( string $value ): string {
		return str_replace( [ '&', '<', '>' ], [ '&amp;', '&lt;', '&gt;' ], $value );
	}

	/**
	 * Find the top-level Note for replies, including older nested replies.
	 *
	 * @param WP_Comment $comment Note or reply.
	 * @return int
	 */
	private function root_note_id( WP_Comment $comment ): int {
		$root = $comment;
		for ( $depth = 0; $depth < 10 && $root->comment_parent; $depth++ ) {
			$parent = get_comment( $root->comment_parent );
			if ( ! $parent instanceof WP_Comment || 'note' !== $parent->comment_type ) {
				break;
			}
			$root = $parent;
		}
		return (int) $root->comment_ID;
	}

	/**
	 * Keep only a sanitized error code, never the Note or bot token.
	 *
	 * @param WP_Error $error Error.
	 * @return void
	 */
	private function record_error( WP_Error $error ): void {
		update_option( 'wp_google_login_slack_last_error', sanitize_key( $error->get_error_code() ), false );
	}

	/**
	 * Only admins may change workspace settings.
	 *
	 * @param string $nonce_action Nonce action.
	 * @return void
	 */
	private function verify_admin( string $nonce_action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot manage Notes notifications.', 'login-with-google' ) );
		}
		check_admin_referer( $nonce_action );
	}

	/**
	 * Return to the Notes settings tab with a status code.
	 *
	 * @param string $status Status code.
	 * @param int    $people_page Current People page.
	 * @return void
	 */
	private function redirect( string $status, int $people_page = 1 ): void {
		$url = add_query_arg(
			[
				'page'            => 'login-with-google',
				'tab'             => 'notes-slack',
				'lwg_status'      => $status,
				'lwg_people_page' => max( 1, $people_page ),
			],
			admin_url( 'options-general.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Save connection and recipient choices.
	 *
	 * @return void
	 */
	public function save_settings(): void {
		$this->verify_admin( 'rtcamp_google_notes_slack_save' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verify_admin() checks the nonce; values are sanitized below.
		$input    = isset( $_POST['notes_slack'] ) && is_array( $_POST['notes_slack'] ) ? wp_unslash( $_POST['notes_slack'] ) : [];
		$token    = isset( $input['token'] ) ? sanitize_text_field( $input['token'] ) : '';
		$settings = $this->settings;

		if ( '' !== $token && ! defined( 'WP_GOOGLE_LOGIN_SLACK_BOT_TOKEN' ) ) {
			if ( 0 !== strpos( $token, 'xoxb-' ) ) {
				$this->redirect( 'invalid_token' );
			}
			$client = new SlackClient( $token );
			$auth   = $client->auth_test();
			if ( is_wp_error( $auth ) || empty( $auth['team_id'] ) ) {
				$this->redirect( 'invalid_token' );
			}
			$settings['token']     = $token;
			$settings['team_id']   = sanitize_text_field( $auth['team_id'] );
			$settings['team_name'] = sanitize_text_field( $auth['team'] ?? '' );
		}
		if ( defined( 'WP_GOOGLE_LOGIN_SLACK_BOT_TOKEN' ) && empty( $settings['team_id'] ) ) {
			$auth = ( new SlackClient( $this->token() ) )->auth_test();
			if ( is_wp_error( $auth ) || empty( $auth['team_id'] ) ) {
				$this->redirect( 'invalid_token' );
			}
			$settings['team_id']   = sanitize_text_field( $auth['team_id'] );
			$settings['team_name'] = sanitize_text_field( $auth['team'] ?? '' );
		}

		$settings['notify_post_author']   = empty( $input['notify_post_author'] ) ? 0 : 1;
		$settings['notify_mentions']      = empty( $input['notify_mentions'] ) ? 0 : 1;
		$settings['notify_thread_author'] = empty( $input['notify_thread_author'] ) ? 0 : 1;
		$settings['enabled']              = ! empty( $input['enabled'] ) && ( ! empty( $settings['token'] ) || '' !== $this->token() ) ? 1 : 0;

		update_option( self::OPTION, $settings, false );
		$this->settings = $settings;
		$this->redirect( 'saved' );
	}

	/**
	 * Disconnect from Slack. A token supplied via wp-config.php remains available.
	 *
	 * @return void
	 */
	public function disconnect(): void {
		$this->verify_admin( 'rtcamp_google_notes_slack_disconnect' );
		$settings = $this->settings;
		unset( $settings['token'], $settings['team_id'], $settings['team_name'] );
		$settings['enabled'] = 0;
		update_option( self::OPTION, $settings, false );
		$this->redirect( 'disconnected' );
	}

	/**
	 * Send a test DM to the current administrator.
	 *
	 * @return void
	 */
	public function send_test(): void {
		$this->verify_admin( 'rtcamp_google_notes_slack_test' );
		if ( '' === $this->token() ) {
			$this->redirect( 'no_connection' );
		}
		$client  = new SlackClient( $this->token() );
		$user_id = $this->slack_user_id( wp_get_current_user(), $client );
		if ( is_wp_error( $user_id ) ) {
			$this->record_error( $user_id );
			$this->redirect( 'test_failed' );
		}
		$dm = $client->open_dm( $user_id );
		if ( is_wp_error( $dm ) || empty( $dm['channel']['id'] ) ) {
			$this->record_error( is_wp_error( $dm ) ? $dm : new WP_Error( 'slack_no_dm' ) );
			$this->redirect( 'test_failed' );
		}
		$sent = $client->post_message( $dm['channel']['id'], __( 'WordPress Notes notifications are connected. This is a test message.', 'login-with-google' ) );
		if ( is_wp_error( $sent ) ) {
			$this->record_error( $sent );
		}
		$this->redirect( is_wp_error( $sent ) ? 'test_failed' : 'test_sent' );
	}

	/**
	 * Check one page of WordPress people to avoid Slack rate limits.
	 *
	 * @return void
	 */
	public function match_people(): void {
		$this->verify_admin( 'rtcamp_google_notes_slack_match' );
		if ( '' === $this->token() ) {
			$this->redirect( 'no_connection' );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verify_admin() checks the form nonce.
		$people_page = isset( $_POST['lwg_people_page'] ) ? max( 1, absint( $_POST['lwg_people_page'] ) ) : 1;
		$client      = new SlackClient( $this->token() );
		$users       = get_users(
			[
				'number'  => 20,
				'offset'  => ( $people_page - 1 ) * 20,
				'orderby' => 'display_name',
			]
		);
		foreach ( $users as $user ) {
			$this->slack_user_id( $user, $client );
		}
		$this->redirect( 'matched', $people_page );
	}

	/**
	 * Admin-only manual mapping for email aliases.
	 *
	 * @param WP_User $user Profile owner.
	 * @return void
	 */
	public function render_user_field( WP_User $user ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$id = (string) get_user_meta( $user->ID, self::USER_OVERRIDE, true );
		?>
		<h2><?php esc_html_e( 'WordPress Notes in Slack', 'login-with-google' ); ?></h2>
		<?php wp_nonce_field( 'rtcamp_google_slack_user_' . $user->ID, 'rtcamp_google_slack_nonce' ); ?>
		<table class="form-table"><tr>
			<th><label for="rtcamp-google-slack-user-id"><?php esc_html_e( 'Slack user ID override', 'login-with-google' ); ?></label></th>
			<td><input id="rtcamp-google-slack-user-id" name="rtcamp_google_slack_user_id" type="text" class="regular-text" value="<?php echo esc_attr( $id ); ?>" placeholder="U0123456789" />
			<p class="description"><?php esc_html_e( 'Leave blank to match by WordPress email.', 'login-with-google' ); ?></p></td>
		</tr></table>
		<?php
	}

	/**
	 * Save a profile's optional Slack user ID override.
	 *
	 * @param int $user_id Profile owner ID.
	 * @return void
	 */
	public function save_user_field( int $user_id ): void {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_user', $user_id ) || ! isset( $_POST['rtcamp_google_slack_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rtcamp_google_slack_nonce'] ) ), 'rtcamp_google_slack_user_' . $user_id ) ) {
			return;
		}
		$id = isset( $_POST['rtcamp_google_slack_user_id'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['rtcamp_google_slack_user_id'] ) ) ) : '';
		if ( '' === $id ) {
			delete_user_meta( $user_id, self::USER_OVERRIDE );
		} elseif ( preg_match( '/^[UW][A-Z0-9]{8,}$/', $id ) ) {
			update_user_meta( $user_id, self::USER_OVERRIDE, $id );
		}
	}

	/**
	 * Show Slack setup and recipient controls in the plugin's Settings tab.
	 *
	 * @return void
	 */
	public function render_settings(): void {
		$connected = '' !== $this->token();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This only selects a read-only People page.
		$people_page = isset( $_GET['lwg_people_page'] ) ? max( 1, absint( $_GET['lwg_people_page'] ) ) : 1;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Status only controls a read-only admin notice.
		$status   = isset( $_GET['lwg_status'] ) ? sanitize_key( wp_unslash( $_GET['lwg_status'] ) ) : '';
		$messages = [
			'saved'         => __( 'Notes notification settings saved.', 'login-with-google' ),
			'disconnected'  => __( 'Slack disconnected.', 'login-with-google' ),
			'invalid_token' => __( 'Slack could not verify that bot token.', 'login-with-google' ),
			'test_sent'     => __( 'Test DM sent.', 'login-with-google' ),
			'test_failed'   => __( 'Test DM failed. Check the user mapping and bot permissions.', 'login-with-google' ),
			'no_connection' => __( 'Connect Slack first.', 'login-with-google' ),
			'matched'       => __( 'Checked the WordPress users shown below.', 'login-with-google' ),
		];
		if ( isset( $messages[ $status ] ) ) {
			printf( '<div class="notice notice-info"><p>%s</p></div>', esc_html( $messages[ $status ] ) );
		}
		?>
		<h2><?php esc_html_e( 'WordPress Notes → Slack', 'login-with-google' ); ?></h2>
		<p><?php esc_html_e( 'Send private Slack DMs about new editor Notes and replies. Google sign-in settings do not change.', 'login-with-google' ); ?></p>
		<?php if ( version_compare( get_bloginfo( 'version' ), '6.9', '<' ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'WordPress 6.9 or newer is needed for Notes.', 'login-with-google' ); ?></p></div>
		<?php endif; ?>
		<p><strong><?php esc_html_e( 'Workspace:', 'login-with-google' ); ?></strong> <?php echo $connected ? esc_html( $this->settings['team_name'] ?? __( 'Connected', 'login-with-google' ) ) : esc_html__( 'Not connected', 'login-with-google' ); ?></p>
		<p><?php esc_html_e( 'Create a Slack app in your workspace with bot scopes chat:write, im:write, users:read, and users:read.email. Install it, then paste its Bot User OAuth Token below. The bot sends DMs directly from this site to Slack.', 'login-with-google' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="rtcamp_google_notes_slack_save" />
			<?php wp_nonce_field( 'rtcamp_google_notes_slack_save' ); ?>
			<table class="form-table">
				<tr><th><label for="lwg-slack-token"><?php esc_html_e( 'Slack bot token', 'login-with-google' ); ?></label></th><td>
					<input id="lwg-slack-token" name="notes_slack[token]" type="password" class="regular-text" value="" autocomplete="new-password" <?php disabled( defined( 'WP_GOOGLE_LOGIN_SLACK_BOT_TOKEN' ) ); ?> placeholder="<?php echo esc_attr( $connected ? __( 'Connected — leave blank to keep', 'login-with-google' ) : 'xoxb-…' ); ?>" />
					<p class="description"><?php esc_html_e( 'For managed hosting, set WP_GOOGLE_LOGIN_SLACK_BOT_TOKEN in wp-config.php instead.', 'login-with-google' ); ?></p>
				</td></tr>
				<tr><th><?php esc_html_e( 'Recipients', 'login-with-google' ); ?></th><td>
					<label><input type="checkbox" name="notes_slack[notify_post_author]" value="1" <?php checked( $this->settings['notify_post_author'] ?? 1 ); ?> /> <?php esc_html_e( 'Post author', 'login-with-google' ); ?></label><br />
					<label><input type="checkbox" name="notes_slack[notify_mentions]" value="1" <?php checked( $this->settings['notify_mentions'] ?? 1 ); ?> /> <?php esc_html_e( 'People @mentioned in the Note (WordPress 7.1+)', 'login-with-google' ); ?></label><br />
					<label><input type="checkbox" name="notes_slack[notify_thread_author]" value="1" <?php checked( $this->settings['notify_thread_author'] ?? 1 ); ?> /> <?php esc_html_e( 'Original Note author when someone replies', 'login-with-google' ); ?></label>
				</td></tr>
				<tr><th><?php esc_html_e( 'Notifications', 'login-with-google' ); ?></th><td><label><input type="checkbox" name="notes_slack[enabled]" value="1" <?php checked( $this->settings['enabled'] ?? 0 ); ?> /> <?php esc_html_e( 'Send Slack DMs for new Notes', 'login-with-google' ); ?></label></td></tr>
			</table>
			<?php submit_button( __( 'Save Notes settings', 'login-with-google' ) ); ?>
		</form>
		<?php if ( $connected ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:1em;"><input type="hidden" name="action" value="rtcamp_google_notes_slack_test" /><?php wp_nonce_field( 'rtcamp_google_notes_slack_test' ); ?><?php submit_button( __( 'Send me a test DM', 'login-with-google' ), 'secondary', 'submit', false ); ?></form>
			<?php if ( ! defined( 'WP_GOOGLE_LOGIN_SLACK_BOT_TOKEN' ) ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;"><input type="hidden" name="action" value="rtcamp_google_notes_slack_disconnect" /><?php wp_nonce_field( 'rtcamp_google_notes_slack_disconnect' ); ?><?php submit_button( __( 'Disconnect Slack', 'login-with-google' ), 'secondary', 'submit', false ); ?></form>
			<?php endif; ?>
		<?php endif; ?>
		<h2><?php esc_html_e( 'People', 'login-with-google' ); ?></h2>
		<p><?php esc_html_e( 'WordPress users are matched to Slack by email when first notified. Edit a user profile to set a Slack user ID for an email alias.', 'login-with-google' ); ?></p>
		<?php $this->render_people( $people_page ); ?>
		<?php if ( $connected ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="rtcamp_google_notes_slack_match" /><input type="hidden" name="lwg_people_page" value="<?php echo esc_attr( $people_page ); ?>" /><?php wp_nonce_field( 'rtcamp_google_notes_slack_match' ); ?><?php submit_button( __( 'Check people on this page', 'login-with-google' ), 'secondary' ); ?></form>
		<?php endif; ?>
		<?php $last_error = get_option( 'wp_google_login_slack_last_error', '' ); ?>
		<?php
		if ( $last_error ) :
			?>
			<p>
			<?php
				/* translators: %s: Sanitized Slack error code. */
				printf( esc_html__( 'Last delivery error: %s', 'login-with-google' ), '<code>' . esc_html( $last_error ) . '</code>' );
			?>
			</p><?php endif; ?>
		<p><?php esc_html_e( 'Only new Notes and replies are sent. WordPress email notifications remain controlled under Settings → Discussion.', 'login-with-google' ); ?></p>
		<?php
	}

	/**
	 * Render current mapping status without making Slack calls on page load.
	 *
	 * @param int $page People page number.
	 * @return void
	 */
	private function render_people( int $page ): void {
		$users    = get_users(
			[
				'number'  => 21,
				'offset'  => ( $page - 1 ) * 20,
				'orderby' => 'display_name',
			]
		);
		$has_next = count( $users ) > 20;
		$users    = array_slice( $users, 0, 20 );
		?>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'WordPress user', 'login-with-google' ); ?></th><th><?php esc_html_e( 'Email', 'login-with-google' ); ?></th><th><?php esc_html_e( 'Slack match', 'login-with-google' ); ?></th></tr></thead><tbody>
		<?php foreach ( $users as $user ) : ?>
			<?php
			$override           = get_user_meta( $user->ID, self::USER_OVERRIDE, true );
			$match              = get_user_meta( $user->ID, self::USER_MATCH, true );
			$matching_workspace = is_array( $match ) && ( $match['email'] ?? '' ) === $user->user_email && ( $match['team_id'] ?? '' ) === ( $this->settings['team_id'] ?? '' );
			$matched            = $matching_workspace ? ( $match['id'] ?? '' ) : '';
			$match_status       = $override ? __( 'Manual: ', 'login-with-google' ) . $override : ( $matched ? __( 'Matched: ', 'login-with-google' ) . $matched : __( 'Not checked', 'login-with-google' ) );
			if ( ! $override && ! $matched && $matching_workspace && ! empty( $match['error'] ) ) {
				$match_status = 'slack_users_not_found' === $match['error'] ? __( 'No Slack match', 'login-with-google' ) : __( 'Needs attention', 'login-with-google' );
			}
			?>
			<tr><td><a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>"><?php echo esc_html( $user->display_name ); ?></a></td><td><?php echo esc_html( $user->user_email ); ?></td><td><?php echo esc_html( $match_status ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<p class="tablenav">
			<?php
			if ( $page > 1 ) :
				?>
				<a class="button" href="
				<?php
				echo esc_url(
					add_query_arg(
						[
							'page'            => 'login-with-google',
							'tab'             => 'notes-slack',
							'lwg_people_page' => $page - 1,
						],
						admin_url( 'options-general.php' )
					)
				);
				?>
				"><?php esc_html_e( 'Previous people', 'login-with-google' ); ?></a><?php endif; ?>
			<?php
			if ( $has_next ) :
				?>
				<a class="button" href="
				<?php
				echo esc_url(
					add_query_arg(
						[
							'page'            => 'login-with-google',
							'tab'             => 'notes-slack',
							'lwg_people_page' => $page + 1,
						],
						admin_url( 'options-general.php' )
					)
				);
				?>
				"><?php esc_html_e( 'Next people', 'login-with-google' ); ?></a><?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Open a Note thread when a recipient follows its Slack link.
	 *
	 * @param string $hook Admin page.
	 * @return void
	 */
	public function enqueue_note_link( string $hook ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- The URL only selects a Note to focus; it is checked below against user permissions.
		if ( 'post.php' !== $hook || ! isset( $_GET['lwg_note'], $_GET['post'] ) ) {
			return;
		}
		$note_id = absint( $_GET['lwg_note'] );
		$post_id = absint( $_GET['post'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$note = get_comment( $note_id );
		if ( ! $note instanceof WP_Comment || 'note' !== $note->comment_type || (int) $note->comment_post_ID !== $post_id || ! current_user_can( 'edit_comment', $note_id ) ) {
			return;
		}
		wp_enqueue_script( 'rtcamp-google-note-link', plugin()->url . 'assets/js/note-deeplink.js', [ 'wp-data' ], plugin()->version, true );
		wp_add_inline_script( 'rtcamp-google-note-link', 'window.rtcampGoogleNoteId = ' . (int) $note_id . ';', 'before' );
	}
}
