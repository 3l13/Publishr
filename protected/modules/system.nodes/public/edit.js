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
	}

	var destination = $(form.elements['#destination']);
	var key = $(form.elements['#key']);

	if (destination && key)
	{
		var base = '/api/' + destination.value + '/' + key.value + '/';

		window.addEvent
		(
			'domready', function()
			{
				var op = new Request.JSON
				(
					{
						url: base + 'lock',
						link: 'cancel'
					}
				);

				( function() { op.send(); }).periodical(30 * 1000);
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
						async: false,
						link: 'cancel'
					}
				);

				op.get();
			}
		);
	}
	else
	{
		/*
		 * For new entries, we use the core/ping method in order to keep the user's session alive.
		 */

		window.addEvent
		(
			'domready', function()
			{
				var op = new Request.JSON
				(
					{
						url: '/api/core/ping',
						link: 'cancel'
					}
				);

				op.send.bind(op).periodical(30 * 1000);
			}
		);
	}

	/*
	 * The following code looks for changes in elements' values between the 'domready' event and
	 * the 'onbeforeunload' event. If there are changes, the user is asked to confirm page unload.
	 */

	function toQueryString(el)
	{
		var elements = el.getElements('*[name]');
		var keys = [];
		var values = [];
		var assoc = {};

		elements.each
		(
			function(el)
			{
				if (el.disabled)
				{
					//console.log('el: %a is disabled', el);

					return;
				}

				var key = el.get('name');
				var value = el.get('value');

				keys.push(key);
				values.push(value);

				assoc[key] = value;
			}
		);

		var sorted_keys = keys.slice(0);

		sorted_keys.sort();

		//console.log('elements (%d): %a, active: %a, concat: %s', elements.length, elements, actives, concat);

		//console.log('keys: %a, values: %a', keys, values);

		var sorted_values = {};

		for (var i = 0; i < sorted_keys.length ; i++)
		{
			var key = sorted_keys[i];

			sorted_values[key] = assoc[key];
		}

		var hash = new Hash(sorted_values);

		//console.log('sorted keys: %a, values: %a', sorted_keys, sorted_values);

		//console.log('queryString: %s', hash.toQueryString());

		return hash.toQueryString();
	}

	var values_init;

	window.addEvent
	(
		'load', function()
		{
			values_init = toQueryString(form);
		}
	);

	window.onbeforeunload = function()
	{
		var values_now = toQueryString(form);

		//console.log('values_now: %s', values_now);

		if (values_init == values_now)
		{
			return;
		}

		return "Des changements ont été fait sur la page. Si vous changez de page maintenant, ils seront perdus.";
	};

	form.addEvent
	(
		'submit', function(ev)
		{
			window.onbeforeunload = null;
		}
	);

})();