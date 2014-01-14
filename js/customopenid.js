function addCustomOpenID()
{
	var input = jQuery('#wp-opauth-custom-openid-id');
	var list = jQuery('#wp-opauth-custom-openid-list');
	var id = input.val();
	var urlName = 'customopenid[' + id + '][url]';
	var iconName = 'customopenid[' + id + '][icon]';

	if (id && !jQuery('input[name="' + urlName + '"]').length)
	{
		var div = jQuery(
				'<div class="wp-opauth-custom-openid-item"></div>').appendTo(list);

		var img = jQuery('<img>').appendTo(div);
		img.attr('src', i18n['defaultIconURL']);
		img.attr('alt', id);
		img.attr('width', 16);
		img.attr('height', 16);
		img.after(' ');

		jQuery('<span></span>').appendTo(div).text(id + ': ');

		var url = jQuery('<input type="text" size="32">').appendTo(div);
		url.val(i18n['defaultURL']);
		url.attr('name', urlName);

		var icon = jQuery('<input type="file">').appendTo(div);
		icon.attr('name', iconName);

		var remove = jQuery('<input type="button">').appendTo(div);
		remove.val(i18n['remove']);
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

	jQuery('.wp-opauth-custom-openid-item input[type="button"]').click(function()
	{
		jQuery(this).parent().remove();
	});
});
