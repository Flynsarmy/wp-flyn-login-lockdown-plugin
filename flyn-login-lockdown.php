<?php
/*
Plugin Name: Flyn Login Lockdown
Plugin URI: https://github.com/flynsarmy/wp-flyn-login-lockdown
Description: A simple way to prevent brute force login attemps on your WordPress installation.
Version: 1
Author: Flyn San
Author URI: https://www.flynsarmy.com
License: MIT
*/

require_once __DIR__.'/vendor/autoload.php';

\FLL\Lockdown::instance();

if(is_admin() && (!defined('DOING_AJAX' ) || !DOING_AJAX))
{
    $instance = \FLL\Admin::instance();
}

add_action('wp_ajax_fll_lockout_test', function() {
	\FLL\Lockout::set(\FLL\Lockout::TYPE_IP, '99.99.99.99', 'test_username99999999');
	exit('Added an IP lock for 99.99.99.99');
});