/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

"use strict";

var WdGauge = new Class
({
	Implements: Options,

	options:
	{
		min: 0,
		max: 100
	},

	initialize: function(options)
	{
		this.setOptions(options);

		this.element = new Element
		(
			'div',
			{
				'class': 'gauge'
			}
		);

		this.gauge = new Element
		(
			'div',
			{
				'class': 'bar'
			}
		);

		this.set(0);
		this.element.appendChild(this.gauge);
	},

	set: function(value)
	{
		var max = this.options.max;
		var min = this.options.min;

		var percentage = 1 - (max - value + min) / (max - min);

		this.gauge.setStyle('width', 100 * percentage + '%');
	},

	destroy: function()
	{
		this.element.destroy();
	}
});

var WdManager = new Class
({
	Implements: [ Events ],

	initialize: function()
	{
		var menuOptions = $('menu-options');

		var search = menuOptions.getElement('.manage .search');

		//
		// prevent search submit
		//

		search.onsubmit = function() { return false; };

		var searchLast = null;
		
		search.addEvent
		(
			'keydown', function(ev)
			{
				if (ev.key == 'enter')
				{
					ev.stop();
				}
			}
		);
		
		search.addEvent
		(
			'keyup', function(ev)
			{
				if (ev.key == 'esc')
				{
					ev.target.value = '';
				}
				
				value = ev.target.value;
				
				if (value != searchLast)
				{
					this.getBlock({ search: value });
				}
				
				searchLast = value;
			}
			.bind(this)
		);

		//
		//
		//

		this.browseNext = menuOptions.getElement('.browse.next');
		
		if (this.browseNext)
		{
			this.browseNext.addEvent('click', this.handleBrowse.bind(this));
		}
		
		this.browsePrevious = menuOptions.getElement('.browse.previous');

		if (this.browsePrevious)
		{
			this.browsePrevious.addEvent('click', this.handleBrowse.bind(this));
		}
	},

	handleBrowse: function(ev)
	{
		ev.stop();

		var uri = new URI(ev.target.href);

		var start = uri.getData('start');

		this.getBlock({ start: start });
	},

	getBlock: function(params)
	{
		if (this.op)
		{
			this.op.cancel();
		}

		this.op = new WdOperation
		(
			this.destination, 'getBlock',
			{
				onRequest: function()
				{
					if (spinner)
					{
						spinner.start();
					}
				},

				onCancel: function()
				{
					if (spinner)
					{
						spinner.finish();
					}
				},

				onComplete: function(response)
				{
					if (spinner)
					{
						spinner.finish();
					}
					/*
					var wrapper = this.element.getParent();
					var parent = this.element.getParent();

					this.element = null;
					*/

					var el = Elements.from(response.rc)[0];

					//el.inject(parent, 'top');

					el.replaces($('manager'));

					this.attach(el);
				}
				.bind(this)
			}
		);
		
		this.op.get
		(
			$merge
			(
				{ name: this.blockName }, params
			)
		);
	},

	attach: function(el)
	{
		this.element = $(el);
		this.parentElement = this.element.getParent(); // FIXME: WHAT FOR ? is this supposed to be the wrapper ?
		this.destination = this.element['#destination'].value;
		this.blockName = this.element['#manager-block'].value;
		
		//
		// handle jobs
		//

		var jobs = this.element.getElement('div.jobs');

		if (jobs)
		{
			jobs.set('opacity', 0);

			//
			// when jobs are clicked, they trigger an operation
			//

			jobs.getElement('select').addEvent
			(
				'change', function(ev)
				{
					var operation = this.get('value');

					if (!operation)
					{
						return;
					}
					
					var entries = manager.getSelectedEntries();

					manager.queryOperation(operation, entries);
				}
			);
		}
		
		//
		// link checkboxes
		//

		var checkboxes = this.element.getElements('tbody .key input[type=checkbox]');
		var checkboxes_master = this.element.getElement('tfoot .key input[type=checkbox]');

		if (checkboxes.length)
		{
			var form = checkboxes_master.form;

			//
			// if the [alt] key is pressed the boxes are toggled
			//

			var toggleBoxes = function(ev)
			{
				checkboxes.each
				(
					function(box)
					{
						box.click();
					}
				);
			};

			checkboxes_master.getParent().addEvent
			(
				'click', function(ev)
				{
					if (ev.alt)
					{
						toggleBoxes(ev);
					}
				}
			);
			
			//
			// toggle boxes when the master is clicked:
			//

			checkboxes_master.addEvent
			(
				'click', function(ev)
				{
					var checked = this.checked;

					if (ev.alt)
					{
						toggleBoxes();
					}
					else
					{
						//
						// - if the master is checked, all the boxes are checked too
						// - if the master is unchecked, all the boxes are uncheked too
						//

						checkboxes.each
						(
							function(box)
							{
								if (box.checked != checked)
								{
									box.click();
								}
							}
						);
					}
				}
			);

			/**
			 * Each row is bond to its selection checkbox. When a row is clicked, its selected state is toggled
			 * by toggling its selection box.
			 */
			
			manager.element.addEvent
			(
				'click', function(ev)
				{
					var target = ev.target;
					
					if (target.get('tag') != 'a')
					{
						var parent = target.getParent('td');
						
						if (parent)
						{
							target = parent;
						}
					}
					
					if (target.get('tag') != 'td')
					{
						return;
					}
					
					target = target.getParent();
					
					if (!target.hasClass('entry'))
					{
						return;
					}
					
					target = target.getElement('td.key input');
					
					if (!target)
					{
						return;
					}
					
					target.click();
				}
			);
			
			/**
			 * The selection checkboxes are bond to the operation selector.
			 * 
			 * The operation selector appears when at least one of the selection checkboxes is
			 * checked and disapears when all the selection checkboxes are unchecked. 
			 */
			
			manager.element.addEvent
			(
				'click', function(ev)
				{
					var target = ev.target;
					
					if (checkboxes.indexOf(target) == -1)
					{
						// the target is not one of our selection checkboxes
						
						return;
					}
					
					var count = 0;

					checkboxes.each
					(
						function(el)
						{
							if (el.checked)
							{
								count++;
							}
						}
					);

					jobs.get
					(
						'tween',
						{
							property: 'opacity',
							duration: 'short',
							link: 'cancel'
						}
					)
					.start(count ? 1 : 0);
				}
			);
		}

		//
		// browse
		//

		var browseNext = this.element.getElement('.browse.next');

		if (browseNext && this.browseNext)
		{
			this.browseNext.href = browseNext.href;

			browseNext.addEvent
			(
				'click', this.handleBrowse.bind(this)
			);
		}

		var browsePrevious = this.element.getElement('.browse.previous');

		if (browsePrevious && this.browsePrevious)
		{
			this.browsePrevious.href = browsePrevious.href;

			browsePrevious.addEvent
			(
				'click', this.handleBrowse.bind(this)
			);
		}

		//
		// start and limit
		//

		var start = this.element.getElement('input[name=start]');

		if (start)
		{
			start.addEvent
			(
				'keypress', function(ev)
				{
					if (ev.key != 'enter')
					{
						return;
					}

					ev.stop();

					manager.getBlock({ start: this.value });
				}
			);
		}

		var limit = this.element.getElement('select[name=limit]');

		if (limit)
		{
			limit.onchange = function()
			{
				manager.getBlock({ limit: this.value });
			};
		}
		
		//
		// filters
		//
		
		this.element.addEvent
		(
			'click', function(ev)
			{
				var target = ev.target;
				var parent = target.getParent('a');
				
				if (parent)
				{
					target = parent;
				}
				
				if (target.get('tag') != 'a' || (!target.hasClass('filter') && !target.hasClass('ajaj') && target.getParent().get('tag') != 'th'))
				{
					return;
				}
				
				ev.stop();
				
				var params = target.get('href').substring(1).parseQueryString();
				
				manager.getBlock(params);
			}
		);
		
		//
		//
		//
		
		this.attachOperations();

		//
		//
		//

		if (0)
		{
			master.fireEvent('click', {});

			this.queryOperation('delete');
		}

		this.fireEvent('ready', {});
	},
	
	attachOperations: function()
	{
		var operationsMenu = null;
		var operationsMenuTarget = null;
		
		var getOperationMenu = function()
		{
			if (operationsMenu)
			{
				return operationsMenu
			}
				
			var children = [];
				
			manager.element.getElements('select[name=jobs] option').each
			(
				function(el)
				{
					var operation = el.get('value');
					
					if (!operation)
					{
						return;
					}
					
					var li = new Element
					(
						'li',
						{
							'class': operation,
							'html': el.get('html')
						}
					);
					
					children.push(li);
					
					li.addEvent
					(
						'click', function(ev)
						{
							//ev.stop();
							
							var operation = this.get('class');
							var key = operationsMenuTarget.getParent('tr').getElement('td.key input[type=checkbox]').get('value');
							
							closeOperationsMenu();
							
							manager.queryOperation(operation, [ key ]);
						}
					);
				}
			);
			
			operationsMenu = new Element
			(
				'ul',
				{
					id: 'manager-operations-menu'
				}
			);
			
			operationsMenu.adopt(children);

			operationsMenu.hide();
			operationsMenu.setStyle('visibility', 'hidden');
			
			operationsMenu.inject(document.body);
		};
		
		var closeOperationsMenu = function()
		{
			if (operationsMenuTarget)
			{
				operationsMenuTarget.setStyle('visibility', '');
			}
			
			operationsMenuTarget = null;
			
			if (!operationsMenu)
			{
				return;
			}
			
			operationsMenu.hide();
			operationsMenu.setStyle('visibility', 'hidden');
		};
		
		var openOperationsMenu = function(trigger)
		{
			operationsMenuTarget = trigger;
			
			trigger.setStyle('visibility', 'visible');
			
			getOperationMenu();
			
			operationsMenu.show();
			
			var coords = trigger.getCoordinates();
									
			operationsMenu.setStyles
			({
				top: coords.top + coords.height + 5,
				left: coords.left + coords.width - operationsMenu.getSize().x
			});
			
			operationsMenu.setStyle('visibility', 'visible');
		};
		
		this.element.getElements('td.operations a').each
		(
			function(el)
			{
				el.addEvent
				(
					'click', function(ev)
					{
						ev.stop();
						
						if (operationsMenuTarget)
						{
							if (operationsMenuTarget == el)
							{
								closeOperationsMenu();
								
								return;
							}
							else
							{
								operationsMenuTarget.setStyle('visibility', '');
							}
						}
						
						openOperationsMenu(el);
					}
				);
			}
		);
	},

	getSelectedEntries: function()
	{
		var entries = [];

		this.element.getElements('.key input[type=checkbox]').each
		(
			function(el)
			{
				if (!el.checked || el.value == 'on')
				{
					return;
				}

				entries.push(el.value);
			}
		);

		return entries;
	},

	queryOperation: function(operation, entries)
	{
		this.element.set('slide', {duration: 'short'});

		var op = new WdOperation
		(
			this.destination, 'queryOperation',
			{
				onRequest: function()
				{
					if (spinner)
					{
						spinner.start();
					}
				},

				onCancel: function()
				{
					if (spinner)
					{
						spinner.finish();
					}
				},

				onSuccess: function(response)
				{
					if (spinner)
					{
						spinner.finish();
					}

					var rc = response.rc;
					var html = '';

					if (!rc)
					{
						alert('Uknown operation: "' + operation + '"');

						return;
					}

					html += '<h3>' + rc.title + '</h3>';

					html += '<div class="confirm">';
					html += '<p>' + rc.message + '</p>';
					html += '<button name="cancel">' + rc.confirm[0] + '</button>';
					html += '<span class="spacer">&nbsp;</span>';
					html += '<button name="ok" class="warn">' + rc.confirm[1] + '</button>';
					html += '</div>';

					this.container = new Element
					(
						'div',
						{
							'id': 'manage-job',
							'class': 'group',
							'html': html
						}
					);

					this.containerWrapper = new Element
					(
						'div',
						{
							'class': 'wrapper',
							'styles':
							{
								'overflow': 'hidden'
							}
						}
					);

					this.container.inject(this.containerWrapper);

					this.container.set('slide', { duration: 'short', wrapper: this.containerWrapper });
					this.container.store('wrapper', this.containerWrapper);

					this.container.getElement('button[name=cancel]').addEvent
					(
						'click', this.cancelOperation.bind(this)
					);

					this.container.getElement('button[name=ok]').addEvent
					(
						'click', function()
						{
							var confirm = this.container.getElement('div.confirm');

							confirm.get('tween', {property: 'opacity', duration: 'short'}).start(0).chain
							(
								function()
								{
									confirm.destroy();

									this.startOperation(operation, rc.params);
								}
								.bind(this)
							);
						}
						.bind(this)
					);

					this.element.get('slide').slideOut().chain
					(
						function()
						{
							//
							// insert just after the element wrapper
							//

							this.container.slide('hide');
							this.containerWrapper.inject(this.element.getParent(), 'after');
							this.container.slide('in');
						}
						.bind(this)
					);
				}
				.bind(this)
			}
		);

		op.post
		({
			operation: operation,
			entries: entries
		});
	},

	cancelOperation: function()
	{
		//
		// reset job selector's value
		//
		
		this.element.getElement('div.jobs select').set('value', '');

		this.container.get('slide').slideOut().chain
		(
			function()
			{
				this.containerWrapper.destroy();
				this.containerWrapper = null;
				this.container = null;

				this.element.slide('in');
			}
			.bind(this)
		);
	},

	startOperation: function(operation, params)
	{
		var progress = new Element('div', { 'class': 'progress' });

		progress.set('tween', { property: 'opacity' });
		progress.set('opacity', 0);

		var gauge = new WdGauge({ max: params.entries.length });

		gauge.element.inject(progress);

		var message = new Element('p');

		message.inject(progress);

		progress.inject(this.container);

		progress.get('tween').start(1);

		/* iterator */

		var entries = params.entries;
		var iterations = entries.length;

		var iterator = function()
		{
			var entry = entries.pop();

			if (!entry)
			{
				progress.get('tween', {property: 'opacity', duration: 'short'}).start(0).chain
				(
					function()
					{
						progress.destroy();

						this.finishOperation(operation, 'Operation complete !');
					}
					.bind(this)
				);

				return;
			}

			var op = new WdOperation
			(
				this.destination, operation,
				{
					onSuccess: function(response)
					{
						var log = response.log;

						//
						// if there is no result for the operation, we abort the operation
						//

						if (!response.rc || log.error.length)
						{
							entries = [];

							progress.destroy();

							this.finishOperation(operation, log.error);

							return;
						}

						//
						// update the progress bar and message
						//

						gauge.set(iterations - entries.length);

						if (log.done.length)
						{
							message.set('html', log.done.join('<br />'));
						}

						iterator();
					}
					.bind(this)
				}
			);

			op.post({ '#key': entry });
		}
		.bind(this);

		iterator();
	},

	finishOperation: function(operation, message)
	{
		var el = new Element
		(
			'div',
			{
				'class': 'finish',
				'html': '<p>' + message + '</p><div class="confirm"><button name="ok" class="continue">Ok</button></div>'
			}
		);

		el.getElement('button').addEvent
		(
			'click', function()
			{
				this.container.get('slide').slideOut().chain
				(
					function()
					{
						el.destroy();

						this.containerWrapper.destroy();
						this.containerWrapper = null;
						this.container = null;

						var op = new WdOperation
						(
							this.destination, 'getBlock',
							{
								onSuccess: function(response)
								{
									var wrapper = this.element.getParent();
									var parent = wrapper.getParent();

									wrapper.destroy();

									this.element = null;

									var temp = new Element('div', { 'html': response.rc });

									var el = temp.getFirst();

									el.inject(parent, 'top');

									/*
									if (typeof Slimbox != 'undefined')
									{
										Slimbox.scanPage();
									}

									//new WdManager(el);
									*/

									this.attach(el);
								}
								.bind(this)
							}
						);

						op.options.url += '?start=1&search=';
						op.post({ name: this.blockName });
					}
					.bind(this)
				);
			}
			.bind(this)
		);

		el.fade('hide');
		el.inject(this.container);
		el.fade('in');
	}
});

var manager = new WdManager();

window.addEvent
(
	'domready', function()
	{
		manager.attach('manager');
	}
);

manager.addEvent
(
	'ready', function()
	{
		manager.element.getElements('label.checkbox-wrapper').each
		(
			function (el)
			{
				var checkbox = el.getElement('input');

				if (checkbox.checked)
				{
					el.addClass('checked');
				}

				if (checkbox.disabled)
				{
					el.addClass('disabled');
				}

				if (checkbox.readonly)
				{
					el.addClass('readonly');
				}

				checkbox.addEvent
				(
					'change', function()
					{
						this.checked ? el.addClass('checked') : el.removeClass('checked');
					}
				);
			}
		);
	}
);