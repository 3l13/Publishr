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
		
		//
		// ping
		//
		
		var ping = new Request();
		
		(function() { ping.get({ 'do': 'core.ping' }); } ).periodical(1000 * 60 * 5); // ping every 5 minutes
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

			el.addEvent
			(
				'focus', function()
				{
					if (el.hasClass('empty'))
					{
						el.value = '';
						el.removeClass('empty');
					}
				}
			);

			el.addEvent
			(
				'blur', function()
				{
					if (!el.value)
					{
						el.addClass('empty');
						el.value = 'Rechercher';
					}
				}
			);
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