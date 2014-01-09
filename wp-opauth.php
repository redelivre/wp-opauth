<?php

/*
Plugin Name: Opauth
Plugin URI: http://labculturadigital.org/
Description: Adds Opauth (opauth.org) support
Author: Laboratório de Cultura Digital - Flávio Zavan
Version: 0.01
Text Domain: wp-opauth
*/

define('CONF_FILE',
		dirname(__FILE__) . DIRECTORY_SEPARATOR . 'opauth.conf.php');
define('OPAUTH_PATH',
		dirname(__FILE__)
		. DIRECTORY_SEPARATOR . 'opauth'
		. DIRECTORY_SEPARATOR . 'lib'
		. DIRECTORY_SEPARATOR . 'Opauth');
define('WPOPAUTH_PATH', dirname(__FILE__));
define('WPOPAUTH_USER_TABLE_NAME', 'wpopauth_users');

require_once CONF_FILE;
require_once OPAUTH_PATH . DIRECTORY_SEPARATOR . 'Opauth.php';
require_once WPOPAUTH_PATH . DIRECTORY_SEPARATOR . 'wpopauth.php';

$opauth = new WPOpauth($config);

?>
