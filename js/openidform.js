function submitOpenIDForm()
{
	var input = jQuery('#wp-opauth-openid-url').val();
	var url = window.location.href;

	/* Strip GET */
	url = url.replace(/\?.+/, '');
	url += '?openidurl=' + encodeURIComponent(input);

	if (input)
	{
		window.location = url;
	}
}

jQuery(document).ready(function()
{
	jQuery('#wp-opauth-openid-login').click(submitOpenIDForm);
	jQuery('#wp-opauth-openid-url').keypress(function(e)
	{
		if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13))
		{
			jQuery('#wp-opauth-openid-login').click();
			return false;
		}
		return true;
	});
});
