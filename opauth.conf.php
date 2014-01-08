<?php
/* This file is not meant for editing unless you're trying to add strategies
 * that don't come prepackaged. Do not edit the other options for configuration
 * purposes. */

$config = array(
	'path' => preg_replace('/^' . preg_quote(site_url(), '/') . '/',
			'', WP_PLUGIN_URL) . "/wp-opauth/auth/",
	'callback_url' => preg_replace('/^' . preg_quote(site_url(), '/') . '/',
			'', WP_PLUGIN_URL) . "/wp-opauth/auth/callback.php",
	'callback_transport' => 'post',
	'strategy_dir' => WPOPAUTH_PATH . DIRECTORY_SEPARATOR . 'strategies',
	
	/* Generated and stored in the DB */
	'security_salt' => '',
	
	/* Values defined as null will be configurable through the admin panel */
	'Strategy' => array(
		'GitHub' => array(
			'client_id' => null,
			'client_secret' => null
		),
		'Yahoojp' => array(
			'client_id' => null,
			'client_secret' => null
		),
		'Foursquare' => array(
			'client_id' => null,
			'client_secret' => null
		),
		'OpenID' => array(),
		'Mixi' => array(
			'client_id' => null,
			'client_secret' => null
		),
		'Bitbucket' => array(
			'key' => null,
			'secret' => null
		),
		'Twitter' => array(
			'key' => null,
			'secret' => null
		),
		'Google' => array(
			'client_id' => null,
			'client_secret' => null
		),
		'SinaWeibo' => array(
			'key' => null,
			'secret' => null
		),
		'Disqus' => array(
			'api_key'    => null,
			'api_secret' => null,
		),
		'Instagram' => array(
			'client_id' => null,
			'client_secret' => null
		),
		'Live' => array(
			'client_id' => null,
			'client_secret' => null
		),
		'Facebook' => array(
			'app_id' => null,
			'app_secret' => null
		),
		'Flickr' => array(
			'key' => null,
			'secret' => null
		),
		'Do' => array(
			'client_id' => null,
			'client_secret' => null
		),
		'VKontakte' => array(
			'app_id' => null,
			'app_secret' => null
		),
		'LinkedIn' => array(
			'api_key' => null,
			'secret_key' => null
		),
		'Behance' => array(
			'client_id' => null,
			'client_secret' => null
		),
		'Bitly' => array(
			'client_id' => null,
			'client_secret' => null
		),
		'Evernote' => array(
			'client_id' => null,
			'client_secret' => null,
			'sandbox' => false,
		),
		'Tumblr' => array(
			'consumer_key' => null,
			'consumer_secret' => null
		),
		'Vimeo' => array(
			'key' => null,
			'secret' => null
		),
	),
);
