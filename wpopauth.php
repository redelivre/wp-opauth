<?php

class WPOpauth
{
	private $opauth,
					$originalStrategies,
					$areButtonsOutside,
					$originalPath,
					$networkCustomOpenID,
					$localCustomOpenIDEnabled,
					$localCustomOpenID,
					$emailNewAccounts,
					$allowDisabling,
					$strategies,
					$disabledStrategies;

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
		$this->localCustomOpenID =
			($this->localCustomOpenIDEnabled?
			 get_option('wp-opauth-local-custom-openid', array()) :
			 array());
		$this->emailNewAccounts =
			get_site_option('wp-opauth-email-new-accounts', true);
		$this->allowDisabling =
			get_site_option('wp-opauth-allow-disabling', true);
		$this->disabledStrategies =
			get_option('wp-opauth-disabled-strategies', array());

		if (!$this->allowDisabling)
		{
			$this->disabledStrategies = array();
		}

		if ($salt === false)
		{
			$salt = self::generateRandomSalt(64, 128);
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

		/* Priorities are: local > network > default */
		$this->networkStrategies = array_merge($this->opauth->strategyMap,
				$this->networkCustomOpenID);
		/* Unset the disabled strategies */
		$this->opauth->strategyMap =
			array_diff_key($this->opauth->strategyMap, $this->disabledStrategies);

		if (sizeof($config['Strategy']))
		{
			add_action('login_form', array($this, 'loginForm'));
			add_action('init', array($this, 'init'));
		}
		add_action('network_admin_menu', array($this, 'networkAdminMenu'));
		add_action('admin_menu', array($this, 'adminMenu'));
		add_action('login_init', array($this, 'loginInit'));
	}

	public function loginInit()
	{
		/* Handle custom openid providers */
		if (array_key_exists('openidurl', $_GET))
		{
			self::handleOpenIDRedirection();
			die;
		}

		/* Show errors */
		if (array_key_exists('wp-opauth-errors', $_POST))
		{
			global $error;

			$errors = json_decode(stripslashes($_POST['wp-opauth-errors']));
			$error .= '<ul>';
			foreach ($errors as $e)
			{
				$error .= "<li>$e</li>";
			}
			$error .= '</ul>';
		}

		if (array_key_exists('redirect_to', $_REQUEST))
		{
			if (!isset($_SESSION))
			{
				session_start();
			}
			$_SESSION['wp-opauth-redirect'] = $_REQUEST['redirect_to'];
		}
	}

	public function loginForm()
	{
		$strategies = $this->getStrategies();

		if ($this->areButtonsOutside)
		{
			wp_enqueue_script('wp-opauth-login-movebuttons',
					plugins_url('js/movebuttons.js', __FILE__));
		}
		wp_enqueue_script('wp-opauth-login-openid-form',
				plugins_url('js/openidform.js', __FILE__));
		wp_enqueue_style('wp-opauth-login',
				plugins_url('css/login.css', __FILE__));

		require WPOPAUTH_PATH
			. DIRECTORY_SEPARATOR . 'views'
			. DIRECTORY_SEPARATOR . 'login_form.php';
	}

