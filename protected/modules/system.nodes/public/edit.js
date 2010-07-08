/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

(function()
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
		var base = '/do/' + destination.value + '/' + key.value + '/';

		window.addEvent
		(
			'domready', function()
			{
				var op = new Request.JSON
				(
					{
						url: base + 'lock'
					}
				);

				op.get.periodical(30 * 1000, op);
			}
		);

		window.addEvent
		(
			'unload', function()
			{
				var op = new Request.JSON
				(
					{
						url: base + 'unlock',
						async: false
					}
				);

				op.get();
			}
		);
	}
})();