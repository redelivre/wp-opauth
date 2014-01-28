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

define('WPOPAUTH_CONF_FILE',
		dirname(__FILE__) . DIRECTORY_SEPARATOR . 'opauth.conf.php');
define('WPOPAUTH_OPAUTH_PATH',
		dirname(__FILE__)
		. DIRECTORY_SEPARATOR . 'opauth'
		. DIRECTORY_SEPARATOR . 'lib'
		. DIRECTORY_SEPARATOR . 'Opauth');
define('WPOPAUTH_PATH', dirname(__FILE__));
define('WPOPAUTH_USER_TABLE_NAME', 'wpopauth_users');
define('WPOPAUTH_OPAUTH_CLASS_FILE',
		WPOPAUTH_OPAUTH_PATH . DIRECTORY_SEPARATOR . 'Opauth.php');
define('WPOPAUTH_DEFAULT_OPENID_ICON',
		substr(plugins_url('favicons/openid.png', __FILE__), strlen(site_url())));
/* 4 kilobytes should be enough for tiny 16x16 icons */
define('WPOPAUTH_MAXIMUM_ICON_SIZE', 1 << 12);
define('WPOPAUTH_INVALID_EMAIL', 'noemail@example.com');
define('WPOPAUTH_REDIRECT_STRATEGY', '_redirect');

/* This is not the best place for loading the translations. But we need for the
 * error message below. */
load_plugin_textdomain('wp-opauth', false,
		dirname(plugin_basename(__FILE__)) . '/languages/');
if (!file_exists(WPOPAUTH_OPAUTH_CLASS_FILE))
{
	trigger_error(__('The Opauth class file was not found.', 'wp-opauth') . ' '
			. __('Did you download the submodules?', 'wp-opauth') . ' '
			. __('Read the README file for more information', 'wp-opauth'),
			E_USER_NOTICE);
	return;
}

require_once WPOPAUTH_CONF_FILE;
require_once WPOPAUTH_OPAUTH_CLASS_FILE;
require_once WPOPAUTH_PATH . DIRECTORY_SEPARATOR . 'wpopauth.php';

$opauth = new WPOpauth($config);

?>
