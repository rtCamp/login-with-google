<?php
/**
 * Small Slack Web API client for Notes notifications.
 *
 * @package RtCamp\GoogleLogin
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Utils;

use WP_Error;

/**
 * Talks directly to Slack from the WordPress site.
 */
class SlackClient {
	/**
	 * Slack bot token.
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Create a client for the installed Slack app.
	 *
	 * @param string $token Slack bot token.
	 */
	public function __construct( string $token ) {
		$this->token = $token;
	}

	/**
	 * Check the token and return the connected workspace.
	 *
	 * @return array|WP_Error
	 */
	public function auth_test() {
		return $this->request( 'auth.test' );
	}

	/**
	 * Find a Slack user by their WordPress email address.
	 *
	 * @param string $email Email address.
	 * @return array|WP_Error
	 */
	public function user_by_email( string $email ) {
		return $this->request( 'users.lookupByEmail', [ 'email' => $email ], 'GET' );
	}

	/**
	 * Open a DM with a Slack user.
	 *
	 * @param string $user_id Slack user ID.
	 * @return array|WP_Error
	 */
	public function open_dm( string $user_id ) {
		return $this->request( 'conversations.open', [ 'users' => $user_id ] );
	}

	/**
	 * Send a message to a DM conversation.
	 *
	 * @param string $channel DM ID.
	 * @param string $text Message with Slack formatting.
	 * @return array|WP_Error
	 */
	public function post_message( string $channel, string $text ) {
		return $this->request(
			'chat.postMessage',
			[
				'channel'      => $channel,
				'text'         => $text,
				'unfurl_links' => false,
				'unfurl_media' => false,
			],
			'POST'
		);
	}

	/**
	 * Make a Slack API request without exposing the token in the URL or logs.
	 *
	 * @param string $method API method.
	 * @param array  $data API arguments.
	 * @param string $http_method HTTP method.
	 * @return array|WP_Error
	 */
	private function request( string $method, array $data = [], string $http_method = 'POST' ) {
		$url  = 'https://slack.com/api/' . $method;
		$args = [
			'timeout' => 3,
			'headers' => [ 'Authorization' => 'Bearer ' . $this->token ],
		];

		if ( 'GET' === $http_method ) {
			$url      = add_query_arg( $data, $url );
			$response = wp_remote_request( $url, array_merge( $args, [ 'method' => 'GET' ] ) );
		} else {
			$args['headers']['Content-Type'] = 'application/json; charset=utf-8';
			$args['body']                    = wp_json_encode( (object) $data );
			$response                        = wp_remote_post( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['ok'] ) ) {
			$error = is_array( $body ) && ! empty( $body['error'] ) ? sanitize_key( $body['error'] ) : 'invalid_response';
			return new WP_Error( 'slack_' . $error, $error );
		}

		return $body;
	}
}
