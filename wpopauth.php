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

		self::loginAs($uid);

		wp_redirect(get_home_url());
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

	private static function getUsername($response)
	{
		return substr(sanitize_user($response['auth']['info']['name'], true),
				0, 16);
	}

	private static function createUser($response)
	{
		global $wpdb;

		$table = self::getUserTableName();

		$prefix = self::getUsername($response);
		$suffix = '';
		$username = '';

		do
		{
			$username = $prefix . $suffix++;
		} while (username_exists($username));

		$user = array();
		$user['user_login'] = $username;
		$user['first_name'] = $response['auth']['info']['name'];
		$user['user_pass'] = $response['signature'];
		if (array_key_exists('email', $response['auth']['info']))
		{
			$user['user_email'] = $response['auth']['info']['email'];
		}

		$uid = wp_insert_user($user);

		if (is_wp_error($uid))
		{
			return null;
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
}

?>
