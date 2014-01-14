<div id="wp-opauth-custom-openid-list">
	<i><?php
		_e('Icons are 16x16 png files up to 128 kilobytes', 'wp-opauth');
	?></i>
	<?php
		foreach ($customOpenID as $id => $info)
		{
			$hid = htmlspecialchars($id);
			$iconURL = htmlspecialchars(site_url($info['icon'] === null?
						DEFAULT_OPENID_ICON : $info['icon']));
		?>
			<div class="wp-opauth-custom-openid-item">
				<img src="<?php echo $iconURL; ?>"
					alt="<?php echo htmlspecialchars($id); ?>">
				<span><?php echo $hid; ?></span>
				<input type="text" size="32"
					value="<?php echo htmlspecialchars($info['url']); ?>"
					name="customopenid[<?php echo $hid; ?>][url]">
				<input type="file" name="customopenid[<?php echo $hid; ?>][icon]">
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