	public function init()
	{
		/* Redirection */
		if (preg_match('/^' . preg_quote($this->opauth->config['path'], '/')
					. WPOPAUTH_REDIRECT_STRATEGY . '$/',
					$_SERVER['REQUEST_URI']))
		{
			if (!isset($_SESSION))
			{
				session_start();
			}

			$redirectURL = (array_key_exists('wp-opauth-redirect', $_SESSION)?
					$_SESSION['wp-opauth-redirect'] : admin_url());

			wp_redirect($redirectURL);
			die;
		}
		/* Callback */
		else if (preg_match('/^'
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
			$uid = $this->createUser($response);
		}

		if (is_wp_error($uid))
		{
			self::redirectWithPost(wp_login_url(),
					array('wp-opauth-errors'
						=> json_encode($uid->get_error_messages())));
			die;
		}

		self::loginAs($uid);

		wp_redirect(
				site_url($this->opauth->config['path'] . WPOPAUTH_REDIRECT_STRATEGY));
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

		if (!$name && array_key_exists('nickname', $response['auth']['info']))
		{
			$name = $response['auth']['info']['nickname'];
		}

		return ($name? $name : __('Anonymous', 'wp-opauth'));
	}

	private function createUser($response)
	{
		global $wpdb;

		if (!get_option('users_can_register'))
		{
			return new WP_Error(1, __('User registration is disabled', 'wp-opauth'));
		}

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
		$user['user_pass'] = self::generateRandomSalt(12, 16);
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

		if ($this->emailNewAccounts)
		{
			self::emailUserInformation($user);
		}

		$wpdb->replace($table,
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
		add_menu_page(__('Opauth', 'wp-opauth'),
				__('Opauth', 'wp-opauth'),
				'manage_network_options',
				'wp-opauth');
		add_submenu_page('wp-opauth',
				__('Opauth Plugin Options', 'wp-opauth'),
				__('Settings', 'wp-opauth'),
				'manage_network_options',
				'wp-opauth',
				array($this, 'networkAdminOptions'));
		add_submenu_page('wp-opauth',
				__('Opauth User List', 'wp-opauth'),
				__('Users', 'wp-opauth'),
				'manage_network_options',
				'wp-opauth-users',
				array(get_class(), 'showUsers'));
	}

	public function adminMenu()
	{
		if ($this->localCustomOpenIDEnabled)
		{
			add_options_page('Opauth Plugin Options',
					'Opauth',
					'manage_options',
					'wp-opauth',
					array($this, 'adminOptions'));
		}
	}

	public function adminOptions()
	{
		global $errors, $success;
		$customOpenID = $this->localCustomOpenID;
		$allowDisabling = $this->allowDisabling;
		$disabledStrategies = $this->disabledStrategies;
		$strategies = $this->networkStrategies;

		if (!empty($_POST))
		{
			$this->saveLocalSettings($_POST);
			$customOpenID =
				get_option('wp-opauth-local-custom-openid', array());
			$success = __('Settings updated successfully', 'wp-opauth');
			$disabledStrategies =
				get_option('wp-opauth-disabled-strategies', array());
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
		$emailNewAccounts = $this->emailNewAccounts;
		$allowDisabling = $this->allowDisabling;

		if (!empty($_POST))
		{
			$this->saveNetworkSettings($_POST);
			$values = get_site_option('wp-opauth-strategies');
			$areButtonsOutside =
				get_site_option('wp-opauth-are-buttons-outside', true);
			$customOpenID =
				get_site_option('wp-opauth-network-custom-openid', array());
			$localCustomOpenIDEnabled =
				get_site_option('wp-opauth-local-custom-openid-enabled', true);
			$emailNewAccounts =
				get_site_option('wp-opauth-email-new-accounts', true);
			$allowDisabling =
				get_site_option('wp-opauth-allow-disabling', true);
			$success = __('Settings updated successfully', 'wp-opauth');
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
		$emailNewAccounts =
			(array_key_exists('emailNewAccounts', $candidate)? true : '');
		$allowDisabling =
			(array_key_exists('allowDisabling', $candidate)? true : '');
		$uploadDir = wp_upload_dir();
		$baseUploadDir = $uploadDir['basedir'] . DIRECTORY_SEPARATOR . 'wp-opauth';
		$baseUploadURL = $uploadDir['baseurl'] . '/wp-opauth';

		self::checkAndCreateDir($baseUploadDir, 0755);

		if (array_key_exists('customopenid', $candidate))
		{
			/* openid is reserved */
			unset($candidate['customopenid']['openid']);
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
		update_site_option('wp-opauth-email-new-accounts', $emailNewAccounts);
		update_site_option('wp-opauth-allow-disabling', $allowDisabling);
	}

	public function saveLocalSettings($candidate)
	{
		$customOpenID = array();
		$uploadDir = wp_upload_dir();
		$baseUploadDir = $uploadDir['basedir'] . DIRECTORY_SEPARATOR . 'wp-opauth';
		$baseUploadURL = $uploadDir['baseurl'] . '/wp-opauth';
		$disabledStrategies = array();

		self::checkAndCreateDir($baseUploadDir, 0755);

		if (array_key_exists('customopenid', $candidate))
		{
			/* openid is reserved */
			unset($candidate['customopenid']['openid']);
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

		/* Disabled network strategies */
		if (array_key_exists('enabled', $_POST))
		{
			foreach ($this->networkStrategies as $id => $info)
			{
				if (!array_key_exists($id, $_POST['enabled']))
				{
					/* The key makes coding everything else easier */
					$disabledStrategies[$id] = null;
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
		update_option('wp-opauth-disabled-strategies', $disabledStrategies);
	}

	public static function generateRandomSalt($min, $max)
	{
		$alphabet = 'abcdefghijklmnopqrstuvwxyz';
		$alphabet .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$alphabet .= '0123456789';
		$length = mt_rand($min, $max);
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

	private static function emailUserInformation($user)
	{
		$message = '<p>';
		$message .= sprintf(__('Hello %s,', 'wp-opauth'), $user['first_name']);
		$message .= '</p>';
		$message .= '<p>';
		$message .=__('Your wordpress account was created.', 'wp-opauth');
		$message .= '</p>';
		$message .= '<p>';
		$message .= __('Username:', 'wp-opauth') . ' ' .  $user['user_login'];
		$message .= '</p>';
		$message .= '<p>';
		$message .= __('Password:', 'wp-opauth') . ' ' .  $user['user_pass'];
		$message .= '</p>';

		wp_mail($user['user_email'],
				__('Wordpress Account Creation', 'wp-opauth'),
				$message);
	}

	static private function getCustomOpenIDVariables($string)
	{
		preg_match_all('/%([^%]*)%/', $string, $matches);

		return $matches[1];
	}

	static private function updateCustomOpenIDVariablesFromPost($variables)
	{
		$values = array();

		foreach ($variables as $v)
		{
			$sv = sanitize_html_class($v);
			$values[$v] = (array_key_exists($sv, $_POST)? $_POST[$sv] : '');
		}

		return $values;
	}

	static private function isCustomOpenIDFormFilledOut($values)
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

	static private function replaceCustomOpenIDVariables($url, $values)
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

	static private function handleOpenIDRedirection()
	{
		$url = (array_key_exists('openidurl', $_GET)? $_GET['openidurl'] : '');
		$name = (array_key_exists('openidname', $_GET)? $_GET['openidname'] : '');
		$values = self::updateCustomOpenIDVariablesFromPost(
				self::getCustomOpenIDVariables($url));

		if (self::isCustomOpenIDFormFilledOut($values))
		{
			$url = self::replaceCustomOpenIDVariables($url, $values);
			self::redirectWithPost(plugins_url('auth/openid', __FILE__),
					array('openid_url' => $url));
			die;
		}

		/* The form is not filled out, show it */

		require WPOPAUTH_PATH . DIRECTORY_SEPARATOR . 'views'
			. DIRECTORY_SEPARATOR .  'openid_variables.php';
	}

	private function getStrategies()
	{
		/* Priorities are: local > network > default */
		$network =
			array_diff_key($this->networkStrategies, $this->disabledStrategies);
		return array_merge($network, $this->localCustomOpenID);
	}

	static private function getUsers()
	{
		global $wpdb;

		$table = self::getUserTableName();
		$query = "SELECT * FROM $table";

		$users = array();
		foreach ($wpdb->get_results($query, ARRAY_A) as $user)
		{
			$data = get_userdata($user['local_id']);

			if ($data !== false)
			{
				$user['display_name'] = $data->data->display_name;
				$users[] = $user;
			}
		}

		usort($users, function ($a, $b)
		{
			return strcasecmp($a['display_name'], $b['display_name']);
		});

		return $users;
	}

	public static function showUsers()
	{
		$users = self::getUsers();

		require WPOPAUTH_PATH
			. DIRECTORY_SEPARATOR . 'views'
			. DIRECTORY_SEPARATOR . 'user_list.php';
	}
}

?>
