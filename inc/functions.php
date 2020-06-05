<?php
/**
 * Don't load directly.
 *
 * @package wp-google-login
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Get Client id.
 *
 * @return mixed|string
 */
function wp_google_login_get_client_id() {
	$options   = get_option( 'wp_google_login_settings' );
	$client_id = '';
	if ( defined( 'WP_GOOGLE_LOGIN_CLIENT_ID' ) ) {
		$client_id = WP_GOOGLE_LOGIN_CLIENT_ID;
	} else {
		$client_id = ! empty( $options['client_id'] ) ? $options['client_id'] : '';
	}

	return $client_id;
}

/**
 * Get Client secret.
 *
 * @return mixed|string
 */
function wp_google_login_get_client_secret() {
	$options       = get_option( 'wp_google_login_settings' );
	$client_secret = '';
	if ( defined( 'WP_GOOGLE_LOGIN_SECRET' ) ) {
		$client_secret = WP_GOOGLE_LOGIN_SECRET;
	} else {
		$client_secret = ! empty( $options['client_secret'] ) ? $options['client_secret'] : '';
	}

	return $client_secret;
}

/**
 * Get Client whitelisted domains.
 *
 * @return mixed|string
 */
function wp_google_login_get_whitelisted_domains() {
	$options             = get_option( 'wp_google_login_settings' );
	$whitelisted_domains = '';
	if ( defined( 'WP_GOOGLE_LOGIN_WHITELIST_DOMAINS' ) ) {
		$whitelisted_domains = trim( WP_GOOGLE_LOGIN_WHITELIST_DOMAINS );
	} else {
		$whitelisted_domains = ! empty( $options['whitelisted_domains'] ) ? $options['whitelisted_domains'] : '';
	}

	return $whitelisted_domains;
}
