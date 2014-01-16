<?php

class WPOpauth
{
	private $opauth,
					$originalStrategies,
					$areButtonsOutside,
					$originalPath,
					$networkCustomOpenID,
					$localCustomOpenIDEnabled,
					$localCustomOpenID;

	public function __construct($config = array())
	{
		$this->originalStrategies = $config['Strategy'];
		$this->originalPath = $config['path'];
		/* Only OpenID is enabled by default as it doesn't need to be configured */
		$strategies = get_site_option('wp-opauth-strategies',
				array('OpenID' => array()));
		$salt = get_site_option('wp-opauth-salt');
		$this->areButtonsOutside =
			get_site_option('wp-opauth-are-buttons-outside', true);
		$this->networkCustomOpenID =
			get_site_option('wp-opauth-network-custom-openid', array());
		$this->localCustomOpenIDEnabled =
			get_site_option('wp-opauth-local-custom-openid-enabled', true);
		$this->localCustomOpenID=
			get_option('wp-opauth-local-custom-openid', array());

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
		add_action('network_admin_menu', array($this, 'networkAdminMenu'));
		add_action('admin_menu', array($this, 'adminMenu'));
	}

	public function loginForm()
	{
		$strategies = $this->opauth->strategyMap;
		$networkCustomOpenID = (array_key_exists('openid', $strategies)?
				$this->networkCustomOpenID : array());
		$localCustomOpenID = (array_key_exists('openid', $strategies)?
				$this->localCustomOpenID : array());

		if ($this->areButtonsOutside)
		{
			wp_enqueue_script('wp-opauth-login-movebuttons',
					plugins_url('js/movebuttons.js', __FILE__));
		}
		wp_enqueue_style('wp-opauth-login',
				plugins_url('css/login.css', __FILE__));

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
				$this->opauth->env['host'] = preg_replace('/\/$/', '',
						network_site_url());
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

		/* Check if the user was deleted */
		if ($uid === null || get_userdata($uid) === false)
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

	public static function redirectWithPost($url, $post)
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
		/* The email has to be set. Otherwise users get incoherent error
		 * messages when trying to update their profile. */
		$user['user_email'] =
			(array_key_exists('email', $response['auth']['info'])
			 && $response['auth']['info']['email']?
			 $response['auth']['info']['email'] :
			 WPOPAUTH_INVALID_EMAIL . $username);

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

	public function networkAdminMenu()
	{
		add_menu_page('Opauth Plugin Options',
				'Opauth',
				'manage_options',
				'wp-opauth',
				array($this, 'networkAdminOptions'));
	}

	public function adminMenu()
	{
		if ($this->localCustomOpenIDEnabled)
		{
			add_menu_page('Opauth Plugin Options',
					'Opauth',
					'manage_options',
					'wp-opauth',
					array($this, 'adminOptions'));
		}
	}

	public function adminOptions()
	{
		global $errors;
		$customOpenID = $this->localCustomOpenID;

		if (!empty($_POST))
		{
			$this->saveLocalSettings($_POST);
			$customOpenID =
				get_option('wp-opauth-local-custom-openid', array());
		}

		wp_enqueue_style('wp-opauth-admin',
				plugins_url('css/admin.css', __FILE__));
		wp_enqueue_script('wp-opauth-custom-openid',
				plugins_url('js/customopenid.js', __FILE__));
		wp_localize_script('wp-opauth-custom-openid', 'i18n',
				array(
					'defaultURL' => __("Login URL", 'wp-opauth'),
					'defaultIconURL' => site_url(DEFAULT_OPENID_ICON),
					'remove' => __("Remove", 'wp-opauth')));

		require WPOPAUTH_PATH
			. DIRECTORY_SEPARATOR . 'views'
			. DIRECTORY_SEPARATOR . 'admin_page.php';
	}

	public function networkAdminOptions()
	{
		global $errors;
		$strategies = $this->originalStrategies;
		$values = (isset($this->opauth)?
				$this->opauth->config['Strategy'] : array());
		$callbackURLs = $this->loadCallbackURLs();
		$areButtonsOutside = $this->areButtonsOutside;
		$customOpenID = $this->networkCustomOpenID;
		$localCustomOpenIDEnabled = $this->localCustomOpenIDEnabled;

		if (!empty($_POST))
		{
			$this->saveNetworkSettings($_POST);
			$values = get_site_option('wp-opauth-strategies');
			$areButtonsOutside = get_site_option('wp-opauth-are-buttons-outside');
			$customOpenID =
				get_site_option('wp-opauth-network-custom-openid', array());
			$localCustomOpenIDEnabled =
				get_site_option('wp-opauth-local-custom-openid-enabled', true);
		}

		wp_enqueue_style('wp-opauth-admin',
				plugins_url('css/admin.css', __FILE__));
		wp_enqueue_script('wp-opauth-custom-openid',
				plugins_url('js/customopenid.js', __FILE__));
		wp_localize_script('wp-opauth-custom-openid', 'i18n',
				array(
					'defaultURL' => __("Login URL", 'wp-opauth'),
					'defaultIconURL' => site_url(DEFAULT_OPENID_ICON),
					'remove' => __("Remove", 'wp-opauth')));

		require WPOPAUTH_PATH
			. DIRECTORY_SEPARATOR . 'views'
			. DIRECTORY_SEPARATOR . 'network_admin_page.php';
	}

	public function saveNetworkSettings($candidate)
	{
		$strategies = array();
		$customOpenID = array();
		/* An empty string because add_site_option fails if the value is false */
		$areButtonsOutside =
			(array_key_exists('areButtonsOutside', $candidate)? true : '');
		$localCustomOpenIDEnabled =
			(array_key_exists('localCustomOpenIDEnabled', $candidate)? true : '');
		$uploadDir = wp_upload_dir();
		$baseUploadDir = $uploadDir['basedir'] . DIRECTORY_SEPARATOR . 'wp-opauth';
		$baseUploadURL = $uploadDir['baseurl'] . '/wp-opauth';

		self::checkAndCreateDir($baseUploadDir, 0755);

		if (array_key_exists('customopenid', $candidate))
		{
			foreach ($candidate['customopenid'] as $id => $info)
			{
				$customOpenID[$id]['url'] = (string) $info['url'];
				if (self::validateUploadedIcon('customopenid', $id))
				{
					$filePath = $baseUploadDir . DIRECTORY_SEPARATOR
						. sanitize_file_name("$id.png");
					move_uploaded_file($_FILES['customopenid']['tmp_name'][$id]['icon'],
							$filePath);
					$fileURL = $baseUploadURL . '/' . sanitize_file_name("$id.png");
					$customOpenID[$id]['icon'] = substr($fileURL, strlen(site_url()));
				}
				else
				{
					$customOpenID[$id]['icon'] =
						(array_key_exists($id, $this->networkCustomOpenID)?
							$this->networkCustomOpenID[$id]['icon'] :
							null);
				}
			}
		}
		if (array_key_exists('strategies', $candidate))
		{
			foreach ($candidate['strategies'] as $id => $info)
			{
				/* Only store enabled strategies that are in the config file */
				if (array_key_exists($id, $this->originalStrategies)
						&& array_key_exists('enabled', $info))
				{
					$strategies[$id] = array();
					/* Only set the keys that are present in the filee */
					foreach ($info as $key => $v)
					{
						if (array_key_exists($key, $this->originalStrategies[$id]))
						{
							$strategies[$id][$key] = $v;
						}
					}
				}
			}
		}

		/* Delete the old icons */
		$oldIcons = array_diff_key($this->networkCustomOpenID, $customOpenID);
		foreach ($oldIcons as $info)
		{
			if ($info['icon'] !== null)
			{
				$filename = end(explode('/', $info['icon']));
				unlink($baseUploadDir . DIRECTORY_SEPARATOR . $filename);
			}
		}

		update_site_option('wp-opauth-network-custom-openid', $customOpenID);
		update_site_option('wp-opauth-strategies', $strategies);
		update_site_option('wp-opauth-are-buttons-outside', $areButtonsOutside);
		update_site_option('wp-opauth-local-custom-openid-enabled',
				$localCustomOpenIDEnabled);
	}

	public function saveLocalSettings($candidate)
	{
		$customOpenID = array();
		$uploadDir = wp_upload_dir();
		$baseUploadDir = $uploadDir['basedir'] . DIRECTORY_SEPARATOR . 'wp-opauth';
		$baseUploadURL = $uploadDir['baseurl'] . '/wp-opauth';

		self::checkAndCreateDir($baseUploadDir, 0755);

		if (array_key_exists('customopenid', $candidate))
		{
			foreach ($candidate['customopenid'] as $id => $info)
			{
				$customOpenID[$id]['url'] = (string) $info['url'];
				if (self::validateUploadedIcon('customopenid', $id))
				{
					$filePath = $baseUploadDir . DIRECTORY_SEPARATOR
						. sanitize_file_name("$id.png");
					move_uploaded_file($_FILES['customopenid']['tmp_name'][$id]['icon'],
							$filePath);
					$fileURL = $baseUploadURL . '/' . sanitize_file_name("$id.png");
					$customOpenID[$id]['icon'] = substr($fileURL, strlen(site_url()));
				}
				else
				{
					$customOpenID[$id]['icon'] =
						(array_key_exists($id, $this->localCustomOpenID)?
							$this->localCustomOpenID[$id]['icon'] :
							null);
				}
			}
		}

		/* Delete the old icons */
		$oldIcons = array_diff_key($this->localCustomOpenID, $customOpenID);
		foreach ($oldIcons as $info)
		{
			if ($info['icon'] !== null)
			{
				$filename = end(explode('/', $info['icon']));
				unlink($baseUploadDir . DIRECTORY_SEPARATOR . $filename);
			}
		}

		update_option('wp-opauth-local-custom-openid', $customOpenID);
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
				network_site_url($this->originalPath)
				. strtolower($strategy) . '/' . $suffix;
		}

		return $callbackURLs;
	}

