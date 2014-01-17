<form method="post">
	<h1><?php echo htmlspecialchars($name); ?></h1>
	<?php
		ksort($values);
		foreach ($values as $key => $v)
		{
			?>
				<span><?php echo htmlspecialchars($key); ?>:</span>
				<input type="text" name="<?php echo sanitize_html_class($key); ?>"
					value="<?php echo htmlspecialchars($v); ?>">
				<br>
			<?php
		}
	?>
	<input type="submit" value="<?php echo __('Next', 'wp-opauth'); ?>">
</form>
