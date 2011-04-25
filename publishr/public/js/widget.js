/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This is the namespace for all widgets constructors.
 */
var Widget = {};

/**
 * Widgets auto-constructor.
 *
 * On the 'elementsready' document event, constructors defined under the `Widget` namespace are
 * traversed and for each one of them, matching widgets are searched in the DOM and if the `widget`
 * property is not stored, a new widget is created using the constructor.
 *
 * Widgets are matched against a constructor based on the following naming convention: for a
 * "AdjustNode" constructor, the elements matching ".widget-adjust-node" are turned into widgets.
 */
document.addEvent
(
	'elementsready', function()
	{
		Object.each
		(
			Widget,
			(
				function(constructor, key)
				{
					var wclass = '.widget' + key.hyphenate();

					$$(wclass).each
					(
						function(el)
						{
							if (el.retrieve('widget'))
							{
								return;
							}

							var widget = new constructor(el, Dataset.get(el));

							el.store('widget', widget);
						}
					);
				}
			)
		);
	}
);

Request.Widget = new Class
({
	Extends: Request.Element,

	initialize: function(cl, onSuccess, options)
	{
		if (options == undefined)
		{
			options = {};
		}

		options.url = 'widgets/' + cl;
		options.onSuccess = onSuccess;

		this.parent(options);
	}
});

Widget.Popup = new Class
({
	Implements: [ Options, Events ],

	options:
	{
		anchor: null,
		iframe: null
	},

	initialize: function(el, options)
	{
		this.setOptions(options);

		this.element = $(el);
		this.element.addClass('popup');
		this.element.addClass('hidden');

		this.arrow = this.element.getElement('div.arrow');

		if (!this.arrow)
		{
			this.arrow = new Element('div.arrow').adopt(new Element('div'));

			this.arrow.inject(el);
		}

		if (this.options.anchor)
		{
			this.attachAnchor(this.options.anchor);
		}

		this.repositionCallback = this.reposition.bind(this);
	},

	attachAnchor: function(anchor)
	{
		this.anchor = $(anchor);
		this.options.anchor = this.anchor;
	},

	changePositionClass: function(position)
	{
		this.element.removeClass('before');
		this.element.removeClass('after');
		this.element.removeClass('above');
		this.element.removeClass('below');

		this.element.addClass(position);
	},

	reposition: function()
	{
		var anchor = this.anchor;

		if (!anchor)
		{
			return;
		}

		var tCoords = anchor.getCoordinates();
		var tX = tCoords.left;
		var tY = tCoords.top;
		var tH = tCoords.height;
		var tW = tCoords.width;

		var tBody = anchor.getParent('body');

		if (!tBody)
		{
			return;
		}

		var tBodySize = tBody.getSize();
		var tBodyScroll = tBody.getScroll();
		var tBodyH = tBodySize.y;

//		console.log('tBodySize: %a; tBodyScroll: %a, tBodyH: %d', tBodySize, tBodyScroll, tBodyH);

//		console.log('anchor: %dx%d', tW, tH);

		var iframe = this.options.iframe;

		if (iframe)
		{
			var frameCoords = iframe.getCoordinates();

//			console.log('iframe coordinates: %a', iframe.getCoordinates());

			tH = Math.min(frameCoords.height, tH);

			tX -= tBodyScroll.x;
			tY -= tBodyScroll.y;

			var tMaxH = tBodySize.y - tY + 1;

			//console.log('height: %d, tY: %d (tMaxH: %d), tBodyY: %d', tBodySize.y, tY, tMaxH, tBody.getScroll().y);

			tH = Math.min(tH, tMaxH); // visible height of the anchor

			tX += frameCoords.left;
			tY += frameCoords.top;
		}

		var anchorMiddleX = tX + tW / 2;
		var anchorMiddleY = tY + tH / 2;

		var body = $(document.body);
		var bodySize = body.getSize();
		var bodyScroll = body.getScroll();
		var bodyX = bodyScroll.x;
		var bodyY = bodyScroll.y;
		var bodyW = bodySize.x;
		var bodyH = bodySize.y;

//		console.log('anchor: %d:%d, %dx%d, body: %dx%d, relative: %a', tX, tY, tW, tH, bodyW, bodyH, anchor.getCoordinates(body));

		var size = this.element.getSize();
		var w = size.x;
		var h = size.y;

		var x;
		var y = Math.round(tY + (tH - h) / 2);

		if (anchorMiddleX > bodyX + bodyW / 2)
		{
			this.changePositionClass('before');

			x = tX - w;
		}
		else
		{
			this.changePositionClass('after');

			x = tX + tW;
		}

		var pad = 50;

		//
		// adjust X
		//

		var minX = bodyX + pad;
		var maxX = bodyX + bodyW - (w + pad);

		if (x > maxX)
		{
			x = maxX;
		}

		x = Math.max(minX, x);

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

		var arrowH = this.arrow.getSize().y;
		var aY = (tY + tH / 2 - arrowH / 2) - y;

		//console.log('min aY: %d', this.element.getElement('div.confirm').getSize().y + aH);

		var confirm = this.element.getElement('div.confirm');

		aY = Math.min(h - (confirm ? confirm.getSize().y : 0) - arrowH - 10, aY);
		aY = Math.max(50, aY);

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

	open: function()
	{
		var el = this.element;

		el.addClass('hidden');

		document.body.appendChild(el);
		document.fireEvent('elementsready', { target: document.body });

		window.addEvents
		({
			'resize': this.repositionCallback,
			'scroll': this.repositionCallback
		});

		if (this.options.iframe)
		{
			$(this.options.iframe.contentWindow).addEvents
			({
				'resize': this.repositionCallback,
				'scroll': this.repositionCallback
			});
		}

		this.reposition();
		el.removeClass('hidden');
	},

	close: function()
	{
		this.element.removeEvent('adjust', this.repositionCallback);
		this.element.addClass('hidden');
		this.element.dispose();

		window.removeEvent('resize', this.repositionCallback);
		window.removeEvent('scroll', this.repositionCallback);

		if (this.options.iframe)
		{
			var contentWindow = $(this.options.iframe.contentWindow);

			contentWindow.removeEvent('resize', this.repositionCallback);
			contentWindow.removeEvent('scroll', this.repositionCallback);
		}
	}
});

/**
 * The "elementsready" event is fired for elements to be initialized, to become alive thanks to the
 * magic of Javascript. This event is usually fired when new widgets are added to the DOM.
 */
window.addEvent
(
	'domready', function()
	{
		document.fireEvent('elementsready', { target: $(document.body) });
	}
);