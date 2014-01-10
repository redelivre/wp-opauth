function addCustomOpenID()
{
	var input = jQuery('#wp-opauth-custom-openid-id');
	var list = jQuery('#wp-opauth-custom-openid-list');
	var id = input.val();
	var inputName = 'customopenid[' + id + ']';

	if (id && !jQuery('input[name="' + inputName + '"]').length)
	{
		var div = jQuery(
				'<div class="wp-opauth-custom-openid-item"></div>').appendTo(list);
		jQuery('<span></span>').appendTo(div).text(id + ': ');
		var url = jQuery('<input type="text" size="32">').appendTo(div);
		url.val(i10n['defaultURL']);
		url.attr('name', inputName);
		var remove = jQuery('<input type="button">').appendTo(div);
		remove.val(i10n['remove']);
		remove.click(function()
		{
			jQuery(this).parent().remove();
		});
	}

	input.val('');
}

jQuery(document).ready(function()
{
	jQuery('#wp-opauth-custom-openid-add').click(addCustomOpenID);

	jQuery('#wp-opauth-custom-openid-id').keypress(function(e)
	{
		if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13))
		{
			jQuery('#wp-opauth-custom-openid-add').click();
			return false;
		}
		return true;
	});
});
