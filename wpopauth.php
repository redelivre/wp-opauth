<?php

class WPOpauth
{
	private $opauth;

	public function __construct($config = array())
	{
		/* Override Opauth's path, callback_url and callback_transport variables */
		$config['path'] = preg_replace('/^' . preg_quote(site_url(), '/') . '/',
				'', WP_PLUGIN_URL) . "/wp-opauth/auth/";
		$config['callback_url'] = $config['path'] . 'callback.php';
		$config['callback_transport'] = 'post';

		/* Set the state for multisite support */
		foreach ($config['Strategy'] as &$strategy)
		{
			$strategy['state'] = get_current_blog_id();
		}

		$this->opauth = new Opauth($config, false);
		/* Override host to enable multisite support */
		$this->opauth->env['host'] = preg_replace('/\/$/', '', network_site_url());
		add_action('login_form', array($this, 'loginForm'));
		add_action('init', array($this, 'init'));
	}

	private function loginForm()
	{
		$strategies = $this->opauth->strategyMap;

		require WPOPAUTH_PATH
			. DIRECTORY_SEPARATOR . 'views'
			. DIRECTORY_SEPARATOR . 'login_form.php';
	}

	public function init()
	{
		/* Run the callback */
		if (preg_match( '/^'
					. preg_quote($this->opauth->config['callback_url'], '/')
					. '(\?[^\/]*)?$/',
					$_SERVER['REQUEST_URI']))
		{
			if (array_key_exists('state', $_GET)
					&& $_GET['state'] != get_current_blog_id())
			{
				$details = get_blog_details($_GET['state']);
				if ($details !== false)
				{
					self::redirectWithPost($details->siteurl
							. $this->opauth->config['callback_url']);
					die;
				}
			}
			$this->callback();
			die;
		}
		/* Run opauth only when in the right path. */
		else if (preg_match(
					'/^' . preg_quote($this->opauth->config['path'], '/') . '(.*)/',
					$_SERVER['REQUEST_URI'], $matches))
		{
			/* Intercept callback GET to pass state to the generic callback */
			$params = explode('/', $matches[1]);
			if (sizeof($params) >= 2
					&& !empty($params[0]) && !empty($params[1])
					&& array_key_exists('state', $_GET)) {
				$this->opauth->env['callback_url'] .= '?state=' . $_GET['state'];
			}

			$this->opauth->run();
			die;
		}
	}

	private function callback()
	{
		$response = unserialize(base64_decode($_POST['opauth']));

		var_dump($response);
	}

	private static function redirectWithPost($url)
	{
		echo '<form action="' . htmlentities($url)
			. '" method="post" name="redirect_form">';
		foreach ($_POST as $name => $value)
		{
			echo '<input type="hidden" name="' . htmlentities($name)
				. '" value="' . htmlentities($value) . '">';
		}
		echo '</form>';
		echo '<script language="Javascript">';
		echo "document.redirect_form.submit()";
		echo "</script>";
	}
}

?>
