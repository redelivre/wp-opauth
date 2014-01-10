<div id="wp-opauth-custom-openid-list">
	<?php
		foreach ($customOpenID as $id => $url)
		{
		?>
			<div class="wp-opauth-custom-openid-item">
			<span><?php echo htmlspecialchars($id); ?></span>
			<input type="text" size="32"
				value="<?php echo htmlspecialchars($url); ?>"
				name="customopenid[<?php echo htmlspecialchars($id); ?>]">
			<input type="Button"
				value="<?php _e('Remove', 'wp-opauth'); ?>">
			</div>
		<?php
		}
	?>
</div>
<br>
<input type="text" size="12" id="wp-opauth-custom-openid-id">
<input type="button" id="wp-opauth-custom-openid-add"
	value="<?php _e('Add', 'wp-opauth'); ?>">
