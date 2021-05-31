<?php
/**
 * Test settings module class.
 */

declare( strict_types=1 );

namespace RtCamp\GithubLogin\Tests\Unit;

use Mockery;
use InvalidArgumentException;
use Pimple\Container as PimpleContainer;
use RtCamp\GithubLogin\Container;
use RtCamp\GithubLogin\Container as Testee;
use RtCamp\GithubLogin\Tests\TestCase;
use RtCamp\GithubLogin\Interfaces\Container as ContainerInterface;

/**
 * Class ContainerTest
 *
 * @coversDefaultClass \RtCamp\GithubLogin\Container
 *
 * @package RtCamp\GithubLogin\Tests\Unit
 */
class ContainerTest extends  TestCase {

	/**
	 * @var PimpleContainer
	 */
	private $pimpleMock;

	/**
	 * Object under test.
	 *
	 * @var Container
	 */
	private $testee;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$this->pimpleMock = $this->createMock( PimpleContainer::class );
		$this->testee     = new Testee( $this->pimpleMock );
	}

	public function testContainerImplementsInterface() {
		$this->assertInstanceOf( ContainerInterface::class, $this->testee );
	}

	/**
	 * @covers ::get
	 */
	public function testGetThrowsExceptionForNonExistentService() {
		$this->pimpleMock->expects( $this->once() )
		                        ->method( 'keys' )
		                        ->willReturn( [ 'example_service' ] );

		$this->expectException( InvalidArgumentException::class );
		$this->testee->get( 'non_existent_service' );
	}

	/**
	 * @covers ::get
	 */
	public function testGetReturnsServiceObject() {
		$dummyService = (object) [
			'some_key'       => 'some_value',
			'some_other_key' => 'some_other_value',
		];

		$this->testee->container['test_service'] = $dummyService;

		$this->pimpleMock->expects( $this->once() )
		                        ->method( 'keys' )
		                        ->willReturn( [ 'test_service' ] );

		$this->pimpleMock->expects( $this->once() )
		                        ->method( 'offsetGet' )
		                        ->with( 'test_service' )
		                        ->willReturn( $dummyService );

		$this->testee->get( 'test_service' );
	}
}
