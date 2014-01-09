jQuery(document).ready(function()
{
	var login = jQuery('#wp-opauth-login');
	var nav = jQuery('#nav');
	if (login.length && nav.length)
	{
		login.insertAfter(nav);
		login.addClass('wp-opauth-login-box');
	}
});
