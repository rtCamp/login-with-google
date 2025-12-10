<?php
/**
 * Helper class for user profile functions.
 *

 *
 * @package RtCamp\GoogleLogin
 * @since n.e.x.t
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Utils;

/**
 * Class UserProfileHelper
 */
class UserProfileHelper {

	/**
	 * Return Google profile picture which is saved attachment ID.
	 *
	 * @since n.e.x.t
	 *
	 * @param integer $user_id WP User ID.
	 * @return integer|null
	 */
	public static function get_saved_google_profile_picture_id( int $user_id ): ?int {
		$profile_picture_id = get_user_meta( $user_id, 'rtlwg_profile_picture_id', true );

		if ( ! empty( $profile_picture_id ) ) {
			return (int) $profile_picture_id;
		}

		return null;
	}

	/**
	 * Set the attachment as Google profile picture to user.
	 *
	 * @since n.e.x.t
	 *
	 * @param integer $user_id WP User ID.
	 * @param integer $attachment_id Attachment ID of the saved profile picture.
	 *
	 * @return void
	 */
	public static function set_google_profile_picture_to_user( int $user_id, int $attachment_id ): void {
		update_user_meta( $user_id, 'rtlwg_profile_picture_id', $attachment_id );
	}

	/**
	 * Get saved profile picture source.
	 *
	 * @since n.e.x.t
	 *
	 * @param integer $user_id WP User ID.
	 * @return string Returns 'gravatar' as default source.
	 */
	public static function get_profile_picture_source( int $user_id ): ?string {
		$profile_picture_source = get_user_meta( $user_id, 'rtlg_profile_picture_source', true );
		$profile_picture_source = trim( strval( $profile_picture_source ) );
		$profile_picture_source = in_array( $profile_picture_source, [ 'google', 'gravatar' ], true ) ? $profile_picture_source : 'gravatar';

		return $profile_picture_source;
	}

	/**
	 * Save original Google profile picture URL.
	 *
	 * @since n.e.x.t
	 *
	 * @param integer $user_id WP User ID.
	 * @param string  $url Original Google profile picture URL.
	 * @return void
	 */
	public static function save_original_google_profile_picture_url( int $user_id, string $url ): void {
		update_user_meta( $user_id, 'rtlg_original_google_profile_picture_url', esc_url_raw( $url ) );
	}

	/**
	 * Get saved original Google profile picture URL.
	 *
	 * @since n.e.x.t
	 *
	 * @param integer $user_id WP User ID.
	 * @return string|null
	 */
	public static function get_saved_original_google_profile_picture_url( int $user_id ): ?string {
		$original_url = get_user_meta( $user_id, 'rtlg_original_google_profile_picture_url', true );

		if ( empty( $original_url ) ) {
			return null;
		}

		return esc_url_raw( $original_url );
	}

	/**
	 * Save profile picture source.
	 *
	 * @since n.e.x.t
	 *
	 * @param integer $user_id WP User ID.
	 * @param string  $source Profile picture source.
	 * @return void
	 */
	public static function save_profile_picture_source( int $user_id, string $source ): void {
		update_user_meta( $user_id, 'rtlg_profile_picture_source', $source );
	}

	/**
	 * Check if user has Google profile picture set.
	 *
	 * @since n.e.x.t
	 *
	 * @param integer $user_id WP User ID.
	 * @return boolean
	 */
	public static function has_google_profile_picture( int $user_id ): bool {
		$profile_picture_id                  = self::get_saved_google_profile_picture_id( $user_id );
		$original_google_profile_picture_url = self::get_saved_original_google_profile_picture_url( $user_id );

		return ! ( empty( $profile_picture_id ) || empty( $original_google_profile_picture_url ) );
	}
}
