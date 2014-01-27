<h1><?php _e('Opauth User List', 'wp-opauth'); ?></h1>

<p><?php printf(__('%d opauth users', 'wp-opauth'), sizeof($users)); ?></p>

<table id="wp-opauth-user-table">
	<thead>
		<tr>
			<td>
				<b><?php _e('Name', 'wp-opauth'); ?></b>
			</td>
			<td>
				<b><?php _e('Provider', 'wp-opauth'); ?></b>
			</td>
			<td>
				<b><?php _e('Remote ID', 'wp-opauth'); ?></b>
			</td>
		</tr>
	</thead>
	<tbody>
		<?php
			foreach ($users as $user)
			{
				$editURL = htmlspecialchars(get_edit_user_link($user['local_id']));
				?>
					<tr>
						<td>
							<a href="<?php echo $editURL; ?>"><?php
								echo $user['display_name'];
							?></a>
						</td>
						<td>
							<?php echo $user['provider']; ?>
						</td>
						<td>
							<?php echo $user['remote_id']; ?>
						</td>
					</tr>
				<?php
			}
		?>
	</tbody>
</table>
