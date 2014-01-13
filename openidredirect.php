<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wpopauth.php';

WPOpauth::redirectWithPost('auth/openid',
		array('openid_url' => array_key_exists('url', $_GET)? $_GET['url'] : ''));
?>
