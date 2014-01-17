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
		$sv = sanitize_html_class($v);
		$values[$v] = (array_key_exists($sv, $_POST)? $_POST[$sv] : '');
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

$url = (array_key_exists('openidurl', $_GET)? $_GET['openidurl'] : '');
$name = (array_key_exists('openidname', $_GET)? $_GET['openidname'] : '');
$values = updateFromPost(getVariables($url));

if (isFormFilledUp($values))
{
	$url = replaceVariables($url, $values);
	WPOpauth::redirectWithPost(plugins_url('auth/openid', __FILE__),
			array('openid_url' => $url));
	die;
}

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views'
	. DIRECTORY_SEPARATOR .  'openid_variables.php';

?>
