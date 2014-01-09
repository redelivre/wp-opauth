<div id="wp-opauth-login">
	<h3 class="wp-opauth-login-title">
		<?php _e('Or login using:', 'wp-opauth'); ?>
	</h3>
	<ul class="wp-opauth-login-strategies">
		<?php
			asort($strategies);
			foreach ($strategies as $id => $info)
			{
				?>
				<li class="wp-opauth-login-strategy">
					<a href="<?php echo WP_PLUGIN_URL . "/wp-opauth/auth/$id"; ?>"><?php
						if (file_exists(WPOPAUTH_PATH
									. DIRECTORY_SEPARATOR . 'favicons'
									. DIRECTORY_SEPARATOR . $id . '.png'))
						{
							echo '<img src="' . WP_PLUGIN_URL . "/wp-opauth/favicons/$id.png"
								. '" alt=' . $id . '> ';
						}
						echo '<span>' . $info['name'] . '</span>';
					?></a>
				</li>
				<?php
			}
		?>
	</ul>
	<div class="wp-opauth-login-clear"></div>
</div>
