/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

document.addEvent
(
	'elementsready', function(ev)
	{
		function awake(el)
		{
			if (el.retrieve('adjust'))
			{
				return;
			}

			var adjust = new WdAdjustNode(el);

			el.store('adjust', adjust);
		}

		var target = ev.target;
		var match = '.wd-adjustimage';

		if (target.match(match))
		{
			awake(target);
		}
		else
		{
			target.getElements(match).each(awake);
		}
	}
);