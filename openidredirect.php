<?php

function getVariables($string)
{
	preg_match_all('/%([^%]*)%/', $string, $matches);

	return $matches[1];
}

function updateFromPost($variables)
{
	$values = array();

	foreach ($variables as $v)
	{
		$values[$v] = (array_key_exists($v, $_POST)? $_POST[$v] : '');
	}

	return $values;
}

function isFormFilledUp($values)
{
	foreach ($values as $v)
	{
		if (trim($v) === '')
		{
			return false;
		}
	}

	return true;
}

function replaceVariables($url, $values)
{
	$search = array();
	$replace = array();

	foreach ($values as $key => $v)
	{
		$search[] = '%' . $key . '%';
		$replace[] = $v;
	}

	return str_replace($search, $replace, $url);
}

$url = (array_key_exists('url', $_GET)? $_GET['url'] : '');
$name = (array_key_exists('name', $_GET)? $_GET['name'] : '');
$values = updateFromPost(getVariables($url));

if (isFormFilledUp($values))
{
	require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wpopauth.php';

	$url = replaceVariables($url, $values);
	WPOpauth::redirectWithPost('auth/openid',
			array('openid_url' => $url));
	die;
}

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views'
	. DIRECTORY_SEPARATOR .  'openid_variables.php';

?>
