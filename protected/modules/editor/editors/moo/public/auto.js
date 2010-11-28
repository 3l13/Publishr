window.addEvent
(
	'domready', function()
	{
		$$('textarea.moo').each
		(
			function(el)
			{
				var config =
				{
					actions: 'bold italic underline strikethrough | formatBlock justifyleft justifyright justifycenter justifyfull | insertunorderedlist insertorderedlist indent outdent | undo redo | createlink unlink | image | removeformat toggleview',
					externalCSS:
					[
					 	'/$wd/wdpublisher/public/css/reset.css',
					 	'/$wd/wdpublisher/public/support/mooeditable/body.css'
				 	]
				};

				var config_el = el.getNext('input.wd-editor-config');

				if (config_el)
				{
					config = JSON.decode(config_el.value);
				}

				el.mooEditable(config);
			}
		);
	}
);