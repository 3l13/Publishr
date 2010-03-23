window.addEvent
(
	'domready', function()
	{
		$$('.wd-titleslugcombo').each
		(
			function(el)
			{
				var reminder = el.getElement('.slug-reminder');
				var target = el.getElement('.slug');
				
				var expand = reminder.getElement('a');
				var collapse = el.getElement('a[href$=slug-collapse]');
				
				var toggle = function(ev)
				{
					ev.stop();
					
					target.toggle();
					reminder.toggle();
					
					collapse.setStyle('display', collapse.getStyle('display') == 'none' ? 'inline' : 'none');
				};
				
				expand.addEvent('click', toggle);
				collapse.addEvent('click', toggle);
				
				target.getElement('input').addEvent
				(
					'change', function(ev)
					{
						var value = this.get('value');
						
						reminder.getElement('span').set(value ? 'text' : 'html', value ? value : '<em>non défini</em>');
					}
				);
			}
		);
	}
);