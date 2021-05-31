<?php
/**
 * Test settings module class.
 */

declare( strict_types=1 );

namespace RtCamp\GithubLogin\Tests\Unit\Utils;

use WP_Mock;
use Exception;
use RtCamp\GithubLogin\Tests\TestCase;
use RtCamp\GithubLogin\Utils\GithubClient as Testee;

/**
 * Class GithubClientTest
 *
 * @coversDefaultClass \RtCamp\GithubLogin\Utils\GithubClient
 *
 * @package RtCamp\GithubLogin\Tests\Unit\Utils
 */
class GithubClientTest extends TestCase {

	/**
	 * Object under test.
	 *
	 * @var Testee
	 */
	private $testee;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$this->testee = new Testee( [
			'client_id'     => 'cid',
			'client_secret' => 'csc',
		] );
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$this->assertSame( 'cid', $this->getTesteeProperty( 'client_id', $this->testee ) );
		$this->assertSame( 'csc', $this->getTesteeProperty( 'client_secret', $this->testee ) );
		$this->assertSame( '', $this->getTesteeProperty( 'redirect_uri', $this->testee ) );
	}

	/**
	 * @covers ::__call
	 */
	public function testCallWithUser() {
		$this->expectException( Exception::class );
		$this->testee->__call( 'user', null );
	}

	/**
	 * @covers ::__call
	 */
	public function testCallWithEmails() {
		$this->expectException( Exception::class );
		$this->testee->__call( 'emails', null );
	}

	/**
	 * @covers ::__call
	 * @covers ::gt_redirect_url
	 */
	public function testCallWithOtherMethods() {
		WP_Mock::expectFilterNotAdded( 'rtcamp.github_redirect_url', '' );
		$this->testee->__call( 'some_other_method', null );

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::set_access_token
	 * @covers ::state
	 * @covers ::gt_redirect_url
	 * @covers ::access_token
	 */
	public function testSetAccessToken() {
		WP_Mock::expectFilter( 'rtcamp.github_redirect_url', '' );

		$this->wpMockFunction(
			'wp_create_nonce',
			[
				'login_with_github'
			],
			1,
			'some123'
		);

		WP_Mock::expectFilter( 'rtcamp.github_login_state', [ 'nonce' => 'some123' ] );

		$this->wpMockFunction(
			'wp_json_encode',
			[
				[
					'nonce'    => 'some123',
					'provider' => 'github',
				]
			],
			1,
			'jsonEncoded'
		);

		$this->wpMockFunction(
			'wp_remote_post',
			[
				'https://github.com/login/oauth/access_token',
				[
					'headers' => [
						'Accept' => 'application/json',
					],
					'body'    => [
						'client_id'     => 'cid',
						'client_secret' => 'csc',
						'redirect_uri'  => '',
						'code'          => 'abc',
						'state'         => base64_encode( 'jsonEncoded' ),
					],
				]
			],
			1,
			'response'
		);

		$this->wpMockFunction(
			'wp_remote_retrieve_response_code',
			[
				'response'
			],
			1,
			200
		);

		$this->wpMockFunction(
			'wp_remote_retrieve_body',
			[
				'response',
			],
			1,
			function() {
				$token = (object) [
					'access_token' => 'AccessToken'
				];
				return json_encode( $token );
			}
		);

		$obj = $this->testee->set_access_token( 'abc' );
		$token = $this->getTesteeProperty( 'access_token', $this->testee );

		$this->assertSame( $this->testee, $obj );
		$this->assertSame( 'AccessToken', $token );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::set_access_token
	 */
	public function testSetAccessTokenThrowsException() {
		WP_Mock::expectFilter( 'rtcamp.github_redirect_url', '' );

		$this->wpMockFunction(
			'wp_create_nonce',
			[
				'login_with_github'
			],
			1,
			'some123'
		);

		WP_Mock::expectFilter( 'rtcamp.github_login_state', [ 'nonce' => 'some123' ] );

		$this->wpMockFunction(
			'wp_json_encode',
			[
				[
					'nonce'    => 'some123',
					'provider' => 'github',
				]
			],
			1,
			'jsonEncoded'
		);

		$this->wpMockFunction(
			'wp_remote_post',
			[
				'https://github.com/login/oauth/access_token',
				[
					'headers' => [
						'Accept' => 'application/json',
					],
					'body'    => [
						'client_id'     => 'cid',
						'client_secret' => 'csc',
						'redirect_uri'  => '',
						'code'          => 'abc',
						'state'         => base64_encode( 'jsonEncoded' ),
					],
				]
			],
			1,
			'response'
		);

		$this->wpMockFunction(
			'wp_remote_retrieve_response_code',
			[
				'response'
			],
			1,
			400
		);

		$this->expectException( Exception::class );
		$this->testee->set_access_token( 'abc' );
	}

	/**
	 * @covers ::access_token
	 */
	public function testAccessTokenThrowsExceptionForNon200Code() {
		$ghClient = $this->createPartialMock( Testee::class, [ 'gt_redirect_url', 'state' ] );
		$ghClient->expects( $this->once() )->method( 'gt_redirect_url' )->willReturn( '' );
		$ghClient->expects( $this->once() )->method( 'state' )->willReturn( 'dummystate' );
		$ghClient->client_id     = 'cid';
		$ghClient->client_secret = 'csc';

		$this->wpMockFunction(
			'wp_remote_post',
			[
				'https://github.com/login/oauth/access_token',
				[
					'headers' => [
						'Accept' => 'application/json',
					],
					'body'    => [
						'client_id'     => 'cid',
						'client_secret' => 'csc',
						'redirect_uri'  => '',
						'code'          => 'abc',
						'state'         => 'dummystate',
					],
				]
			],
			1,
			'response'
		);

		$this->wpMockFunction(
			'wp_remote_retrieve_response_code',
			[
				'response'
			],
			1,
			400
		);

		$this->expectException( Exception::class );
		$ghClient->access_token( 'abc' );
	}

	/**
	 * @covers ::user
	 */
	public function testUserReturnsObject() {
		$this->setTesteeProperty( $this->testee, 'access_token', 'someToken' );

		$this->wpMockFunction(
			'trailingslashit',
			[
				'https://api.github.com'
			],
			1,
			'https://api.github.com/'
		);

		$this->wpMockFunction(
			'wp_remote_get',
			[
				'https://api.github.com/user',
				[
					'headers' => [
						'Authorization' => 'token ' . 'someToken',
						'Accept'        => 'application/json',
					],
				]
			],
			1,
			'response'
		);

		$this->wpMockFunction(
			'wp_remote_retrieve_response_code',
			[
				'response'
			],
			1,
			200
		);

		$this->wpMockFunction(
			'wp_remote_retrieve_body',
			[
				'response',
			],
			1,
			function() {
				$token = (object) [
					'email' => 'user@domain.com',
					'login' => 'login',
				];
				return json_encode( $token );
			}
		);

		$user = $this->testee->user();
		$this->assertInstanceOf( \stdClass::class, $user );
		$this->assertSame( $user->email, 'user@domain.com' );
		$this->assertSame( $user->login, 'login' );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::user
	 */
	public function testUserThrowsException() {
		$this->setTesteeProperty( $this->testee, 'access_token', 'someToken' );

		$this->wpMockFunction(
			'trailingslashit',
			[
				'https://api.github.com'
			],
			1,
			'https://api.github.com/'
		);

		$this->wpMockFunction(
			'wp_remote_get',
			[
				'https://api.github.com/user',
				[
					'headers' => [
						'Authorization' => 'token ' . 'someToken',
						'Accept'        => 'application/json',
					],
				]
			],
			1,
			'response'
		);

		$this->wpMockFunction(
			'wp_remote_retrieve_response_code',
			[
				'response'
			],
			1,
			404
		);

		$this->expectException( Exception::class );
		$this->testee->user();
	}

	/**
	 * @covers ::emails
	 */
	public function testEmailReturnsObject() {
		$this->setTesteeProperty( $this->testee, 'access_token', 'someToken' );

		$this->wpMockFunction(
			'trailingslashit',
			[
				'https://api.github.com'
			],
			1,
			'https://api.github.com/'
		);

		$this->wpMockFunction(
			'wp_remote_get',
			[
				'https://api.github.com/user/emails',
				[
					'headers' => [
						'Authorization' => 'token ' . 'someToken',
						'Accept'        => 'application/json',
					],
				]
			],
			1,
			'response'
		);

		$this->wpMockFunction(
			'wp_remote_retrieve_response_code',
			[
				'response'
			],
			1,
			200
		);

		$this->wpMockFunction(
			'wp_remote_retrieve_body',
			[
				'response',
			],
			1,
			function () {
				$mails = [
					[
						'email'   => 'user1@domain.com',
						'primary' => true,
					],
					[
						'email'   => 'user2@domain.com',
						'primary' => false,
					]
				];

				return json_encode( $mails );
			}
		);

		$emails = $this->testee->emails();
		$this->assertIsArray( $emails );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::emails
	 */
	public function testEmailsThrowsException() {
		$this->setTesteeProperty( $this->testee, 'access_token', 'someToken' );

		$this->wpMockFunction(
			'trailingslashit',
			[
				'https://api.github.com'
			],
			1,
			'https://api.github.com/'
		);

		$this->wpMockFunction(
			'wp_remote_get',
			[
				'https://api.github.com/user/emails',
				[
					'headers' => [
						'Authorization' => 'token ' . 'someToken',
						'Accept'        => 'application/json',
					],
				]
			],
			1,
			'response'
		);

		$this->wpMockFunction(
			'wp_remote_retrieve_response_code',
			[
				'response'
			],
			1,
			400
		);

		$this->expectException( Exception::class );
		$this->testee->emails();
	}

	/**
	 * @covers ::authorization_url
	 */
	public function testAuthorizationURL() {
		$ghClient = $this->createPartialMock( Testee::class, [ 'gt_redirect_url', 'state' ] );
		$ghClient->expects( $this->once() )->method( 'gt_redirect_url' )->willReturn( '' );
		$ghClient->expects( $this->once() )->method( 'state' )->willReturn( 'abcd' );
		$ghClient->client_id     = 'cid';

		$expected = 'https://github.com/login/oauth/authorize?client_id=cid&redirect_uri=&state=abcd&scope=user%3Aemail+read%3Auser';

		$this->assertSame( $expected, $ghClient->authorization_url() );
	}
}
