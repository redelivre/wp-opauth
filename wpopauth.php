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
		/* Run the callback */
		$callbackURI = str_replace('{path}',
				$this->opauth->config['path'],
				$this->opauth->config['callback_url']);
		if ($_SERVER['REQUEST_URI'] === $callbackURI)
		{
			$this->callback();
			exit;
		}
		/* Run opauth only when in the right path. */
		else if (preg_match(
					'/^' . preg_quote($this->opauth->config['path'], '/') . '/',
					$_SERVER['REQUEST_URI']))
		{
			$this->opauth->run();
		}
	}

	public function callback()
	{
		$response = null;

		switch($this->opauth->env['callback_transport']) {
			case 'session':
				session_start();
				if (array_key_exists('opauth', $_SESSION))
				{
					$response = $_SESSION['opauth'];
					unset($_SESSION['opauth']);
				}
				break;
			case 'post':
				$response = unserialize(base64_decode($_POST['opauth']));
				break;
			case 'get':
				$response = unserialize(base64_decode($_GET['opauth']));
				break;
		}

		var_dump($response);
	}
}

?>
