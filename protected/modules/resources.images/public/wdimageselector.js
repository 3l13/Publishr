window.addEvent
(
	'domready', function()
	{
		$$('.wdimageselector').each
		(
			function (el)
			{
				var results = el.getElements('ul.results a');
				//var title = el.getElement('div.title');
				var input = el.getElement('input.key');
				
				results.each
				(
					function(result)
					{
						result.addEvent
						(
							'click', function(ev)
							{
								ev.stop();
								
								var uri = new URI(result.href);
								var key = uri.parsed.fragment;
								
								el.getElements('ul.results li').removeClass('selected');
								result.getParent('li').addClass('selected');
								
								//title.set('html', result.title);
								input.value = key;
							}
						);
					}
				);
			}
		);
	}
);