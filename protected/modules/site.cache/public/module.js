// JavaScript Document

window.addEvent
(
	'domready', function()
	{
		//
		// confirm checkbox
		//
		
		var clear_confirm = document.getElementById('cache-clear-confirm');
	
		if (clear_confirm)
		{
			new Wd.Elements.Checkbox(clear_confirm);
			
			//
			// confirm button
			//
	
			var clear_button = $$('#main button.ok')[0];
			
			clear_button.set('opacity', 0);
			
			clear_confirm.addEvent
			(
				'click',
				
				function()
				{
					if (this.checked)
					{
						clear_button.fade('in');
					}
					else
					{
						clear_button.fade('out');
					}
				}
			);
		}
	}
);