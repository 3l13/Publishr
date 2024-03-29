/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

Widget.AdjustNode = new Class
({
	Implements: [ Options, Events ],

	options:
	{
		adjust: 'adjust-node',
		constructor: 'system.nodes'
	},

	initialize: function(el, options)
	{
		this.element = $(el);

		this.setOptions(options);
		this.setOptions(Dataset.get(this.element));

		this.element.addEvent('click', this.uberOnClick.bind(this));
		this.selected = this.element.getElement('.results li.selected');
		this.attachSearch();
	},

	uberOnClick: function(ev)
	{
		var target = ev.target;
		var el = target;

		if (target.tagName != 'LI')
		{
			el = target.getParent('li');
		}

		if (el)
		{
			ev.stop();

			this.element.getElements('.results li').removeClass('selected');
			el.addClass('selected');
			this.selected = el;
			this.fireEvent('select', { target: el, event: ev });
			this.fireEvent('change', { target: el, widget: this, event: ev });
		}
		else if (target.getParent('.pager'))
		{
			ev.stop();

			if (target.tagName != 'A')
			{
				target = target.getParent('a');
			}

			var page = target.get('href').split('#')[1];

			this.fetchResults
			({
				page: page,
				search: (this.search && !this.search.hasClass('empty')) ? this.search.value : null
			});
		}
	},

	attachSearch: function()
	{
		var search = this.search = this.element.getElement('input.search');
		var searchLast = null;

		search.onsubmit = function() { return false; };

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
					this.fetchResults({ search: value });
				}

				searchLast = value;
			}
			.bind(this)
		);
	},

	fetchResults: function(params)
	{
		if (!this.fetchResultsOperation)
		{
			this.fetchResultsOperation = new Request.Element
			({
				url: '/api/widgets/' + this.options.adjust + '/results',
				onSuccess: function(el, response)
				{
					el.replaces(this.element.getElement('.results'));

					if (!this.selected)
					{
						this.selected = this.element.getElement('.results li.selected');
					}

					document.fireEvent('elementsready', { target: el });

					this.fireEvent('results', { target: this, response: response });
				}
				.bind(this)
			});
		}

		if (this.selected && !params.selected)
		{
			params.selected = this.selected.get('data-nid');
		}

		params.constructor = this.options.constructor;

		this.fetchResultsOperation.get(params);
	},

	setSelected: function(selected)
	{
		this.fetchResults({selected: selected });
	}
});

Widget.Popup.Adjust = new Class
({
	Implements: [ Options, Events ],
	Extends: Widget.Popup,

	initialize: function(el, options)
	{
		this.parent(el, options);

		this.selected = '';

		['cancel', 'continue', 'none'].each
		(
			function(mode)
			{
				var el = this.element.getElement('button.' + mode);

				if (!el)
				{
					return;
				}

				el.addEvent
				(
					'click', function(ev)
					{
						ev.stop();

						this.fireEvent('closeRequest', { target: this, mode: mode });
					}
					.bind(this)
				);
			},

			this
		);
	},

	open: function()
	{
		this.parent();

		var widget = this.element.getElement('.adjust');

		this.adjust = widget.retrieve('adjust');

		if (!this.adjust)
		{
			this.adjust = widget.retrieve('widget');
		}

		if (this.adjust)
		{
			this.adjust.addEvent('results', this.repositionCallback);
			this.adjust.addEvent('adjust', this.repositionCallback);
		}
	}
});