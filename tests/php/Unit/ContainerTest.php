<?php
/**
 * Test settings module class.
 */

declare( strict_types=1 );

namespace RtCamp\GoogleLogin\Tests\Unit;

use Mockery;
use InvalidArgumentException;
use Pimple\Container as PimpleContainer;
use RtCamp\GoogleLogin\Container;
use RtCamp\GoogleLogin\Container as Testee;
use RtCamp\GoogleLogin\Tests\TestCase;
use RtCamp\GoogleLogin\Interfaces\Container as ContainerInterface;

/**
 * Class ContainerTest
 *
 * @coversDefaultClass \RtCamp\GoogleLogin\Container
 *
 * @package RtCamp\GoogleLogin\Tests\Unit
 */
class ContainerTest extends  TestCase {

	/**
	 * @var PimpleContainer
	 */
	private $pimple_mock;

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

		$this->pimple_mock = $this->createMock( PimpleContainer::class );
		$this->testee      = new Testee( $this->pimple_mock );

	}

	public function testContainerImplementsInterface() {

		$this->assertInstanceOf( ContainerInterface::class, $this->testee );
	}

	/**
	 * @covers ::get
	 */
	public function testGetThrowsExceptionForNonExistentService() {

		$this->pimple_mock->expects( $this->once() )
						->method( 'keys' )
						->willReturn( [ 'example_service' ] );

		$this->expectException( InvalidArgumentException::class );

		$this->testee->get( 'non_existent_service' );
	}

	/**
	 * @covers ::get
	 */
	public function testGetReturnsServiceObject() {

		$dummy_service = (object) [
			'some_key'       => 'some_value',
			'some_other_key' => 'some_other_value',
		];

		$this->testee->container['test_service'] = $dummy_service;

		$this->pimple_mock->expects( $this->once() )
						->method( 'keys' )
						->willReturn( [ 'test_service' ] );

		$this->pimple_mock->expects( $this->once() )
						->method( 'offsetGet' )
						->with( 'test_service' )
						->willReturn( $dummy_service );

		$this->testee->get( 'test_service' );
	}
}
