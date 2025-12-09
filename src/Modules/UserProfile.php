<?php
/**
 * Handle User Profile module.
 *
 * @package RtCamp\GoogleLogin
 * @since n.e.x.t
 * @author rtCamp <contact@rtcamp.com>
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Modules;

use RtCamp\GoogleLogin\Interfaces\Module as ModuleInterface;
use RtCamp\GoogleLogin\Utils\UserProfileHelper;
use function RtCamp\GoogleLogin\plugin;

/**
 * Class UserProfile.
 *
 * @package RtCamp\GoogleLogin\Modules
 */
class UserProfile implements ModuleInterface {

	/**
	 * Module name.
	 *
	 * @since n.e.x.t
	 *
	 * @return string
	 */
	public function name(): string {
		return 'user_profile';
	}

	/**
	 * Initialize the UserProfile module.
	 *
	 * @since n.e.x.t
	 * @return void
	 */
	public function init(): void {
		add_action( 'get_avatar_url', [ $this, 'return_avatar_url' ], 10, 3 );

		// Render the profile edit options.
		add_action( 'show_user_profile', [ $this, 'render_user_profile_edit_options' ] );
		add_action( 'edit_user_profile', [ $this, 'render_user_profile_edit_options' ] );

		// Save the profile edit options.
		add_action( 'personal_options_update', [ $this, 'save_user_profile_edit_options' ] );
		add_action( 'edit_user_profile_update', [ $this, 'save_user_profile_edit_options' ] );
	}

	/**
	 * Return the stored profile picture during the account creation.
	 *
	 * @since n.e.x.t
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
		 * @param boolean $use_saved_profile_picture_for_avatar Whether to bypass the use of the saved profile picture for avatar or not.
		 */
		$use_avatar_url = apply_filters( 'rtcamp.google_use_saved_profile_picture_for_avatar', true );

		if ( ! $use_avatar_url ) {
			return $url;
		}

		// Do not interfere on profile edit page.
		if ( defined( 'IS_PROFILE_PAGE' ) && IS_PROFILE_PAGE ) {
			return $url;
		}

		// Do not interfere on user edit screen in admin.
		$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $current_screen && 'user-edit' === $current_screen->id ) {
			return $url;
		}

		$wp_user = null;
		if ( is_int( $id_or_email ) ) {
			$wp_user = get_user_by( 'id', $id_or_email );
		} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$wp_user = get_user_by( 'email', $id_or_email );
		} elseif ( $id_or_email instanceof \WP_User ) {
			$wp_user = $id_or_email;
		} elseif ( $id_or_email instanceof \WP_Post ) {
			$wp_user = get_user_by( 'id', (int) $id_or_email->post_author );
		} elseif ( $id_or_email instanceof \WP_Comment ) {
			$wp_user = get_user_by( 'id', (int) $id_or_email->user_id );
		}

		// Bail early if the user is not found.
		if ( ! $wp_user ) {
			return $url;
		}

		// Bail if user has chosen gravatar as avatar source.
		$profile_picture_source = UserProfileHelper::get_profile_picture_source( $wp_user->ID );
		if ( $profile_picture_source && 'gravatar' === $profile_picture_source ) {
			return $url;
		}

		// Return the saved google avatar URL.
		$width  = isset( $args['width'] ) ? absint( $args['width'] ) : 96;
		$height = isset( $args['height'] ) ? absint( $args['height'] ) : 96;

		$profile_picture_id = UserProfileHelper::get_saved_google_profile_picture_id( $wp_user->ID );

		if ( ! empty( $profile_picture_id ) ) {
			$profile_picture_url = wp_get_attachment_image_url( $profile_picture_id, [ $width, $height ] );
			if ( $profile_picture_url ) {
				$url = $profile_picture_url;
			}
		}

		return $url;
	}

	/**
	 * Render user profile edit template
	 *
	 * @since n.e.x.t
	 * @param WP_User $wp_user WP_User object.
	 * @return void
	 */
	public function render_user_profile_edit_options( $wp_user ) {
		require_once plugin()->template_dir . 'user-profile-edit.php';
	}

	/**
	 * Save user profile edit options.
	 *
	 * @since n.e.x.t
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function save_user_profile_edit_options( $user_id ) {
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-user_' . $user_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		if ( isset( $_POST['rtlg_profile_picture_source'] ) ) {
			$avatar_source = sanitize_text_field( wp_unslash( $_POST['rtlg_profile_picture_source'] ) );
			Helper::save_profile_picture_source( $user_id, $avatar_source );
		}

		return true;
	}
}
