var WdAdjustNodesList  = new Class
({
	Implements: [ Options, Events ],
	
	options:
	{
		scope: 'system.nodes',
		name: null
	},
	
	initialize: function(el, options)
	{
		this.element = $(el);
		this.element.store('adjust', this);
		
		this.attachSearch(this.element.getElement('input.search'));
		
		this.setOptions(options);
		
		this.list = this.element.getElement('div.list ul');
		this.listHolder = this.list.getElement('li.holder');
		
		this.setScope(this.options.scope);
		this.attachResults();
		this.attachList();
	},
	
	attachSearch: function(el)
	{
		var self = this;
		var lastSearched = null;
		
		this.search = $(el);
		
		//
		// prevent form submission
		//
		
		this.search.addEvent
		(
			'keypress', function(ev)
			{
				if (ev.key == 'enter')
				{
					ev.stop();
				}
			}
		);

		//
		// search as you type
		//

		this.search.addEvent
		(
			'keyup', function(ev)
			{
				if (ev.key == 'esc')
				{
					this.value = '';
				}

				if (lastSearched === this.value)
				{
					return;
				}
				
				lastSearched = this.value;
				
				self.getResults({ search: this.value });
			}
		);
	},
	
	setScope: function(scope)
	{
		this.get_results_operation = null;
		
		if (!scope)
		{
			scope = 'system.nodes';
		}
		
		this.scope = scope;
	},
	
	getResults: function(options)
	{
		if (this.get_results_operation)
		{
			this.get_results_operation.cancel();
		}
		else
		{
			this.get_results_operation = new WdOperation
			(
				this.scope, 'getBlock',
				{
					onComplete: function(response)
					{
						var results = Elements.from(response.rc)[0];
						
						results.replaces(this.element.getElement('div.results'));
						
						this.attachResults();
						this.fireEvent('change', {});
					}
					.bind(this)
				}
			);
		}
		
		if (!options)
		{
			options = {};
		}
		
		options.name = 'adjustResults';
		
		this.get_results_operation.get(options);
	},
	
	attachResults: function()
	{
		var results = this.element.getElement('div.results');
		
		results.addEvent
		(
			'click', function(ev)
			{
				var target = ev.target;
				
				if (target.get('tag') == 'a')
				{
					var uri = new URI(target.get('href'));
					
					ev.stop();
					
					this.getResults({ page: uri.parsed.fragment, search: this.search.hasClass('empty') ? null : this.search.value });
				}
				else
				{
					if (target.match('button.add'))
					{
						target = target.getParent('li');
					}
					else if (target.get('tag') != 'li')
					{
						return;
					}
					
					ev.stop();
					
					this.add(target);
				}
			}
			.bind(this)
		);
		
		results.getElements('li').each
		(
			function(el)
			{
				var add = new Element
				(
					'button',
					{
						'class': 'add',
						type: 'button',
						html: '+'
					}
				);
				
				add.inject(el);
			}
		);
	},
	
	attachList: function()
	{
		this.sortable = new Sortables
		(
			this.list,
			{
				clone: true,
				constrain: true,
				opacity: 0.2,

				onStart: function(el, clone)
				{
					clone.setStyle('z-index', 10000);
				},
				
				onComplete: function()
				{
					this.fireEvent('change', {});
				}
				.bind(this)
			}
		);
		
		this.list.addEvent
		(
			'click', function(ev)
			{
				if (!ev.target.match('button.remove'))
				{
					return;
				}
				
				ev.stop();
				
				this.remove(ev.target.getParent('li'));
			}
			.bind(this)
		);
		
		var i = 0;
		
		this.list.getElements('li.sortable').each
		(
			function(el)
			{
				i++;
				this.attachListEntry(el);
			},
			
			this
		);
		
		this.listHolder[i ? 'hide' : 'show']();
	},
	
	attachListEntry: function(el)
	{
		var remove = new Element
		(
			'button',
			{
				'class': 'remove',
				type: 'button',
				html: '-'
			}
		);
		
		remove.inject(el);
		
		if (this.options.name)
		{
			var input = el.getElement('input.nid');
			
			input.name = this.options.name + '[]';
		}
	},
	
	add: function(nid)
	{
		if ($type(nid) == 'element')
		{
			nid = nid.getElement('input.nid').value;
		}
		
		var self = this;
		
		var op = new WdOperation
		(
			this.scope, 'adjustAdd',
			{
				onComplete: function(response)
				{
					if (!response.rc)
					{
						return;
					}

					var el = new Element
					(
						'li',
						{
							'class': 'sortable',
							'html': response.rc
						}
					);
					
					this.attachListEntry(el);

					el.inject(this.list);

					this.sortable.addItems(el);
					
					this.listHolder.hide();
					this.fireEvent('change', {});
				}
				.bind(this)
			}
		);

		op.get({ nid: nid });
	},
	
	remove: function(el)
	{
		this.sortable.removeItems(el).destroy();
		
		if (this.list.childNodes.length == 1)
		{
			this.listHolder.show();
		}
		
		this.fireEvent('change', {});
	}
});

window.addEvent
(
	'domready', function()
	{
		$$('div.wd-adjustnodeslist').each
		(
			function(el)
			{
				var options = el.getElement('input.wd-element-options');
				
				if (options)
				{
					options = JSON.decode(options.value);
				}
				
				new WdAdjustNodesList(el, options);
			}
		);
	}
);