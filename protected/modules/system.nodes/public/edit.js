/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

window.addEvent
(
	'domready', function()
	{
		var form = $(document.body).getElement('form.edit');

		if (!form)
		{
			throw "Unable to get form";

			return;
		};

		var destination = form.elements['#destination'];
		var key = form.elements['#key'];

		if (destination && key)
		{
			var op = new WdOperation
			(
				destination.get('value'), 'lock'/*,
				{
					onComplete: function(response)
					{
						console.log('response: %a', response);
					}
				}*/
			);

			(function() { op.post({ '#key': key.get('value') }); }).periodical(60 * 1000);
		}
	}
);

window.addEvent
(
	'unload', function()
	{
		var form = $(document.body).getElement('form.edit');

		if (!form)
		{
			throw "Unable to get form";

			return;
		};

		var destination = form.elements['#destination'];
		var key = form.elements['#key'];

		if (destination && key)
		{
			var op = new WdOperation
			(
				destination.get('value'), 'unlock',
				{
					async: false/*,

					onComplete: function(response)
					{
						alert(JSON.encode(response));
					}
					*/
				}
			);

			op.post({ '#key': key.get('value') });
		}
	}
);