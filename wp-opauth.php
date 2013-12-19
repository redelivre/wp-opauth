<?php

/*
Plugin Name: Wordpress Opauth
Plugin URI: http://www.ethymos.com.br
Description: Adds Opauth (opauth.org) support
Author: Ethymos
Version: 0.01
Text Domain: wp-opauth
*/

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wpopauth.php';

add_action('login_form', array('WPOpauth', 'login_form'));

?>
