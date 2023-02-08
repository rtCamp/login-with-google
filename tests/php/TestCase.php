<?php
/**
 * This file is part of the github login package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin\Tests;

use WP_Mock\Tools\TestCase as WPMockTestCase;
use ReflectionException;
use ReflectionMethod;

/**
 * Class Testcase
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class TestCase extends WPMockTestCase {

	/**
	 * Sets up the fixture, for example, open a network connection.
	 * This method is called before a test is executed.
	 */
	public function setUp(): void {

		parent::setUp();
		\WP_Mock::setUp();
	}

	/**
	 * Tears down the fixture, for example, close a network connection.
	 * This method is called after a test is executed.
	 */
	public function tearDown(): void {

		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Build the Testee Mock Object
	 *
	 * Basic configuration available for all of the testee objects, call `getMock` to get the mock.
	 *
	 * @param string $class_name
	 * @param array  $constructor_arguments
	 * @param array  $methods
	 * @param string $sut_method
	 *
	 * @return PHPUnit_Framework_MockObject_MockBuilder
	 */
	protected function buildTesteeMock(
		string $class_name,
		array $constructor_arguments,
		array $methods,
		string $sut_method
	): object { // phpcs:ignore PHPCompatibility.FunctionDeclarations.NewReturnTypeDeclarations.objectFound -- Ignoring because assuming php application version greater than 7.1.

		$testee = $this->getMockBuilder( $class_name );

		$constructor_arguments
			? $testee->setConstructorArgs( $constructor_arguments )
			: $testee->disableOriginalConstructor();

		$methods && $testee->setMethods( $methods );
		$sut_method && $testee->setMethodsExcept( [ $sut_method ] );

		return $testee;
	}

	/**
	 * Retrieve a Testee Mock to Test Protected Methods
	 *
	 * return MockBuilder
	 *
	 * @param string $class_name
	 * @param array  $constructor_arguments
	 * @param string $method
	 * @param array  $methods
	 *
	 * @return array
	 *
	 * @throws ReflectionException
	 */
	protected function buildTesteeMethodMock(
		string $class_name,
		array $constructor_arguments,
		string $method,
		array $methods
	): array {

		$testee = $this->buildTesteeMock(
			$class_name,
			$constructor_arguments,
			$methods,
			''
		)->getMock();

		$reflection_method = new ReflectionMethod( $class_name, $method );
		$reflection_method->setAccessible( true );

		return [
			$testee,
			$reflection_method,
		];
	}

	/**
	 * Retrieve a Testee protected or private property.
	 *
	 * @param $property
	 * @param $object
	 */
	//phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
	protected function getTesteeProperty(
		string $property,
		object $object // phpcs:ignore PHPCompatibility.FunctionDeclarations.NewParamTypeDeclarations.objectFound -- Ignoring because assuming php application version greater than 7.1.
	) {

		$reflection          = new \ReflectionClass( $object );
		$reflection_property = $reflection->getProperty( $property );

		$reflection_property->setAccessible( true );
		return $reflection_property->getValue( $object );
	}

	/**
	 * Set private/protected property.
	 *
	 * @param $object
	 * @param $property
	 * @param $value
	 *
	 * @throws ReflectionException
	 */
	// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
	protected function setTesteeProperty(
		object $object, // phpcs:ignore PHPCompatibility.FunctionDeclarations.NewParamTypeDeclarations.objectFound -- Ignoring because assuming php application version greater than 7.1.
		string $property,
		$value
	): void {

		$reflection          = new \ReflectionClass( $object );
		$reflection_property = $reflection->getProperty( $property );

		$reflection_property->setAccessible( true );
		$reflection_property->setValue( $object, $value );
	}

	/**
	 * Use wp-mock to mock user defined and
	 * build-in wp functions.
	 *
	 * @param string $function_name
	 * @param mixed $args
	 * @param int $times
	 *
	 * @param mixed $return
	 */
	protected function wpMockFunction(
		string $function_name,
		$args = [],
		int $times = 1,
		$return = null
	) {

		$func_args = [
			'times' => $times,
		];

		if ( ! empty( $args ) ) {
			$func_args['args'] = $args;
		}

		if ( ! empty( $return ) ) {
			$func_args['return'] = $return;
		}

		\WP_Mock::userFunction(
			$function_name,
			$func_args
		);

	}
}
