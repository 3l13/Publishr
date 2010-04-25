window.addEvent
(
	'domready', function()
	{
		var form = $(document.body).getElement('form.edit');
		
		if (!form)
		{
			return;
		}
		
		var firstname = $(form.elements['firstname']);
		var lastname = $(form.elements['lastname']);
		var username = $(form.elements['username']);
		
		var auto_username = !firstname.value && !lastname.value;
		
		username.addEvent
		(
			'keypress', function(ev)
			{
				if (ev.key.length > 1)
				{
					return;
				}
				
				auto_username = false;
			}
		);
		
		if (auto_username)
		{
			var update = function()
			{
				if (!auto_username)
				{
					return;
				}
				
				value = ((firstname.value ? firstname.value[0] : '') + (lastname.value ? lastname.value : '')).toLowerCase();
				
				value = value.replace(/[àáâãäåąă]/g,"a");
				value = value.replace(/[çćčċ]/g,"c");
				value = value.replace(/[èéêëēęė]/g,"e");
				value = value.replace(/[ìîïīĩį]/g,"i");
				value = value.replace(/[óôõöøőŏ]/g,"o");
				value = value.replace(/[ùúûüų]/g,"u");
				
				username.value = value;
				username.fireEvent('change', {});
			};
			
			firstname.addEvent('keyup', update);
			firstname.addEvent('change', update);
			
			lastname.addEvent('keyup', update);
			lastname.addEvent('change', update);
		}
		
		//
		//
		//
		
		var display = $(form.elements['display']);
		
		displayOptions = display.getChildren('option');
		
		var updateDisplayOption = function(index, value)
		{
			var el = display.getElement('option[value=' + index + ']');
			
			if (!value)
			{
				if (el)
				{
					el.destroy();
				}
				
				return;
			}
			
			if (!el)
			{
				el = new Element('option', { 'value': index });
				
				displayOptions[index] = el;
				
				display.adopt(displayOptions);
			}
			
			el.set('text', value);
		};
		
		var updateDisplayComposedOption = function()
		{
			if (!firstname.value || !lastname.value)
			{
				updateDisplayOption(3, null);
				updateDisplayOption(4, null);
				
				return;
			}
			
			updateDisplayOption(3, firstname.value + ' ' + lastname.value);
			updateDisplayOption(4, lastname.value + ' ' + firstname.value);
		};
		
		firstname.addEvent
		(
			'keyup', function()
			{
				updateDisplayOption(1, this.value);
				updateDisplayComposedOption();
			}
		);
		
		lastname.addEvent
		(
			'keyup', function()
			{
				updateDisplayOption(2, this.value);
				updateDisplayComposedOption();
			}
		);
		
		username.addEvent
		(
			'change', function()
			{
				updateDisplayOption(0, this.value ? this.value : '<username>');
			}
		);
		
		username.addEvent
		(
			'keyup', function()
			{
				updateDisplayOption(0, this.value ? this.value : '<username>');
			}
		);
	}
);