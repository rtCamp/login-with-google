<?php
/**
 * Test_Plugin class for all helper function test.
 *
 * @author  Suraj Singh <suraj.sk243@gmail.com>
 *
 * @package wp-google-login
 */

namespace WP_Google_Login\Tests;

use WP_Google_Login\Inc\Plugin;

/**
 * Class Test_Plugin
 * 
 * @coversDefaultClass \WP_Google_Login\Inc\Plugin
 * 
 */
class Test_Plugin extends \WP_UnitTestCase {
    
    public function test_setup_hooks() { 
        $plugin = Plugin::get_instance();

        $is_login_enqueue_scripts = has_action('login_enqueue_scripts', [ $plugin, '_setup_hooks' ] );
        $is_login_form            = has_action('login_form', [ $plugin, '_setup_hooks' ] );
        $is_register_form         = has_action('register_form', [ $plugin, '_setup_hooks' ] );
        $this->assertEquals(10 , $is_login_enqueue_scripts );
        $this->assertEquals(10 , $is_login_form );
        $this->assertEquals(10 , $is_register_form );
    }

}

