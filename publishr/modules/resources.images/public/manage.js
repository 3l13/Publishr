var WdPopupImage = new Class
({
	initialize: function(el, src)
	{
		this.element = $(el);
		this.src = src;

		this.element.addEvent('mouseenter', this.onMouseEnter.bind(this));
		this.element.addEvent('mouseleave', this.onMouseLeave.bind(this));
	},

	onMouseEnter: function()
	{
		this.cancel = false;

		var func = this.popup ? this.show : this.load;

		func.delay(100, this);
	},

	load: function()
	{
		if (this.cancel || this.popup)
		{
			return;
		}

		//console.log('create asset for: %s', this.src);

		new Asset.image
		(
			this.src,
			{
				onload: function(popup)
				{
					//
					// setup image
					//

					coord = this.element.getCoordinates();

					popup.addClass('pop-preview');

					popup.setStyles
					(
						{
							'position': 'absolute',
							'top': coord.top + (coord.height - popup.height) / 2 - 2,
							'left': coord.left + coord.width + 10
						}
					);

					popup.set('tween', { duration: 'short', link: 'cancel' });
					popup.set('opacity', 0);

					popup.addEvent('mouseenter', this.onMouseLeave.bind(this));

					if (this.popup)
					{
						//console.info('kill multiple for: %s', this.src);

						popup.destroy();

						return;
					}

					this.popup = popup;

					//
					// show
					//

					this.show();
				}
				.bind(this)
			}
		);
	},

	onMouseLeave: function()
	{
		this.cancel = true;

//		console.info('set cancel to true');

		this.hide();
	},

	show: function()
	{
		//console.info('show (%d) %a', this.cancel, this);

		if (this.cancel)
		{
			return;
		}

		//
		// clear 'title' attribute
		//

		var popup = this.popup;

		document.body.appendChild(popup);

		popup.fade('in');
	},

	hide: function()
	{
		var popup = this.popup;

		if (!popup)
		{
			return;
		}

		if (!popup.parentNode)
		{
			return;
		}

		popup.get('tween').start('opacity', 0).chain
		(
			function()
			{
				document.body.removeChild(popup);
			}
		);
	}
});

manager.addEvent
(
 	'ready', function()
	{
 		if (manager.blockName == 'manage')
 		{
			manager.element.getElements('a[rel="lightbox[]"]').each
			(
				function(el)
				{
					var children = el.getChildren();
	
					new WdPopupImage(children[0], children[1].value);
				}
			);
 		}

		Slimbox.scanPage();
	}
);