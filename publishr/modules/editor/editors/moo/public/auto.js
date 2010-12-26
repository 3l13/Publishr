( function() {

	function apply()
	{
		$$('textarea.moo').each
		(
			function(el)
			{
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
			}
		);
	}

	window.addEvent('domready', apply);
	document.addEvent('editors', apply);

}) ();

