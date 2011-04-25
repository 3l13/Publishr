/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

window.addEvent
(
	'domready', function()
	{
		var container = $('login');

		var login_form = container.getElement('form[name=connect]');
		var login_slide = login_form.getParent();
		var login_wrapper = login_slide.getParent();

		login_slide.set
		(
			'slide',
			{
				duration: 'short',
				wrapper: login_wrapper
			}
		);

		//login_slide.store('wrapper', login_wrapper);

		var password_form = container.getElement('form[name=password]');
		var password_slide = password_form.getParent();
		var password_wrapper = password_slide.getParent();

		password_slide.set('slide', { duration: 'short', wrapper: password_wrapper, resetHeight: true });
		password_slide.get('slide').hide();

		function form_log(message, type)
		{
			if (typeOf(message) == 'array')
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
			else if (typeOf(message) != 'string')
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

			( function() { form_log_clear(type); }).delay(3000);
		}

		function form_log_clear(type)
		{
			var log = password_form.getElement('ul.' + type);

			if (!log)
			{
				return;
			}

			new Fx.Tween(log, { property: 'opacity' }).start(0).chain(function() { log.destroy(); });
		}

		form_log_clear('missing');

		//
		// password form handling
		//

		password_form.addEvent
		(
			'submit', function(ev)
			{
				ev.stop();

				var op = new Request.API
				({
					url: 'nonce-login-request/' + password_form.elements.email.value,
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

						passwordOut.delay(3000);
					}
				});

				op.get();
			}
		);

		//
		// transitions
		//

		function passwordIn()
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

		function passwordOut()
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

				passwordIn();
			}
		);

		password_form.getElement('a').addEvent
		(
			'click', function(ev)
			{
				ev.stop();

				passwordOut();
			}
		);
	}
);