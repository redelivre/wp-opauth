<?php

/*
Plugin Name: Wordpress Opauth
Plugin URI: http://www.ethymos.com.br
Description: Adds Opauth (opauth.org) support
Author: Ethymos
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

if (!file_exists(CONF_FILE))
{
	trigger_error(
			__('Configuration file missing at ' . CONF_FILE
				. '. Wordpress Opauth disabled.'), E_USER_NOTICE);
	return;
}

require_once CONF_FILE;
require_once OPAUTH_PATH . DIRECTORY_SEPARATOR . 'Opauth.php';
require_once WPOPAUTH_PATH . DIRECTORY_SEPARATOR . 'wpopauth.php';

$opauth = new WPOpauth($config);

?>
