var WdAdjustNode = new Class
({

	Implements: [ Options, Events ],

	options:
	{
		target: null,
		targetMargin: 10,
		popup: false,
		iframe: null,
		scope: 'system.nodes'
	},

	initialize: function(el, options)
	{
		this.setOptions(options);

		this.element = $(el);
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
				)
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

		this.attachSearch();
		this.attachResults();
	},

	attachTarget: function(target)
	{
		this.target = $(target);
		this.options.target = this.target;
	},

	attachSearch: function()
	{
		var search = this.element.getElement('input.search');

		this.search = search;

		if (!search)
		{
			return;
		}

		//
		// prevent search submit
		//

		search.onsubmit = function() { return false; };

		var searchLast = null;

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

	attachResults: function()
	{
		var lines = this.element.getElements('li');

		this.element.getElements('li').each
		(
			function(el)
			{
				el.addEvent
				(
					'click', function(ev)
					{
						ev.stop();

						lines.removeClass('selected');
						el.addClass('selected');

						this.fireEvent('select', { entry: el });
						this.adjust();
					}
					.bind(this)
				);
			},

			this
		);

		//
		// pager
		//

		this.element.getElements('div.pager a').each
		(
			function(el)
			{
				el.addEvent
				(
					'click', function(ev)
					{
						ev.stop();

						var page = el.get('href').split('#')[1];

						this.fetchSearchElement({ page: page, search: (this.search && !this.search.hasClass('empty')) ? this.search.value : null });
					}
					.bind(this)
				);
			},

			this
		);
	},

	fetchSearchElement: function(params)
	{
		if (this.fetchSearchOperation)
		{
			this.fetchSearchOperation.cancel();
		}
		else
		{
			this.fetchSearchOperation = new WdOperation
			(
				this.options.scope, 'getBlock',
				{
					onComplete: function(response)
					{
						var el = Elements.from(response.rc)[0];
						var container = this.element.getElement('div.results');

						el.replaces(container);

						this.attachResults();

						this.adjust();
					}
					.bind(this)
				}
			);
		}

		/*
		var selected = null;

		if (this.target)
		{
			selected = this.targetKey ? this.targetKey.value : this.targetPreview.get('src');
		}
		*/

		this.fetchSearchOperation.get
		(
			Object.merge
			(
				{ name: 'adjustResults', selected: this.selected }, params
			)
		);
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
		var selected = options ? options.selected : 0;

		var list = this.element.getElements('li');

		list.removeClass('selected');

		list.each
		(
			function(el)
			{
				if (el.getElement('.nid').value != selected)
				{
					return;
				}

				el.addClass('selected');
			}
		);

		this.selected = selected;

		//
		//
		//

		var body = $(document.body);

		this.element.setStyle('visibility', 'hidden');

		body.adopt(this.element);

		if (this.options.popup)
		{
			this.element.addClass('popup');
			this.adjust();
		}

		this.element.setStyle('visibility', '');
	},

	toElement: function()
	{
		return this.element;
	}
});

/**
 *
 */

WdAdjustNode.fetchElement = function(selected, options)
{
	op = new WdOperation
	(
		options.scope || 'system.nodes', 'getBlock',
		{
			onComplete: function(response)
			{
				//console.log('response: %a', response);

				wd_update_assets
				(
					response.assets, function()
					{
						ai_opts = Object.merge(this.options ? this.options : {}, { popup: true });

						//console.log('assets loaded: %a, target: %a', response.assets, options.target);

						var adjust = new WdAdjustNode(Elements.from(response.rc).shift(), ai_opts);

						if (this.onLoad)
						{
							this.onLoad(adjust);
						}

						window.fireEvent('wd-element-ready', { element: adjust });
					}
					.bind(this)
				);
			}
			.bind(options)
		}
	);

	op.get({ name: 'adjust', selected: selected });
};


window.addEvent
(
	'domready', function()
	{
		$$('.wd-adjustnode').each
		(
			function(el)
			{
				var options = el.getElement('input.options');

				if (options)
				{
					options = JSON.decode(options.get('value'));

					console.log('options: %a', options);
				}

				new WdAdjustNode(el, options);
			}
		);
	}
);