/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

"use strict";

String.implement
({

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

/*
(
	function()
	{
		var str = "Raccourcir une chaine de caractères à des endroits divers et variés.";

		console.log(str.shorten(32, 0));
		console.log(str.shorten(32, .25));
		console.log(str.shorten(32, .5));
		console.log(str.shorten(32, .75));
		console.log(str.shorten(32, 1));
	}
)();
*/












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

		spinner = new WdSpinner('loader');
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

var init = function()
{
	$$('input.search').each
	(
		function(el)
		{
			if (!el.value)
			{
				el.addClass('empty');
				el.value = 'Rechercher';
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
						this.value = 'Rechercher';
					}
				}
			});
		}
	);

	$$('.autofocus').each
	(
		function(el)
		{
			el.focus();
		}
	);
};

window.addEvent('domready', init);
window.addEvent('wd-element-ready', init);

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