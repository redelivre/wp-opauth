<hr>
<h3 class="wp-opauth-login-title">
	<?php _e('Or login using:', 'wp-opauth'); ?>
</h3>
<ul class="wp-opauth-login-strategies">
	<?php
		foreach ($strategies as $id => $info)
		{
			?>
			<li>
				<a href="<?php echo WP_PLUGIN_URL . "/wp-opauth/auth/$id"; ?>"><?php
					if (file_exists(WPOPAUTH_PATH
								. DIRECTORY_SEPARATOR . 'favicons'
								. DIRECTORY_SEPARATOR . $id . '.png'))
					{
						echo '<img src="' . WP_PLUGIN_URL . "/wp-opauth/favicons/$id.png"
							. '" alt=' . $id . '> ';
					}
					echo $info['name'];
				?></a>
			</li>
			<?php
		}
	?>
</ul>
