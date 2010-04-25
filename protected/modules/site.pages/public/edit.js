/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

var WdContentsEditor = new Class
({
	initialize: function(el)
	{
		this.element = $(el);
		this.textarea = this.element.getElement('textarea[name^="contents["]');
		this.form = this.textarea.form;
		this.contentsid = this.textarea.name.match(/\[([^\[]+)\]/)[1];
		
		//console.info('editor: %a, textarea: %a, %a', el, this.textarea, this.contentsid);
		
		this.element.getElement('select.editor-selector').addEvent
		(
			'change', function(ev)
			{
				ev.stop();
				
				this.change(ev.target.value);
			}
			.bind(this)
		);
	},
	
	change: function(editor)
	{
		//console.info('change editor to: %s', type);
		
		this.element.set('tween', { property: 'opacity', duration: 'short', link: 'cancel' });
		this.element.get('tween').start(.5);
		
		var op = new WdOperation
		(
			'site.pages', 'getContentsEditor',
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
		
		op.post
		({
			pageid: key ? key.value : null,
			contentsid: this.contentsid,
			editor: editor,
			contents: this.textarea.value
		});
	},
	
	handleResponse: function(response)
	{
		var el = new Element('div', { html: response.rc.editor });
		var el = el.getFirst();
		
		el.set('tween', { property: 'opacity', duration: 'short', link: 'cancel' });
		el.set('opacity', 0);
		
		el.inject(this.element, 'after');
		
		this.element.destroy();

	
		
		var base = window.location.protocol + '//' + window.location.hostname;
		
		//console.info('base: %s', base);

		//
		// initialize css
		//
		
		var css = []
				
		if (response.rc.css)
		{
			css = response.rc.css;
			
			$(document.head).getElements('link[type="text/css"]').each
			(
				function(el)
				{
					var href = el.href.substring(base.length);
					
					if (css.indexOf(href) != -1)
					{
						//console.info('css already exists: %s', href);
						
						css.erase(href);
					}
				}
			);
		}
		
		//console.info('css final: %a', css);
		
		css.each
		(
			function(href)
			{
				new Asset.css(href);
			}
		);
		
		//
		// initialize javascript
		//
		
		var js = [];
		
		if (response.rc.javascript)
		{
			js = response.rc.javascript;
			
			$(document.head).getElements('script').each
			(
				function(el)
				{
					var src = el.src.substring(base.length);
					
					if (js.indexOf(src) != -1)
					{
						//console.info('script alredy exixts: %s', src);
						
						js.erase(src);
					}
				}
			);
		}
		
		//console.info('js: %a', js);
		
		if (js.length)
		{
			var js_count = js.length;
			
			js.each
			(
				function(src)
				{
					new Asset.javascript
					(
						src,
						{
							onload: function()
							{
								//console.info('loaded: %a', src);
								
								js_count--;
								
								if (!js_count)
								{
									//console.info('no js remaingn, initialize editor');
									
									if (response.rc.initialize)
									{
										eval(response.rc.initialize);
									}
								}
							}
						}
					);
				}
			);
		}
		else
		{
			if (response.rc.initialize)
			{
				eval(response.rc.initialize);
			}
		}
		
		//
		//
		//
		
		this.initialize(el);
		
		el.get('tween').start(1);
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
				new WdContentsEditor(el);
			}
		);
	}
);