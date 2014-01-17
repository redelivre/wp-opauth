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
	<input type="hidden" name="post" value="yes">
	<input type="submit" value="<?php _e('Store Settings', 'wp-opauth'); ?>">
</form>
