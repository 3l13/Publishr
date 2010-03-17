window.addEvent
(
	'domready', function()
	{
		$$('.wd-titleslugcombo').each
		(
			function(el)
			{
				var toggle = el.getElement('.slug-reminder a');
				var target = el.getElement('.slug');
				
				toggle.addEvent
				(
					'click', function(ev)
					{
						ev.stop();
						
						target.toggle();
						toggle.getParent('.slug-reminder').hide();
					}
				);
			}
		);
	}
);