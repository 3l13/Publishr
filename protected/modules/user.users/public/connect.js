window.addEvent
(
	'domready', function()
	{
		var container = $('login');
		
		var login_form = container.getElement('form[name=connect]');
		var login_slide = login_form.getParent(); 
		var login_wrapper = login_slide.getParent();
		
		login_slide.set('slide', { duration: 'short', wrapper: login_wrapper });
		//login_slide.store('wrapper', login_wrapper);
		
		var password_form = container.getElement('form[name=password]');
		var password_slide = password_form.getParent();
		var password_wrapper = password_slide.getParent();
		var password_el = password_form['email'];
		
		password_slide.set('slide', { duration: 'short', wrapper: password_wrapper });
		//password_slide.store('wrapper', password_wrapper);
		
		password_slide.get('slide').hide();
		
		var form_log = function(message, type)
		{
			if ($type(message) == 'array')
			{
				message.each
				(
					function(m)
					{
						form_log(m, type);
					}
				);
				
				return;
			}
			else if ($type(message) != 'string')
			{
				return;
			};
			
			var log = password_form.getElement('ul.' + type);
			
			if (!log)
			{
				var log = new Element('ul', { 'class': type });
				
				log.set('tween', { property: 'opacity' });
				log.get('tween').set(0);
				log.inject(password_form, 'top');
				log.get('tween').start(1);
			}
			
			var line = new Element('li', { 'html': message });
			
			line.inject(log);
		};
		
		var form_log_clear = function(type)
		{
			var log = password_form.getElement('ul.' + type);
			
			if (!log)
			{
				return;
			}
			
			log.destroy();
		};
		
		//
		// password form handling
		//
		
		password_form.addEvent
		(
			'submit', function(ev)
			{
				ev.stop();
				
				var op = new WdOperation
				(
					'user.users', 'retrievePassword',
					{
						onComplete: function(response)
						{
							var rc = response.rc;
							
							form_log_clear('missing');
							
							if (!rc)
							{
								form_log(response.log.error, 'missing');
								
								return;
							}
							
							form_log(response.log.done, 'success');
							
							(
								function()
								{
									password_out();
								}
							)
							.delay(3000);
						}
					}
				);
				
				op.post({ email: password_el.value });
			}
		);
		
		//
		// transitions
		//
		
		var password_in = function()
		{
			form_log_clear('success');
			form_log_clear('missing');
			
			login_slide.get('slide').slideOut().chain
			(
				function()
				{
					password_slide.get('slide').slideIn()/*.chain
					(
						function()
						{
							password_wrapper.setStyle('height', '');
						}
					)*/;
				}
			);
			
			return password_slide.get('slide');
		};
		
		var password_out = function()
		{
			password_slide.get('slide').slideOut().chain
			(
				function()
				{
					login_slide.get('slide').slideIn()/*.chain
					(
						function()
						{
							login_wrapper.setStyle('height', '');
						}
					)*/;
				}
			);
			
			return login_slide.get('slide');
		};
		
		//password_in();
		
		login_form.getElement('a').addEvent
		(
			'click', function(ev)
			{
				ev.stop();
				
				password_in();
			}
		);
		
		password_form.getElement('a').addEvent
		(
			'click', function(ev)
			{
				ev.stop();
				
				password_out();
			}
		);
	}
);