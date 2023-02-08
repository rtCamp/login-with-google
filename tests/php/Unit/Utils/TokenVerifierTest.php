<?php
/**
 * Tests for token verifier.
 *
 * @package RtCamp\GoogleLogin
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Tests\Unit\Utils;

use RtCamp\GoogleLogin\Modules\Settings;
use RtCamp\GoogleLogin\Tests\PrivateAccess;
use RtCamp\GoogleLogin\Tests\TestCase;
use RtCamp\GoogleLogin\Utils\TokenVerifier as Testee;

/**
 * Class TokenVerifierTest
 *
 * @coversDefaultClass \RtCamp\GoogleLogin\Utils\TokenVerifier
 *
 * @package RtCamp\GoogleLogin\Tests\Unit\Utils
 */
class TokenVerifierTest extends TestCase {

	use PrivateAccess;

	/**
	 * Object under test.
	 *
	 * @var \RtCamp\GoogleLogin\Utils\TokenVerifier
	 */
	private $testee;

	/**
	 * @var Settings
	 */
	private $settings_mock;

	/**
	 * @return void
	 */
	public function setUp(): void {

		$this->settings_mock = $this->createMock( Settings::class );
		$this->testee        = new Testee( $this->settings_mock );
	}

	/**
	 * @covers ::__construct
	 */
	public function testInstance() {

		$this->assertInstanceOf( Testee::class, $this->testee );
	}

	public function testCertsURL() {

		$this->assertSame( 'https://www.googleapis.com/oauth2/v1/certs', $this->testee::CERTS_URL );
	}

	/**
	 * @covers ::get_supported_algorithm
	 */
	public function testGetSupportedAlgorithmDefault() {

		\WP_Mock::expectFilter( 'rtcamp.default_algorithm', OPENSSL_ALGO_SHA256, '' );

		$expected = OPENSSL_ALGO_SHA256;
		$algo     = $this->testee::get_supported_algorithm();

		$this->assertSame( $expected, $algo );
	}

	/**
	 * @covers ::get_supported_algorithm
	 */
	public function testGetSHA256Algo() {

		$expected = OPENSSL_ALGO_SHA256;
		$algo     = $this->testee::get_supported_algorithm( 'RS256' );

		$this->assertSame( $expected, $algo );
	}

	/**
	 * @covers ::base64_encode_url
	 */
	public function testBase64EncodeURL() {

		$str    = 'some+random/string=';
		$result = $this->testee->base64_encode_url( $str );

		$this->assertSame( 'c29tZStyYW5kb20vc3RyaW5nPQ', $result );
	}

	/**
	 * @covers ::base64_decode_url
	 */
	public function testBase64DecodeURL() {

		$str    = 'c29tZStyYW5kb20vc3RyaW5nPQ';
		$result = $this->testee->base64_decode_url( $str );

		$this->assertSame( 'some+random/string=', $result );
	}

	/**
	 * @covers ::current_user
	 */
	public function testCurrentUser() {

		$wp_user = (object) [
			'name' => 'Test',
		];

		$this->setTesteeProperty( $this->testee, 'current_user', $wp_user );
		$result = $this->testee->current_user();

		$this->assertSame( $wp_user, $result );
	}

	/**
	 * @covers ::get_public_key
	 */
	public function testPublicKeyIsNull() {

		$pk = $this->testee->get_public_key( null );

		$this->assertNull( $pk );
	}

	/**
	 * @covers ::get_public_key
	 */
	public function testPublicKeyCachedValue() {

		$this->wpMockFunction(
			'get_transient',
			[
				'lwg_pk_my_public_key',
			],
			1,
			'abcd'
		);

		$pk = $this->testee->get_public_key( 'my_public_key' );

		$this->assertSame( 'abcd', $pk );
	}

	/**
	 * @covers ::get_public_key
	 */
	public function testPublicKeyIsNullForNon200Response() {

		$this->wpMockFunction(
			'get_transient',
			[
				'lwg_pk_my_public_key',
			],
			1,
			null
		);

		$this->wpMockFunction(
			'wp_remote_get',
			[
				$this->testee::CERTS_URL,
			],
			1,
			'certificate'
		);

		$this->wpMockFunction(
			'wp_remote_retrieve_response_code',
			[
				'certificate',
			],
			1,
			400
		);

		$pk = $this->testee->get_public_key( 'my_public_key' );

		$this->assertNull( $pk );
	}

	/**
	 * @covers ::get_public_key
	 * @covers ::get_max_age
	 */
	public function testPublicKeyRetrievalFromResponse() {

		$this->wpMockFunction(
			'get_transient',
			[
				'lwg_pk_my_public_key',
			],
			1,
			null
		);

		$this->wpMockFunction(
			'wp_remote_get',
			[
				$this->testee::CERTS_URL,
			],
			1,
			'certificate'
		);

		$this->wpMockFunction(
			'wp_remote_retrieve_response_code',
			[
				'certificate',
			],
			1,
			200
		);

		$headers = \Mockery::mock( \Requests_Utility_CaseInsensitiveDictionary::class );

		$headers->expects( 'offsetExists' )->withArgs( [ 'cache-control' ] )->andReturn( true );
		$headers->expects( 'offsetGet' )->withArgs( [ 'cache-control' ] )->andReturn( 'public, max-age=600' );

		$body = [
			'my_public_key' => 'thisissomerandomkey',
		];

		$body = \wp_json_encode( $body );

		$this->wpMockFunction(
			'wp_remote_retrieve_headers',
			[
				'certificate',
			],
			1,
			$headers
		);

		$this->wpMockFunction(
			'wp_remote_retrieve_body',
			[
				'certificate',
			],
			1,
			$body
		);

		$this->wpMockFunction(
			'set_transient',
			[
				'lwg_pk_my_public_key',
				'thisissomerandomkey',
				300,
			],
			1,
			true
		);

		$pk = $this->testee->get_public_key( 'my_public_key' );

		$this->assertSame( 'thisissomerandomkey', $pk );
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::set_transient
	 */
	public function testSetTransient() {

		$this->wpMockFunction(
			'set_transient',
			[
				'key',
				'val',
				200,
			]
		);

		$this->call_private_method( $this->testee, 'set_transient', [ 'key', 'val', 200 ] );

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::get_transient
	 */
	public function testGetTransient() {
		$this->wpMockFunction(
			'get_transient',
			[
				'key',
			],
			1,
			'val'
		);

		$val = $this->call_private_method( $this->testee, 'get_transient', [ 'key' ] );

		$this->assertSame( 'val', $val );
		$this->assertConditionsMet();
	}
}
