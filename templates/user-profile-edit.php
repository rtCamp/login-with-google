<?php
/**
 * User profile edit template.
 *
 * @package RtCamp\GoogleLogin
 * @since n.e.x.t
 */

?>

<div class="rtlg-user-profile-edit">
	<h2><?php esc_html_e( 'Login With Google', 'login-with-google'); ?></h2>
	<p><?php esc_html_e( 'Login With Google profile settings.', 'login-with-google' ); ?></p>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th>
					<label for="rtlg_avatar_source"><?php esc_html_e( 'Avatar Source', 'login-with-google' ); ?></label>
				</th>
				<td>
					<select name="rtlg_avatar_source" style="width: 15em;">
						<option
							value="google"
							<?php selected( get_user_meta( $wp_user->ID, 'rtlg_avatar_source', true ), 'google' ); ?>
						><?php esc_html_e( 'Google', 'login-with-google' ); ?></option>
						<option
							value="gravatar"
							<?php selected( get_user_meta( $wp_user->ID, 'rtlg_avatar_source', true ), 'gravatar' ); ?>
						><?php esc_html_e( 'Gravatar', 'login-with-google' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th>
					<label for="rtlg_google_avatar"><?php esc_html_e( 'Google Avatar', 'login-with-google' ); ?></label>
				</th>
				<td>
					<img src="https://placehold.co/150x150" />
				</td>
			</tr>
		</tbody>
	</table>
</div>
