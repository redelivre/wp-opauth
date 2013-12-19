<?php

class WPOpauth
{
	public static function login_form()
	{
		require dirname(__FILE__)
			. DIRECTORY_SEPARATOR . 'views'
			. DIRECTORY_SEPARATOR . 'login_form.php';
	}
}

?>
