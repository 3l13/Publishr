/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

manager.addEvent
(
	'ready', function()
	{
		manager.element.addEvent
		(
			'click', function(ev)
			{
				var target = ev.target;
				
				if (!target.match('input.is_online'))
				{
					return;
				}
				
				var operation = new WdOperation
				(
					manager.destination, target.checked ? 'online' : 'offline',
					{
						onSuccess: function(response)
						{
							if (!response.rc)
							{
								//
								// if for some reason the operation failed,
								// we reset the checkbox
								//

								target.checked = !target.checked;

								target.fireEvent('change', {});
							}
						}
					}
				);

				operation.post({ '#key': target.value });
			}
		);
	}
);