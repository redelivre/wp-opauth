<?php

class WPOpauth
{
	private $opauth, $originalStrategies;

	public function __construct($config = array())
	{
		$this->originalStrategies = $config['Strategy'];
		/* Only OpenID is enabled by default as it doesn't need to be configured */
		$strategies = get_site_option('wp-opauth-strategies',
				array('OpenID' => array()));
		$salt = get_site_option('wp-opauth-salt');

		if ($salt === false)
		{
			$salt = self::generateRandomSalt();
			add_site_option('wp-opauth-salt', $salt);
		}

		$config['security_salt'] = $salt;
		$config['Strategy'] = $strategies;

		/* Set the state for multisite support */
		foreach ($config['Strategy'] as &$strategy)
		{
			$strategy['state'] = get_current_blog_id();
		}

		if (sizeof($config['Strategy']))
		{
			$this->opauth = new Opauth($config, false);
		}

		/* Show errors */
		if (array_key_exists('wp-opauth-errors', $_POST))
		{
			global $error;

			$errors = json_decode($_POST['wp-opauth-errors']);
			$error .= '<ul>';
			foreach ($errors as $e)
			{
				$error .= "<li>$e</li>";
			}
			$error .= '</ul>';
		}

		if (sizeof($config['Strategy']))
		{
			add_action('login_form', array($this, 'loginForm'));
			add_action('init', array($this, 'init'));
		}
		add_action('network_admin_menu', array($this, 'admin_menu'));
	}

