<div id="wp-opauth-login">
	<h3 class="wp-opauth-login-title">
		<?php _e('Or login using:', 'wp-opauth'); ?>
	</h3>
	<?php
		if (array_key_exists('openid', $strategies))
		{
			/* JS hack is needed because nested forms are invalid */
			?>
			<div id="wp-opauth-openid-form">
				<img src="<?php echo plugins_url('favicons/openid.png',
					dirname(__FILE__)); ?>" alt="openid">
				<span><?php echo _e('OpenID URL', 'wp-opauth'); ?></span>
				<br>
				<div id="wp-opauth-openid-input">
					<input type="button" id="wp-opauth-openid-login"
						value="<?php _e('Login', 'wp-opauth'); ?>">
					<span><input type="text" id="wp-opauth-openid-url"></span>
				</div>
			</div>
			<?php
		}
	?>
	<ul class="wp-opauth-login-strategies">
		<?php

			ksort($strategies);
			foreach ($strategies as $id => $info)
			{
				/* Treated above */
				if ($id === 'openid')
				{
					continue;
				}
				/* icon is only set it's a custom openid provider */
				if (!array_key_exists('icon', $info))
				{
					$name = $info['name'];
					$url = plugins_url("auth/$id", dirname(__FILE__));
					$icon = (file_exists(WPOPAUTH_PATH . DIRECTORY_SEPARATOR
								. 'favicons' . DIRECTORY_SEPARATOR . "$id.png")?
							plugins_url("favicons/$id.png", dirname(__FILE__)) :
							'');
				}
				else
				{
					$name = $id;
					$url = site_url('wp-login.php?openidurl=' .
							urlencode($info['url']) . '&openidname=' . urlencode($name));
					$icon = site_url($info['icon'] === null?
							DEFAULT_OPENID_ICON : $info['icon']);
				}
				?>
				<li class="wp-opauth-login-strategy">
					<a href="<?php echo $url; ?>"><?php
						if ($icon)
						{
							echo '<img width="16" height="16" src="'
								. $icon . '" alt=' . $name . '> ';
						}
						echo '<span>' . $name . '</span>';
					?></a>
				</li>
				<?php
			}
		?>
	</ul>
	<div class="wp-opauth-login-clear"></div>
</div>
