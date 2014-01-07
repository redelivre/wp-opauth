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

/* This is not the right place for this. But we need the translated error
 * string */
load_plugin_textdomain('wp-opauth', false,
		dirname(plugin_basename(__FILE__)) . '/languages/');

if (!file_exists(CONF_FILE))
{
	trigger_error(sprintf(
				__('Configuration file missing at %s. Opauth disabled.', 'wp-opauth'),
				CONF_FILE),
			E_USER_NOTICE);
	return;
}

require_once CONF_FILE;
require_once OPAUTH_PATH . DIRECTORY_SEPARATOR . 'Opauth.php';
require_once WPOPAUTH_PATH . DIRECTORY_SEPARATOR . 'wpopauth.php';

$opauth = new WPOpauth($config);

?>
