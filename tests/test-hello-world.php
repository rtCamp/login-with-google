<?php
/**
 * Class Test_Hello_World
 *
 * @package Wp_Google_Login
 */

/**
 * Hello world test case.
 */
class Test_Hello_World extends WP_UnitTestCase {

	/**
	 * Hello world example test.
	 */
	public function test_hello_world() {
        $message = 'Hello World';
		$this->assertEquals( 'Hello World', $message );
	}
}

