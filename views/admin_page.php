<h1><?php _e('Opauth', 'wp-opauth'); ?></h1>

<form id="opauth-config" method="post">
	<h2>Custom openid providers</h2>
	<p><i><?php
		_e('Requires the openid strategy to be enabled in the network',
				'wp-opauth');
	?></i></p>
	<?php
		require WPOPAUTH_PATH . DIRECTORY_SEPARATOR
			. 'views' .  DIRECTORY_SEPARATOR . 'custom_openid.php';
	?>
	<br>
	<input type="submit" value="<?php _e('Store Settings', 'wp-opauth'); ?>">
</form>
