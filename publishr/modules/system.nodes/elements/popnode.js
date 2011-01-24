/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

var WdPopNode = new Class
({
	Implements: [ Options ],

	options:
	{
		placeholder: 'Select an entry',
		constructor: 'system.nodes',
		adjust: 'adjustnode'
	},

	initialize: function(el, options)
	{
		this.element = $(el);

		this.setOptions(options);
		this.setOptions(Dataset.get(this.element));

		this.element.addEvent
		(
			'click', this.fetchAdjust.bind(this)
		);

		this.title_el = this.element.getElement('span.title');
		this.key_el = this.element.getElement('input.key');
		this.preview_el = this.element.getElement('img');
	},

	fetchAdjust: function()
	{
		this.title_back = this.title_el.get('html');
		this.key_back = this.key_el.value;

		var preview_el = this.preview_el;

		if (preview_el)
		{
			this.preview_back = preview_el.get('src');
		}

		if (this.popup)
		{
			this.popup.open({ selected: this.key_el.value });

			return;
		}

		if (preview_el)
		{
			preview_el.addEvent
			(
				'load', function()
				{
					if (!this.popup)
					{
						return;
					}

					this.popup.adjust();
				}
				.bind(this)
			);
		}

		if (!this.fetchAdjustOperation)
		{
			this.fetchAdjustOperation = new Request.Element
			({
				url: '/api/components/' + this.options.adjust,
				onSuccess: this.setupAdjust.bind(this)
			});
		}

		this.fetchAdjustOperation.get({ selected: this.key_el.value, constructor: this.options.constructor });
	},

	setupAdjust: function(adjustElement, response)
	{
		this.popup = new WdAdjustPopup
		(
			adjustElement,
			{
				target: this.element
			}
		);

		this.popup.open();

		/*
		 * The adjust object is available after the `elementsready` event has been fired. The event
		 * is fired when the popup is opened.
		 */

		var adjust = adjustElement.retrieve('adjust');

		var title_el = this.title_el;
		var key_el = this.key_el;
		var preview_el = this.preview_el;

		adjust.addEvent
		(
			'select', function(ev)
			{
				var entry = ev.target;

				var entry_nid = entry.get('data-nid');
				var entry_title = entry.get('data-title');
				var entry_path = entry.get('data-path');

				title_el.set('text', entry_title);
				title_el.set('title', entry_title);
				key_el.set('value', entry_nid);

				if (preview_el && entry_nid)
				{
					preview_el.src = '/api/resources.images/' + entry_nid + '/thumbnail?w=64&h=64&m=surface&f=png';
				}

				this.element.removeClass('empty');
				this.popup.adjust();
			}
			.bind(this)
		);

		this.popup.addEvent
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
						title_el.set('html', '<em>' + this.options.placeholder + '</em>');
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

				this.element[(0 + key_el.value.toInt() ? 'remove' : 'add') + 'Class']('empty');

				this.popup.close();
			}
			.bind(this)
		);
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

			var pop = new WdPopNode(el);

			el.store('wd-pop', pop);
		}
	);
};

window.addEvent('domready', WdPopNode.scanPage);