window.addEvent
(
	'domready', function()
	{
		var form = $(document.body).getElement('form.edit');
		
		if (!form)
		{
			return;
		}
		
		function check_is_notify()
		{
			is_notify_target[(is_notify.checked ? 'add' : 'remove') + 'Class']('unfolded');
		}
		
		var is_notify = form.elements['is_notify'];
		var is_notify_target = is_notify.getParent('div.is_notify');
		
		is_notify.addEvent('change', check_is_notify);
		
		check_is_notify();
	}
);