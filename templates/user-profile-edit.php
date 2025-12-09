<?php
/**
 * User profile edit template.
 *
 * @package RtCamp\GoogleLogin
 * @since n.e.x.t
 */

use RtCamp\GoogleLogin\Utils\UserProfileHelper;

$rtlg_profile_picture_id     = UserProfileHelper::get_saved_google_profile_picture_id( $wp_user->ID );
$rtlg_profile_picture_source = UserProfileHelper::get_profile_picture_source( $wp_user->ID );
?>

<div class="rtlg-user-profile-edit">
	<h2><?php esc_html_e( 'Login With Google', 'login-with-google' ); ?></h2>
	<p><?php esc_html_e( 'Login With Google profile settings.', 'login-with-google' ); ?></p>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th>
					<label for="rtlg_profile_picture_source"><?php esc_html_e( 'Profile Picture Source', 'login-with-google' ); ?></label>
				</th>
				<td>
					<select name="rtlg_profile_picture_source" style="width: 15em;">
						<option
							value="google"
							<?php selected( $rtlg_profile_picture_source, 'google' ); ?>
						><?php esc_html_e( 'Google', 'login-with-google' ); ?></option>
						<option
							value="gravatar"
							<?php selected( $rtlg_profile_picture_source, 'gravatar' ); ?>
						><?php esc_html_e( 'Gravatar', 'login-with-google' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th>
					<label for="rtlg_google_profile_picture"><?php esc_html_e( 'Google Profile Picture', 'login-with-google' ); ?></label>				</th>
				<td>
				<?php if ( empty( $rtlg_profile_picture_id ) ) : ?>
					<?php esc_html_e( 'No Google profile picture set.', 'login-with-google' ); ?>
				<?php else : ?>
					<?php echo wp_get_attachment_image( $rtlg_profile_picture_id, [ 96, 96 ] ); ?>
				<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>
