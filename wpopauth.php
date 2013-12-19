<?php

class WPOpauth
{
	private $opauth;

	public function __construct($config = array())
	{
		$this->opauth = new Opauth($config, false);
		add_action('login_form', array($this, 'login_form'));
		add_action('template_redirect', array($this, 'template_redirect'));
	}

	public function login_form()
	{
		$strategies = $this->opauth->strategyMap;

		require WPOPAUTH_PATH
			. DIRECTORY_SEPARATOR . 'views'
			. DIRECTORY_SEPARATOR . 'login_form.php';
	}

	public function template_redirect()
	{
		/* Run opauth only when in the right path. */
		if (preg_match(
					'/^' . preg_quote($this->opauth->config['path'], '/') . '/',
					$_SERVER['REQUEST_URI']))
		{
			$this->opauth->run();
		}
	}
}

?>
