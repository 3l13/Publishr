window.addEvent
(
	'domready', function()
	{
		$(document.body).getElements('form.edit div.form-section').each
		(
			function(section)
			{
				var trigger = section.getPrevious();
				
				if (trigger.tagName != 'H3')
				{
					return;
				}
				
				trigger.addEvent
				(
					'click', function()
					{
						trigger.toggleClass('folded');
						section.toggleClass('folded');
					}
				);
			}
		);
	}
);