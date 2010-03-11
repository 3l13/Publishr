var WdPopImage = new Class
({
	initialize: function(el)
	{
		this.element = $(el);
		
		this.element.addEvent
		(
			'click', this.fetchAdjust.bind(this)
		);
	},
	
	fetchAdjust: function()
	{
		var img = this.element.getElement('img');
		var key = this.element.getElement('input[type=hidden]');
		
		this.src_back = img.get('src');
		this.key_back = key.value;
		
		if (this.adjust)
		{
			this.adjust.open();
			
			return;
		}
		
		if (this.fetchAdjustOperation)
		{
			this.fetchAdjustOperation.cancel();
		}
		else
		{
			this.fetchAdjustOperation = new WdOperation
			(
				'resources.images', 'getBlock',
				{
					onComplete: function(response)
					{
						//console.log('response: %a', response);
						
						wd_update_assets
						(
							response.assets, function()
							{
								var adjust = Elements.from(response.rc)[0];
																
								this.adjust = new WdAdjustImage
								(
									adjust,
									{
										target: this.element,
										popup: true
									}
								);
								
								this.adjust.addEvent
								(
									'closeRequest', function(ev)
									{
										switch (ev.mode)
										{
											case 'cancel':
											{
												img.src = this.src_back;
												key.value = this.key_back;
											}
											break;
											
											case 'none':
											{
												img.src = '';
												img.alt = 'Aucune image sélectionnée';
												key.value = '';
											}
											break;
										}
										
										this.adjust.close();
									}
									.bind(this)
								);
								
								this.adjust.open();
								
								window.fireEvent('wd-element-ready', { element: adjust });
							}
							.bind(this)
						);
					}
					.bind(this)
				}
			);
		}

		this.fetchAdjustOperation.get({ name: 'adjust', value: this.element.getElement('input[type=hidden]').value });
	}
});

window.addEvent
(
	'domready', function()
	{
		$$('.wd-popimage').each
		(
			function(el)
			{
				new WdPopImage(el);
			}	
		);
	}
);