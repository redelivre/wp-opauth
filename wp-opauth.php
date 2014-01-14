<?php

/*
Plugin Name: Opauth
Plugin URI: http://labculturadigital.org/
Description: Adds Opauth (opauth.org) support
Author: Laboratório de Cultura Digital - Flávio Zavan
Version: 0.01
Text Domain: wp-opauth
Network: true
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
define('OPAUTH_CLASS_FILE', OPAUTH_PATH . DIRECTORY_SEPARATOR . 'Opauth.php');
define('DEFAULT_OPENID_ICON',
		substr(plugins_url('favicons/openid.png', __FILE__), strlen(site_url())));

/* This is not the best place for loading the translations. But we need for the
 * error message below. */
load_plugin_textdomain('wp-opauth', false,
		dirname(plugin_basename(__FILE__)) . '/languages/');
if (!file_exists(OPAUTH_CLASS_FILE))
{
	trigger_error(__('The Opauth class file was not found.', 'wp-opauth') . ' '
			. __('Did you download the submodules?', 'wp-opauth') . ' '
			. __('Read the README file for more information', 'wp-opauth'),
			E_USER_NOTICE);
	return;
}

require_once CONF_FILE;
require_once OPAUTH_CLASS_FILE;
require_once WPOPAUTH_PATH . DIRECTORY_SEPARATOR . 'wpopauth.php';

$opauth = new WPOpauth($config);

?>
