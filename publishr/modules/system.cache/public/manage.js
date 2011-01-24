/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

window.addEvent
(
	'domready', function()
	{
		var ids = [];
		var usage = $$('table.manage td.usage');

		$$('table.manage td.state input').each
		(
			function(el, i)
			{
				ids.push(el.name);

				el.addEvent
				(
					'click', function(ev)
					{
						var target = ev.target;
						var cacheName = target.name;

						var req = new Request.JSON
						({

							url: '/api/system.cache/' + ids[i] + '/' + (target.checked ? 'activate' : 'deactivate')

						});

						req.send();
					}
				);
			}
		);

		$$('table.manage button[name="clear"]').each
		(
			function(el, i)
			{
				el.addEvent
				(
					'click', function(ev)
					{
						var req = new Request.JSON
						({

							url: '/api/system.cache/' + ids[i] + '/clear',

							onRequest: function()
							{
								//el.disabled = true;
							},

							onComplete: function()
							{
								//el.disabled = false;

								updateUsage(usage[i]);
							}

						});

						req.send();
					}
				);
			}
		);

		function updateUsage(el)
		{
			var i = usage.indexOf(el);

			var req = new Request.JSON
			({

				url: '/api/system.cache/' + ids[i] + '/usage',

				onSuccess: function(response)
				{
					el[(response.count ? 'remove' : 'add') + 'Class']('empty');
					el.innerHTML = response.rc;
				}
			});

			req.get();
		}

		usage.each(updateUsage);
	}
);