window.addEvent
(
	'domready', function()
	{
		$$('div.wd-thumbnailer-config').each
		(
			function(el)
			{
				var w = el.getElement('input[name$="[w]"]');
				var h = el.getElement('input[name$="[h]"]');
				var method = el.getElement('select[name$="[method]"]');
				var format = el.getElement('select[name$="[format]"]');
				var quality = el.getElement('input[name$="[quality]"]');
				
				function checkMethod()
				{
					switch (method.get('value'))
					{
						case 'fixed-height':
						{
							h.readOnly = false;
							w.readOnly = true;
						}
						break;
						
						case 'fixed-width':
						{
							h.readOnly = true;
							w.readOnly = false;
						}
						break;
						
						default:
						{
							w.readOnly = false;
							h.readOnly = false;
						}
						break;
					}
				}
				
				function checkQuality()
				{
					var value = format.get('value');
					
					quality.getParent().setStyle('display', (value != 'jpeg') ? 'none' : '');
				}
				
				checkMethod();
				checkQuality();
				
				method.addEvent('change', checkMethod);
				format.addEvent('change', checkQuality);
			}
		);
	}
);