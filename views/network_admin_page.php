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
?>

<form id="opauth-config" method="post" enctype="multipart/form-data">
	<h2><?php _e('General Options', 'wp-opauth'); ?></h2>
	<input type="checkbox" name="areButtonsOutside"
		<?php if ($areButtonsOutside) echo 'checked="yes"'; ?> >
	<?php
		_e('Try placing the login buttons below the login form', 'wp-opauth');
	?>
	<br>
	<input type="checkbox" name="localCustomOpenIDEnabled"
		<?php if ($localCustomOpenIDEnabled) echo 'checked="yes"'; ?> >
	<?php
		_e('Allow individual sites to specify custom openid providers', 'wp-opauth');
	?>
	<h3><?php _e('Custom openid providers', 'wp-opauth'); ?></h3>
	<p><i><?php
		_e('Requires the openid strategy to be enabled', 'wp-opauth');
	?></i></p>
	<?php
		require WPOPAUTH_PATH . DIRECTORY_SEPARATOR
			. 'views' .  DIRECTORY_SEPARATOR . 'custom_openid.php';
	?>
	<h2><?php _e('Strategies', 'wp-opauth'); ?></h2>
	<?php
		ksort($strategies);
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
	<input type="submit" value="<?php _e('Store Settings', 'wp-opauth'); ?>">
</form>
