( function() {

	function apply()
	{
		$$('textarea.moo').each
		(
			function(el)
			{
				if (el.retrieve('mooeditable'))
				{
					return;
				}

				var options = new Dataset().getDataset(el);

				if (options.externalCss)
				{
					options.externalCSS = JSON.decode(options.externalCss);
				}

				if (options.baseUrl)
				{
					options.baseURL = options.baseUrl;
				}

				el.mooEditable(options);

				el.store('mooeditable', true);
			}
		);
	}

	window.addEvent('domready', apply);
	document.addEvent('editors', apply); // TODO-20110123: remove 'domready' and 'editors'
	document.addEvent('elementsready', apply);

}) ();

