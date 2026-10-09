<?php
/**
 * Test One Tap login module class.
 */

declare( strict_types=1 );

namespace RtCamp\GoogleLogin\Tests\Unit\Modules;

use WP_Mock;
use RtCamp\GoogleLogin\Tests\TestCase;
use RtCamp\GoogleLogin\Modules\Settings;
use RtCamp\GoogleLogin\Utils\Authenticator;
use RtCamp\GoogleLogin\Utils\GoogleClient;
use RtCamp\GoogleLogin\Utils\TokenVerifier;
use RtCamp\GoogleLogin\Modules\OneTapLogin as Testee;

/**
 * Class OneTapLoginTest
 *
 * @coversDefaultClass \RtCamp\GoogleLogin\Modules\OneTapLogin
 *
 * @package RtCamp\GoogleLogin\Tests\Unit\Modules
 */
class OneTapLoginTest extends TestCase {

	/**
	 * @covers ::one_tap_prompt
	 * @dataProvider hostedDomainProvider
	 */
	public function testOneTapPromptHostedDomain( string $domain, string $pattern ) {
		$settings = $this->createPartialMock( Settings::class, [ 'hosted_domain' ] );
		$settings->method( 'hosted_domain' )->willReturn( $domain );
		$settings->options = [ 'client_id' => 'cid' ];

		WP_Mock::userFunction( 'esc_attr', [ 'return_arg' => 0 ] );
		WP_Mock::userFunction( 'wp_login_url', [ 'return' => 'https://example.test/wp-login.php' ] );

		$testee = new Testee(
			$settings,
			$this->createMock( TokenVerifier::class ),
			$this->createMock( GoogleClient::class ),
			$this->createMock( Authenticator::class )
		);

		$this->expectOutputRegex( $pattern );
		$testee->one_tap_prompt();
	}

	public function hostedDomainProvider(): array {
		return [
			'with domain'    => [ 'example.com', '/data-hd="example\.com"/' ],
			'without domain' => [ '', '/^(?!.*data-hd).*$/s' ],
		];
	}
}
