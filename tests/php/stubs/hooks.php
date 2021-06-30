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
