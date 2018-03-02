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
	<h2><?php _e('Force login to a provider?', 'wp-opauth') ?></h2>
	<label><input  <?php echo $ForceStrategyLogin ? 'checked="checked"' : ''; ?>	
		type="checkbox" name="ForceStrategyLogin" value="1" autocomplete="off">
		<?php _e('Force', 'wp-opauth'); ?>
	</label>
	<br>
	<?php ksort($strategies);?>
	<select name="ForceStrategyID" autocomplete="off">
		<option value="false"><?php _e('Select a provider to be forced', 'wp-opauth'); ?></option><?php
		foreach ($strategies as $id => $info)
		{
			if (array_key_exists($id, $values) || array_key_exists(strtolower($id), $net_strategies))
			{?>
				<option value="<?php echo htmlspecialchars($id); ?>" <?php
					if (strtolower($id) == $ForceStrategyID)
					{
						echo 'selected="selected"';
					}?>
				><?php echo htmlspecialchars($id); ?></option><?php
			}
		}?>
	</select>
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
			foreach ($net_strategies as $id => $info)
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
							WPOPAUTH_DEFAULT_OPENID_ICON : $info['icon']);
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
	<h2><?php _e('Overwrite Strategies Config', 'wp-opauth'); ?></h2>
	<?php
		foreach ($strategies as $id => $info)
		{
			$hid = htmlspecialchars($id);
			echo '<div class="opauth-strategy-config">';
			echo "<h3>$hid</h3>";
			echo '<input ';
			if (array_key_exists($id, $values))
			{
				echo 'checked="yes" ';
			}
			echo "type=\"checkbox\" name=\"strategies[{$hid}][enabled]\"> ";
			_e('Enabled', 'wp-opauth');
			echo '<br>';
			foreach ($info as $name => $v)
			{
				$name = htmlspecialchars($name);
				/* Only null values are to be edited via the panel */
				if ($v === null)
				{
					echo "$name: ";
					echo '<input ';
					if (array_key_exists($id, $values)
							&& array_key_exists($name, $values[$id]))
					{
						echo 'value="' . htmlspecialchars($values[$id][$name]) . '" ';
					}
					echo "type=\"text\" name=\"strategies[{$hid}][$name]\">";
					echo '<br>';
				}
			}
			if (array_key_exists($id, $callbackURLs))
			{
				echo "<span>";
				_e('Return URL', 'wp-opauth');
				echo ': ', $callbackURLs[$id], '</span>';
			}
			echo '</div>';
		}
	?>
	<input type="hidden" name="post" value="yes">
	<input type="submit" value="<?php _e('Store Settings', 'wp-opauth'); ?>">
</form>
