<?php
/**
 * Stubs for non-existent functions in WP.
 */

if ( ! function_exists( 'remove_filter' ) ) {

	function remove_filter( $tag, $callback, $priority = 10, $args = 0 ) { }
}

if ( ! function_exists( 'apply_filters_deprecated' ) ) {
	function apply_filters_deprecated( string $tag, array $args, string $version, string $replacement = '', string $message = '' ) {
		return $args[0] ?? [];
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return ( $thing instanceof \WP_Error );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return is_string( $str ) ? trim( $str ) : '';
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $val ) {
		return $val;
	}
}

