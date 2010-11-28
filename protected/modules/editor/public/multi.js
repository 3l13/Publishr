Element.implement
({
	toValues: function()
	{
		var values = {};

		this.getElements('input, select, textarea', true).each
		(
			function(el)
			{
				if (!el.name || el.disabled || el.type == 'submit' || el.type == 'reset' || el.type == 'file') return;

				var value = (el.tagName.toLowerCase() == 'select')
					? Element.getSelected(el).map
						(
							function(opt)
							{
								return opt.value;
							}
						)
					: ((el.type == 'radio' || el.type == 'checkbox') && !el.checked) ? null : el.value;

				Array.from(value).each
				(
					function(val)
					{
						if (typeof val != 'undefined')
						{
							values[el.name] = val;
						}
					}
				);
			}
		);

		return values;
	}
});

var WdContentsEditor = new Class
({
	Implements: [ Options ],

	options:
	{
		contentsName: 'contents',
		SelectorName: 'editor'
	},

	initialize: function(el, options)
	{
		this.element = $(el);
		this.setOptions(options);

		var selector = this.element.getElement('select.editor-selector');

		if (selector)
		{
			selector.addEvent
			(
				'change', function(ev)
				{
					this.change(ev.target.get('value'));
				}
				.bind(this)
			);
		}

		this.form = this.element.getParent('form');
	},

	change: function(editor)
	{
		//console.info('change editor to: %s', type);

		this.element.set('tween', { property: 'opacity', duration: 'short', link: 'cancel' });
		this.element.get('tween').start(.5);

		var op = new WdOperation
		(
			'editor', 'getEditor',
			{
				onSuccess: function(response)
				{
					//console.info('response: %a', response);

					this.element.get('tween').start(0).chain
					(
						function()
						{
							this.handleResponse(response);
						}
						.bind(this)
					);
				}
				.bind(this)
			}
		);

		var key = this.form['#key'];
		var constructor = this.form['#destination'].value;
		var textarea = this.element.getElement('textarea');

		//console.info('bind: %a, %s', bind, this.baseName + '[bind]');

		op.post
		({
			contentsName: this.options.contentsName,
			selectorName: this.options.selectorName,

			editor: editor,
			contents: textarea ? textarea.value : '',

			nid: key ? key.value : null,
			constructor: constructor
		});
	},

	handleResponse: function(response)
	{
		var el = Elements.from(response.rc).shift();

		el.set('tween', { property: 'opacity', duration: 'short', link: 'cancel' });
		el.set('opacity', 0);

		el.inject(this.element, 'after');

		this.element.destroy();

		wd_update_assets
		(
			response.assets, function()
			{
				this.initialize(el);

				el.get('tween').start(1);
			}
			.bind(this)
		);
	}
});

window.addEvent
(
	'domready', function()
	{
		$(document.body).getElements('div.editor-wrapper').each
		(
			function(el)
			{
				var options = el.getElement('input.wd-multieditor-options');

				if (options)
				{
					options = JSON.decode(options.value);
				}

				new WdContentsEditor(el, options);
			}
		);
	}
);