var WdPopNode = new Class
({
	Implements: [ Options ],
	
	options:
	{
		scope: 'system.nodes',
		emptyLabel: 'No entry selected'
	},
	
	initialize: function(el, options)
	{
		this.element = $(el);
		this.element.store('wd-pop', this);
		
		this.setOptions(options);
		
		this.element.addEvent
		(
			'click', this.fetchAdjust.bind(this)
		);
	},
	
	fetchAdjust: function()
	{
		var title_el = this.element.getElement('span.title');
		var key_el = this.element.getElement('input.key');
		var preview_el = this.element.getElement('img');
		
		this.title_back = title_el.get('html');
		this.key_back = key_el.value;
		
		if (preview_el)
		{
			this.preview_back = preview_el.get('src');
		}
		
		if (this.adjust)
		{
			this.adjust.open({ selected: key_el.value });
			
			return;
		}
		
		if (preview_el)
		{
			preview_el.addEvent
			(
				'load', function()
				{
					if (!this.adjust)
					{
						return;
					}
					
					this.adjust.adjust();
				}
				.bind(this)
			);
		}
		
		if (this.fetchAdjustOperation)
		{
			this.fetchAdjustOperation.cancel();
		}
		//else
		{
			this.fetchAdjustOperation = new WdOperation
			(
				this.options.scope, 'getBlock',
				{
					onComplete: function(response)
					{
						//console.log('response: %a', response);
					
						wd_update_assets
						(
							response.assets, function()
							{
								var adjust = Elements.from(response.rc).shift();
																
								this.adjust = new WdAdjustNode
								(
									adjust,
									{
										target: this.element,
										popup: true,
										scope: this.options.scope
									}
								);
								
								this.adjust.addEvent
								(
									'select', function(ev)
									{
										var entry = ev.entry;
										var entry_title_el = entry.getElement('.title');
										var entry_preview_el = entry.getElement('.preview');
										
										title_el.set('text', entry_title_el.get('text'));
										title_el.set('title', entry_title_el.get('title'));
										key_el.set('value', ev.entry.getElement('.nid').get('value'));
										
										if (preview_el && entry_preview_el)
										{
											preview_el.src = WdOperation.encode
											(
												'thumbnailer', 'get',
												{
													src: entry_preview_el.get('value'),
													w: 64,
													h: 64,
													method: 'surface',
													format: 'png'
												}
											);
										}
										
										this.element.removeClass('empty');
									}
									.bind(this)
								);
								
								this.adjust.addEvent
								(
									'closeRequest', function(ev)
									{
										switch (ev.mode)
										{
											case 'cancel':
											{
												title_el.set('html', this.title_back);
												key_el.value = this.key_back;
												
												if (preview_el)
												{
													preview_el.set('src', this.preview_back);
												}
											}
											break;
											
											case 'none':
											{
												title_el.set('html', '<em>' + this.options.emptyLabel + '</em>');
												key_el.value = '';
												
												if (preview_el)
												{
													preview_el.set('src', '');
												}
											}
											// continue

											case 'continue':
											{
												key_el.fireEvent('change', ev);
											}
											break;
										}
										
										this.element[(key_el.value ? 'remove' : 'add') + 'Class']('empty');
										
										this.adjust.close();
									}
									.bind(this)
								);
								
								this.adjust.open({ selected: key_el.value });
								
								window.fireEvent('wd-element-ready', { element: adjust });
							}
							.bind(this)
						);
					}
					.bind(this)
				}
			);
		}

		this.fetchAdjustOperation.get({ name: 'adjust', value: key_el.value });
	},
	
	setScope: function(scope)
	{
		this.options.scope = scope;
		
		// TODO-20100609: c'est vilain, l'objet 'adjust' devrait supporter le changement de portée
		
		if (this.adjust)
		{
			this.adjust.close();
			
			delete this.adjust;
			
			this.adjust = null;
			
			this.fetchAdjust();
		}
	}
});

WdPopNode.scanPage = function()
{
	$$('.wd-popnode').each
	(
		function(el)
		{
			if (el.retrieve('wd-pop'))
			{
				return;
			}
			
			var options = el.getElement('input.options');
			
			if (options)
			{
				options = JSON.decode(options.get('value'));
			}
			
			new WdPopNode(el, options);
		}	
	);
};

window.addEvent
(
	'domready', WdPopNode.scanPage
);