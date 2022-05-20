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
class TestCase extends WPMockTestCase
{
	/**
	 * Sets up the fixture, for example, open a network connection.
	 * This method is called before a test is executed.
	 */
	public function setUp(): void
	{
		parent::setUp();
		\WP_Mock::setUp();
	}

	/**
	 * Tears down the fixture, for example, close a network connection.
	 * This method is called after a test is executed.
	 */
	public function tearDown(): void
	{
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Build the Testee Mock Object
	 *
	 * Basic configuration available for all of the testee objects, call `getMock` to get the mock.
	 *
	 * @param string $className
	 * @param array $constructorArguments
	 * @param array $methods
	 * @param string $sutMethod
	 *
	 * @return PHPUnit_Framework_MockObject_MockBuilder
	 */
	protected function buildTesteeMock(
		string $className,
		array $constructorArguments,
		array $methods,
		string $sutMethod
	): object {

		$testee = $this->getMockBuilder($className);
		$constructorArguments
			? $testee->setConstructorArgs($constructorArguments)
			: $testee->disableOriginalConstructor();

		$methods and $testee->setMethods($methods);
		$sutMethod and $testee->setMethodsExcept([$sutMethod]);

		return $testee;
	}

	/**
	 * Retrieve a Testee Mock to Test Protected Methods
	 *
	 * return MockBuilder
	 * @param string $className
	 * @param array $constructorArguments
	 * @param string $method
	 * @param array $methods
	 * @return array
	 * @throws ReflectionException
	 */
	protected function buildTesteeMethodMock(
		string $className,
		array $constructorArguments,
		string $method,
		array $methods
	): array {

		$testee = $this->buildTesteeMock(
			$className,
			$constructorArguments,
			$methods,
			''
		)->getMock();
		$reflectionMethod = new ReflectionMethod($className, $method);
		$reflectionMethod->setAccessible(true);
		return [
			$testee,
			$reflectionMethod,
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
		object $object
	) {

		$reflection = new \ReflectionClass($object);
		$reflectionProperty = $reflection->getProperty($property);
		$reflectionProperty->setAccessible(true);
		return $reflectionProperty->getValue($object);
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
	protected function setTesteeProperty(object $object, string $property, $value): void
	{
		$reflection = new \ReflectionClass($object);
		$reflectionProperty = $reflection->getProperty($property);
		$reflectionProperty->setAccessible(true);
		$reflectionProperty->setValue($object, $value);
	}

	/**
	 * Use wp-mock to mock user defined and
	 * build-in wp functions.
	 *
	 * @param string $function_name
	 * @param mixed $args
	 * @param int $times
	 * @param mixed $return
	 */
	protected function wpMockFunction(
		string $functionName,
		$args = [],
		int $times = 1,
		$return = null
	) {

		$funcArgs = [
			'times' => $times,
		];

		if (!empty($args)) {
			$funcArgs['args'] = $args;
		}

		if (!empty($return)) {
			$funcArgs['return'] = $return;
		}

		\WP_Mock::userFunction(
			$functionName,
			$funcArgs
		);
	}
}
