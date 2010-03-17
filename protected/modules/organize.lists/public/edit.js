window.addEvent
(
	'domready', function()
	{
		/*
		$$('div.wd-adjustnodeslist').each
		(
			function(el)
			{
				var options = {};
				var options_el = el.getElement('input.wd-element-options');
				
				if (options_el)
				{
					options = JSON.decode(options_el.value);
				}
				
				new WdAdjustNodesList(el, options);
			}
		);
		*/
		
		var form = $(document.body).getElement('form.edit');
		
		var el = form.getElement('div.wd-adjustnodeslist');
		
		var options = {};
		var options_el = el.getElement('input.wd-element-options');
				
		if (options_el)
		{
			options = JSON.decode(options_el.value);
		}
				
		adjust = new WdAdjustNodesList(el, options);
		
		//
		//
		//
		
		var scope = form.elements['scope'];
		
		scope.addEvent
		(
			'change', function()
			{
				adjust.setScope(this.get('value'));
				adjust.getResults();
			}
		);
	}
);