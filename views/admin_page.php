<h1><?php _e('Opauth', 'wp-opauth'); ?></h1>

<?php
	if (!empty($errors))
	{
		?>
		<p class="wp-opauth-error-message">
			<?php echo $errors; ?>
		</p>
		<?php
	}
	if (!empty($success))
	{
		?>
		<p class="wp-opauth-success-message">
			<?php echo $success; ?>
		</p>
		<?php
	}
?>

<form id="opauth-config" method="post" enctype="multipart/form-data">
	<h2><?php _e('Custom openid providers', 'wp-opauth'); ?></h2>
	<p><i><?php
		_e('Requires the openid strategy to be enabled in the network',
				'wp-opauth');
	?></i></p>
	<?php
		require WPOPAUTH_PATH . DIRECTORY_SEPARATOR
			. 'views' .  DIRECTORY_SEPARATOR . 'custom_openid.php';
	?>
	<br>
	<?php
		if ($allowDisabling)
		{
			echo '<h2>', __('Enabled network strategies', 'wp-opauth'), '</h2>';
			foreach ($strategies as $id => $info)
			{
				/* icon is only set it's a custom openid provider */
				if (!array_key_exists('icon', $info))
				{
					$name = $info['name'];
					$icon = (file_exists(WPOPAUTH_PATH . DIRECTORY_SEPARATOR
								. 'favicons' . DIRECTORY_SEPARATOR . "$id.png")?
							plugins_url("favicons/$id.png", dirname(__FILE__)) :
							'');
				}
				else
				{
					$name = $id;
					$icon = site_url($info['icon'] === null?
							DEFAULT_OPENID_ICON : $info['icon']);
				}
				?>
				<input type="checkbox"
					name="enabled[<?php echo htmlspecialchars($id); ?>]"
					<?php if (!array_key_exists($id, $disabledStrategies))
						echo 'checked="yes"'; ?>>
				<img src="<?php echo htmlspecialchars($icon); ?>"
					width="16" height="16"
					alt="<?php echo htmlspecialchars($id); ?>">
				<span><?php echo htmlspecialchars($name); ?></span>
				<br>
				<?php
			}
		}
	?>
	<input type="hidden" name="post" value="yes">
	<input type="submit" value="<?php _e('Store Settings', 'wp-opauth'); ?>">
</form>
