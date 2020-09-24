<?php
/**
 * Test_Google_Auth class for all function test.
 *
 * @author  Suraj Singh <suraj.sk243@gmail.com>
 *
 * @package login-with-google
 */

namespace WP_Google_Login\Tests;

/**
 * Class Utility
 */
class Utility {

	/**
	 * Utility method to call private/protected method of a class and return method result as returned by the said
	 * method. This is a generic wrapper function to align with relfection class and not to be use directly.
	 *
	 * @param mixed  $object_or_class_name The object/class whose method is to be called.
	 * @param string $method_name          The Name of the method to call.
	 * @param array  $parameters           The Parameters to be passed to the hidden method being called.
	 *
	 * @return mixed                      Result returned by the hidden method being called.
	 */
	public static function invoke_method( $object_or_class_name, string $method_name, array $parameters = [] ) {

		$object = null;

		if ( is_object( $object_or_class_name ) ) {
			$object     = $object_or_class_name;
			$class_name = get_class( $object );
		} else {
			$class_name = $object_or_class_name;
		}

		$o_reflection = new \ReflectionClass( $class_name );

		$method = $o_reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $parameters );

	}

	/**
	 * Utility method to get private/protected property of a class/object.
	 * This is a generic wrapper function to align with relfection class and not to be use directly.
	 *
	 * @param mixed  $object_or_class_name The object/class whose property is to be accessed.
	 * @param string $property_name        The name of the property to access.
	 *
	 * @return mixed                      Value of the hidden property being accessed.
	 */
	public static function get_property( $object_or_class_name, $property_name ) {

		$object = null;

		if ( is_object( $object_or_class_name ) ) {
			$object     = $object_or_class_name;
			$class_name = get_class( $object );
		} else {
			$class_name = $object_or_class_name;
		}

		$o_reflection = new \ReflectionClass( $class_name );
		$property     = $o_reflection->getProperty( $property_name );
		$property->setAccessible( true );

		return $property->getValue( $object );

	}

	/**
	 * Utility method to set private/protected property of an object/class.
	 * This is a generic wrapper function to align with relfection class and not to be use directly.
	 *
	 * @param mixed  $object_or_class_name The object/class whose property is to be accessed.
	 * @param string $property_name        The name of the property to access.
	 * @param mixed  $property_value       The value to be set for the hidden property.
	 *
	 * @return mixed                      Value of the hidden property being accessed.
	 */
	public static function set_and_get_property( $object_or_class_name, string $property_name, $property_value ) {

		$object = null;

		if ( is_object( $object_or_class_name ) ) {
			$object     = $object_or_class_name;
			$class_name = get_class( $object );
		} else {
			$class_name = $object_or_class_name;
		}

		$o_reflection = new \ReflectionClass( $class_name );
		$property     = $o_reflection->getProperty( $property_name );
		$property->setAccessible( true );
		$property->setValue( $object, $property_value );

		return $property->getValue( $object );

	}

}
