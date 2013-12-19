<?php

class WPOpauth
{
	private $opauth;

	public function __construct($config = array())
	{
		$this->opauth = new Opauth($config, false);
		add_action('login_form', array($this, 'login_form'));
	}

	public function login_form()
	{
		$strategies = $this->opauth->strategyMap;

		require WPOPAUTH_PATH
			. DIRECTORY_SEPARATOR . 'views'
			. DIRECTORY_SEPARATOR . 'login_form.php';
	}
}

?>