	public function loginForm()
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
							. $this->opauth->config['callback_url'], $_POST);
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
					&& array_key_exists('state', $_GET))
			{
				$this->opauth->env['callback_url'] .= '?state=' . $_GET['state'];
			}

			/* Override host when not using openid to make multisite support easier
			 * to configure */
			$pos = strpos($params[0], '?');
			$strategy = ($pos === false? $params[0] : substr($params[0], 0, $pos));
			if ($strategy !== 'openid')
			{
				$this->opauth->env['host'] = preg_replace('/\/$/', '', network_site_url());
			}

			$this->opauth->run();
			die;
		}
	}

	private function callback()
	{
		if (!array_key_exists('opauth', $_POST))
		{
			return;
		}

		$response = unserialize(base64_decode($_POST['opauth']));

		if (array_key_exists('error', $response))
		{
			wp_redirect(wp_login_url());
			die;
		}

		if (!self::isInitialized())
		{
			self::createTables();
		}

		$uid = self::getUserID($response);

		if ($uid === null)
		{
			$uid = self::createUser($response);
		}

		if (is_wp_error($uid))
		{
			self::redirectWithPost(wp_login_url(),
					array('wp-opauth-errors'
						=> json_encode($uid->get_error_messages())));
			die;
		}

		self::loginAs($uid);

		wp_redirect(get_home_url());
	}

	private static function redirectWithPost($url, $post)
	{
		echo '<form action="' . htmlentities($url)
			. '" method="post" name="redirect_form">';
		foreach ($post as $name => $value)
		{
			echo '<input type="hidden" name="' . htmlentities($name)
				. '" value="' . htmlentities($value) . '">';
		}
		echo '</form>';
		echo '<script language="Javascript">';
		echo "document.redirect_form.submit()";
		echo "</script>";
	}

	private static function getUsername($name)
	{
		return substr(sanitize_user($name, true), 0, 16);
	}

	private static function getName($response)
	{
		if (array_key_exists('name', $response['auth']['info']))
		{
			return $response['auth']['info']['name'];
		}
		$name = '';
		if (array_key_exists('first_name', $response['auth']['info']))
		{
			$name = $response['auth']['info']['first_name'];
		}
		if (array_key_exists('last_name', $response['auth']['info']))
		{
			if ($name)
			{
				$name .= ' ';
			}
			$name .= $response['auth']['info']['last_name'];
		}

		return ($name? $name : __('Anonymous', 'wp-opauth'));
	}

	private static function createUser($response)
	{
		global $wpdb;

		$table = self::getUserTableName();

		$name = self::getName($response);
		$prefix = self::getUsername($name);
		$suffix = '';
		$username = '';

		do
		{
			$username = $prefix . $suffix++;
		} while (username_exists($username));

		$user = array();
		$user['user_login'] = $username;
		$user['first_name'] = $name;
		$user['user_pass'] = $response['signature'];
		if (array_key_exists('email', $response['auth']['info']))
		{
			$user['user_email'] = $response['auth']['info']['email'];
		}

		$uid = wp_insert_user($user);

		if (is_wp_error($uid))
		{
			return $uid;
		}

		$wpdb->insert($table,
				array(
					'provider' => $response['auth']['provider'],
					'remote_id' => $response['auth']['uid'],
					'local_id' => $uid
				)
		);

		return $uid;
	}

	private static function isInitialized()
	{
		global $wpdb;

		$table = self::getUserTableName();
		$query = $wpdb->prepare("SHOW TABLES LIKE %s;", $table);

		return ($wpdb->get_var($query) === null? false : true);
	}

	private static function createTables()
	{
		global $wpdb;

		$table = self::getUserTableName();
		$query = "CREATE TABLE $table (
				provider varchar(128) NOT NULL,
				remote_id varchar(128) NOT NULL,
				local_id int NOT NULL,
				PRIMARY KEY (provider, remote_id)
			);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($query);
	}

	private static function getUserID($response)
	{
		global $wpdb;

		$table = self::getUserTableName();
		$query = $wpdb->prepare(
				'SELECT local_id'
				. " FROM $table"
				. ' WHERE'
				. ' provider = %s'
				. ' AND'
				. ' remote_id = %s',
				$response['auth']['provider'], $response['auth']['uid']);

		return $wpdb->get_var($query);
	}

	private static function getUserTableName()
	{
		global $wpdb;
		return $wpdb->prefix . WPOPAUTH_USER_TABLE_NAME;
	}

	private static function loginAs($uid)
	{
		$user = wp_set_current_user($uid);
		wp_set_auth_cookie($user->ID);
		do_action('wp_login', $user->user_login);
	}

	public function admin_menu()
	{
		add_menu_page('Opauth Plugin Options',
				'Opauth',
				'manage_options',
				'wp-opauth',
				array($this, 'adminOptions'));
	}

	public function adminOptions()
	{
		$strategies = $this->originalStrategies;
		$values = $this->opauth->config['Strategy'];
		$callbackURLs = $this->loadCallbackURLs();


		if (!empty($_POST))
		{
			$this->saveStrategies($_POST);
			$values = get_site_option('wp-opauth-strategies');
		}

		require WPOPAUTH_PATH
			. DIRECTORY_SEPARATOR . 'views'
			. DIRECTORY_SEPARATOR . 'admin_page.php';
	}

	public function saveStrategies($candidate)
	{
		$strategies = array();

		foreach ($candidate as $id => $info)
		{
			/* Only store enabled strategies that are in the config file */
			if (array_key_exists($id, $this->originalStrategies)
					&& array_key_exists('enabled', $info))
			{
				$strategies[$id] = $candidate[$id];
				unset($strategies[$id]['enabled']);
			}
		}

		update_site_option('wp-opauth-strategies', $strategies);
	}

	public static function generateRandomSalt()
	{
		$alphabet = 'abcdefghijklmnopqrstuvwxyz';
		$alphabet .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$alphabet .= '0123456789';
		$length = mt_rand(64, 128);
		$salt = '';

		while ($length--) {
			$salt .= $alphabet[mt_rand(0, strlen($alphabet) - 1)];
		}

		return $salt;
	}

	private function loadCallbackURLs()
	{
		require WPOPAUTH_PATH . DIRECTORY_SEPARATOR . 'callbacksuffixes.php';

		$callbackURLs = array();
		foreach ($callbackSuffixes as $strategy => $suffix)
		{
			$callbackURLs[$strategy] =
				network_site_url($this->opauth->config['path'])
				. strtolower($strategy) . '/' . $suffix;
		}

		return $callbackURLs;
	}
}

?>
