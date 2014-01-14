<div id="wp-opauth-login">
	<h3 class="wp-opauth-login-title">
		<?php _e('Or login using:', 'wp-opauth'); ?>
	</h3>
	<ul class="wp-opauth-login-strategies">
		<?php
			/* Priorities are: local > network > default */
			$strategies = array_merge($strategies, $networkCustomOpenID);
			$strategies = array_merge($strategies, $localCustomOpenID);
			ksort($strategies);
			foreach ($strategies as $id => $info)
			{
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
					$url = plugins_url('openidredirect.php?url=' . urlencode($info['url']),
							dirname(__FILE__));
					$icon = site_url($info['icon'] === null?
							DEFAULT_OPENID_ICON : $info['icon']);
				}
				?>
				<li class="wp-opauth-login-strategy">
					<a href="<?php echo $url; ?>"><?php
						if ($icon)
						{
							echo '<img src="' . $icon . '" alt=' . $name . '> ';
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
