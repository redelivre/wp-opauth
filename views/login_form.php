<div id="wp-opauth-login">
	<h3 class="wp-opauth-login-title">
		<?php _e('Or login using:', 'wp-opauth'); ?>
	</h3>
	<ul class="wp-opauth-login-strategies">
		<?php
			/* Network defined custom open id providers have priority over the
			 * default strategies. */
			$strategies = array_merge($strategies, $networkCustomOpenID);
			ksort($strategies);
			foreach ($strategies as $id => $info)
			{
				/* Being an array means it's a default strategy */
				if (is_array($info))
				{
					$name = $info['name'];
					$url = plugins_url("auth/$id", dirname(__FILE__));
					$favicon = $id;
				}
				else
				{
					$name = $id;
					$url = plugins_url('openidredirect.php?url=' . urlencode($info),
							dirname(__FILE__));
					$favicon = 'openid';
				}
				?>
				<li class="wp-opauth-login-strategy">
					<a href="<?php echo $url; ?>"><?php
						if (file_exists(WPOPAUTH_PATH
									. DIRECTORY_SEPARATOR . 'favicons'
									. DIRECTORY_SEPARATOR . $favicon . '.png'))
						{
							echo '<img src="'
								. plugins_url("favicons/$favicon.png", dirname(__FILE__))
								. '" alt=' . $name . '> ';
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
