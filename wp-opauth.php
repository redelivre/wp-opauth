<?php
	/*
	Plugin Name: Wordpress Opauth
	Plugin URI: http://www.ethymos.com.br
	Description: Adds Opauth (opauth.org) support
	Author: Ethymos
	Version: 0.01
	Text Domain: wp-opauth
	*/

	function wp_opauth_login_form()
	{
		require dirname(__FILE__)
			. DIRECTORY_SEPARATOR . 'views'
			. DIRECTORY_SEPARATOR . 'login_form.php';
	}

	add_action('login_form', 'wp_opauth_login_form');
?>
