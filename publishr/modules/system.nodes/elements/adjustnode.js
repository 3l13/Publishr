/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

var WdAdjustNode = new Class
({

	Implements: [ Options, Events ],

	options:
	{
		adjust: 'adjustnode',
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
		}
		else if (target.getParent('.pager'))
		{
			ev.stop();

			if (target.tagName != 'A')
			{
				target.getParent('a');
			}

			var page = target.get('href').split('#')[1];

			this.fetchSearchElement
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
					this.fetchSearchElement({ search: value });
				}

				searchLast = value;
			}
			.bind(this)
		);
	},

	fetchSearchElement: function(params)
	{
		if (!this.fetchSearchOperation)
		{
			this.fetchSearchOperation = new Request.Element
			({
				url: '/api/components/' + this.options.adjust + '/results',
				onSuccess: function(el, response)
				{
					el.replaces(this.element.getElement('.results'));

					document.fireEvent('elementsready', { target: el });

					this.fireEvent('results', { target: this, response: response });
				}
				.bind(this)
			});
		}

		if (this.selected)
		{
			params.selected = this.selected.get('data-nid');
		}

		params.constructor = this.options.constructor;

		this.fetchSearchOperation.get(params);
	}
});

var WdAdjustPopup = new Class
({
	Implements: [ Options, Events ],

	options:
	{
		target: null,
		targetMargin: 10,
		iframe: null
	},

	initialize: function(el, options)
	{
		this.setOptions(options);

		this.element = $(el);
		this.element.addClass('popup');

		this.arrow = this.element.getElement('div.arrow');
		this.selected = '';

		if (this.options.target)
		{
			this.attachTarget(this.options.target);
		}

		['cancel', 'continue', 'none'].each
		(
			function(mode)
			{
				this.element.getElement('button.' + mode).addEvent
				(
					'click', function(ev)
					{
						ev.stop();

						this.fireEvent('closeRequest', { mode: mode });
					}
					.bind(this)
				);
			},

			this
		);

		var adjustCallback = this.adjust.bind(this);

		window.addEvents
		({
			'resize': adjustCallback,
			'scroll': adjustCallback
		});

		if (this.options.iframe)
		{
			$(this.options.iframe.contentWindow).addEvents
			({
				'resize': adjustCallback,
				'scroll': adjustCallback
			});
		}
	},

	attachTarget: function(target)
	{
		this.target = $(target);
		this.options.target = this.target;
	},

	adjust: function()
	{
		if (!this.target)
		{
			return;
		}

		var pad = 50;

		var iframe = this.options.iframe;

		var tCoords = this.target.getCoordinates();
		var tX = tCoords.left;
		var tY = tCoords.top;
		var tH = tCoords.height;
		var tW = tCoords.width;

		//
		// adjust target width and height depending on the visible part of the target
		//

		var tBody = this.target.getParent('body');
		var tBodySize = tBody.getSize();
		var tBodyScroll = tBody.getScroll();
		var tBodyH = tBodySize.h;

		//tX -= tBodyScroll.x;

		if (iframe)
		{
			tY -= tBodyScroll.y;

			var tMaxH = tBodySize.y - tY + 1;

			//console.log('height: %d, tY: %d (tMaxH: %d), tBodyY: %d', tBodySize.y, tY, tMaxH, tBody.getScroll().y);

			tH = Math.min(tH, tMaxH);
		}

		//
		//
		//

		var size = this.element.getSize();
		var w = size.x;
		var h = size.y;

		//
		// adjust target X and Y depending on the iframe it is located in.
		//

		if (iframe)
		{
			var iPos = iframe.getPosition();

			tX += iPos.x;
			tY += iPos.y;
		}

		var x = tX + tW;
		var y = Math.round(tY + (tH - h) / 2);

		var body = $(document.body);
		var bodySize = body.getSize();
		var bodyScroll = body.getScroll();
		var bodyX = bodyScroll.x;
		var bodyY = bodyScroll.y;
		var bodyW = bodySize.x;
		var bodyH = bodySize.y;

		x += this.options.targetMargin;

		//
		// adjust X
		//

		var minX = bodyX + pad;
		var maxX = bodyX + bodyW - (w + pad);

		if (x > maxX)
		{
			x = maxX;
		}

		//
		// adjust Y
		//

		var minY = bodyY + pad;
		var maxY = bodyY + bodyH - (h + pad);

		if (y > maxY)
		{
			y = maxY;
		}

		y = Math.max(minY, y);

		//
		// adjust arrow
		//

		//console.log('y: %d, h: %d, tY: %d, tH: %d', y, h, tY, tH);

		var aH = this.arrow.getSize().y;

		var aY = (tY + tH / 2 - aH / 2) - y;

		//console.log('min aY: %d', this.element.getElement('div.confirm').getSize().y + aH);

		aY = Math.min(h - this.element.getElement('div.confirm').getSize().y - aH - 2, aY);
		aY = Math.max(10, aY);


		var visible = (this.element.getStyle('visibility') == 'visible');


		if (!visible || this.arrow.getPosition(this.element).y > h)
		{
			this.arrow.setStyle('top', aY);
		}
		else
		{
			this.arrow.tween('top', aY);
		}

		//
		//
		//

		var params = { left: x, top: y };

		visible ? this.element.morph(params) : this.element.setStyles(params);
	},

	close: function()
	{
		this.element.dispose();
	},

	open: function(options)
	{
//		this.selected = options ? options.selected : null;

		this.element.setStyle('visibility', 'hidden');

		document.body.appendChild(this.element);
		document.fireEvent('elementsready', { target: this.element });

		var adjust = this.element.retrieve('adjust');

		adjust.addEvent
		(
			'results', this.adjust.bind(this)
		);

		this.adjust();

		this.element.setStyle('visibility', '');
	}
});


document.addEvent
(
	'elementsready', function(ev)
	{
		$$('.wd-adjustnode').each
		(
			function(el)
			{
				if (el.retrieve('adjust'))
				{
					return;
				}

				var adjust = new WdAdjustNode(el);

				el.store('adjust', adjust);
			}
		);
	}
);