	private static function validateUploadedIcon($key, $name)
	{
		global $errors;

		if (!array_key_exists($key, $_FILES))
		{
			return false;
		}

		/* Check for errors and error corruption attacks */
		if (!array_key_exists('error', $_FILES[$key])
				|| !array_key_exists($name, $_FILES[$key]['error'])
				|| !array_key_exists('icon', $_FILES[$key]['error'][$name])
				|| $_FILES[$key]['error'][$name]['icon'] !== UPLOAD_ERR_OK)
		{
			return false;
		}

		/* Make sure the file is not too big */
		if ($_FILES[$key]['size'][$name]['icon'] > MAXIMUM_ICON_SIZE)
		{
			$errors .= sprintf(__('The uploaded file is larger than %d bytes.',
						'wp-opauth'), MAXIMUM_ICON_SIZE);

			return false;
		}

		/* Only png files */
		if (exif_imagetype($_FILES[$key]['tmp_name'][$name]['icon'])
				!== IMAGETYPE_PNG)
		{
			$errors .= __('The uploaded file is not a valid png file.', 'wp-opauth');

			return false;
		}

		return true;
	}

	private static function checkAndCreateDir($path, $permissions)
	{
		if (!file_exists($path) && !is_dir($path))
		{
			mkdir($path, $permissions, true);
		}
	}
}

?>
