/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

"use strict";

window.addEvent
(
	'domready', function()
	{
		$$('.wd-titleslugcombo').each
		(
			function(el)
			{
				var options = JSON.decode(el.getElement('input.element-options').value);

				var reminder = el.getElement('.slug-reminder');
				var target = el.getElement('.slug');

				var expand = el.getElement('a[href$=slug-edit');
				var collapse = el.getElement('a[href$=slug-collapse]');
				var del = el.getElement('a[href$=slug-delete]');

				var input = target.getElement('input');

				var toggle = function(ev)
				{
					ev.stop();

					target.toggle();
					reminder.toggle();

					collapse.setStyle('display', collapse.getStyle('display') == 'none' ? 'inline' : 'none');
				};

				expand.addEvent('click', toggle);
				collapse.addEvent('click', toggle);

				function checkInput()
				{
					var value = input.get('value');
					var type = value ? 'text' : 'html';

					if (value)
					{
						value = value.shorten();
						del.getParent('span').setStyle('display', 'inline');
					}
					else
					{
						value = options.autoLabel;
						del.getParent('span').setStyle('display', 'none');
					}

					reminder.getElement('a').set(type, value);
				}

				input.addEvent('change', checkInput);

				del.addEvent
				(
					'click', function(ev)
					{
						ev.stop();

						input.value = '';
						input.fireEvent('change', {});
					}
				);

				checkInput();
			}
		);
	}
);