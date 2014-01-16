<form method="post">
	<h1><?php echo htmlspecialchars($name); ?></h1>
	<?php
		ksort($values);
		foreach ($values as $key => $v)
		{
			$hkey = htmlspecialchars($key);
			$hv = htmlspecialchars($v);
			?>
				<span><?php echo $hkey; ?>:</span>
				<input type="text" name="<?php echo $hkey; ?>"
					value="<?php echo $hv; ?>">
				<br>
			<?php
		}
	?>
	<input type="submit" value="OK">
</form>
