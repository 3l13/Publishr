/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

String.implement
({
	/**
	 * Shortens a string to given length from a given position.
	 *
	 * Example:
	 *
	 * var str = "Raccourcir une chaine de caractères à des endroits divers et variés.";
	 *
	 * console.log(str.shorten(32, 0)); // remove characters from the beginning of the string
	 * console.log(str.shorten(32, .25));
	 * console.log(str.shorten(32, .5)); // remove characters from the middle of the string
	 * console.log(str.shorten(32, .75));
	 * console.log(str.shorten(32, 1)); // remove characters from the end of the string
	 *
	 * @param int length
	 * @param float position
	 * @return string A string shortened.
	 */
	shorten: function(length, position)
	{
		if (length === undefined)
		{
			length = 32;
		}

		if (position === undefined)
		{
			position = .75;
		}

		var l = this.length;

		if (l <= length)
		{
			return this;
		}

		length--;
		position = Math.round(position * length);

		if (position == 0)
		{
			return '…' + this.substring(l - length);
		}
		else if (position == length)
		{
			return this.substring(0, length) + '…';
		}
		else
		{
			return this.substring(0, position) + '…' + this.substring(l - (length - position));
		}
	}
});













if (!Wd)
{
	var Wd = {};
}

if (!Wd.Elements)
{
	Wd.Elements = {};
}

var spinner = null;

window.addEvent
(
	'domready', function()
	{
		//
		// disabled Firefox's spellchecking for textarea elements with the 'code' class
		//

		$$('textarea.code').each
		(
			function(el)
			{
				if (el.spellcheck)
				{
					el.spellcheck = false;
				}
			}
		);

		if ($('loader'))
		{
			spinner = new WdSpinner('loader');
		}
	}
);

window.addEvent
(
	'domready', function()
	{
		$$('label.checkbox-wrapper').each
		(
			function (el)
			{
				var checkbox = el.getElement('input');

				if (checkbox.checked)
				{
					el.addClass('checked');
				}

				if (checkbox.disabled)
				{
					el.addClass('disabled');
				}

				if (checkbox.readonly)
				{
					el.addClass('readonly');
				}

				checkbox.addEvent
				(
					'change', function()
					{
						this.checked ? el.addClass('checked') : el.removeClass('checked');
					}
				);
			}
		);
	}
);

(function() {

var init = function(ev)
{
	ev.target.getElements('input.search').each
	(
		function(el)
		{
			if (el.retrieve('widget-search'))
			{
				return;
			}

			var label = el.get('data-placeholder') || 'Search';

			if (!el.value)
			{
				el.addClass('empty');
				el.value = label;
			}

			el.addEvents
			({
				focus: function()
				{
					if (this.hasClass('empty'))
					{
						this.value = '';
						this.removeClass('empty');
					}
				},

				blur: function()
				{
					if (!this.value)
					{
						this.addClass('empty');
						this.value = label;
					}
				}
			});

			el.store('widget-search', true);
		}
	);

	ev.target.getElements('.autofocus').each
	(
		function(el)
		{
			el.focus();
		}
	);
};

document.addEvent('elementsready', init);

})();



window.addEvent
(
	'load', function()
	{
		(
			function()
			{
				$$('ul.wddebug.done').slide('out');
			}
		)
		.delay(4000);
	}
);


/**
 * The "elementsready" event is fired for elements to be initialized, to become alive thanks to the
 * magic of Javascript. This event is usually fired when new widgets are added to the DOM.
 */
window.addEvent
(
	'domready', function()
	{
		document.fireEvent('elementsready', { target: $(document.body) });
	}
);