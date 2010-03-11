/*
---

script: MooEditable.Image.js

description: Extends MooEditable to insert image with manipulation options.

license: MIT-style license

authors:
- Radovan Lozej

requires:
# - MooEditable
# - MooEditable.UI
# - MooEditable.Actions

provides: [MooEditable.UI.ImageDialog, MooEditable.Actions.image]

usage: |
	Add the following tags in your html
	<link rel="stylesheet" href="MooEditable.css">
	<link rel="stylesheet" href="MooEditable.Image.css">
	<script src="mootools.js"></script>
	<script src="MooEditable.js"></script>
	<script src="MooEditable.Image.js"></script>

	<script>
	window.addEvent('domready', function(){
		var mooeditable = $('textarea-1').mooEditable({
			actions: 'bold italic underline strikethrough | image | toggleview'
		});
	});
	</script>

...
*/

MooEditable.UI.ImageDialog = new Class
({
	Extends: MooEditable.UI.Dialog,
	
	initialize: function(editor)
	{
		this.editor = editor;
		this.unique = Math.random();
		
		this.dummy_el = new Element
		(
			'div',
			{
				styles:
				{
					'display': 'none'
				}
			}
		);
	},
	
	toElement: function()
	{
		return this.dummy_el;
	},
	
	click: function()
	{
		this.fireEvent('click', arguments);
		
		return this;
	},
	
	close: function()
	{
		if (this.adjust)
		{
			this.adjust.close();
		}
		
		this.fireEvent('close', this);
		
		return this;
	},
	
	open: function()
	{
		//
		// get the node to edit, if none, a new one is created with a default image
		//
		
		this.node = this.editor.selection.getNode();
		
		if (!this.node || this.node.get('tag') != 'img')
		{
			this.node = new Element('img', { 'src': '/public/medias/404.png' });
			
			this.editor.selection.getRange().insertNode(this.node);
		}
		
		//
		// We create the adjust element if it's not created yet
		//
		
		if (!this.adjust)
		{
			WdAdjustImage.fetchElement
			(
				this.node.get('src'),
				{
					onLoad: function(adjust)
					{
						this.open_callback(adjust);
					}
					.bind(this),
					
					options:
					{
						iframe: this.editor.iframe
					}
				}
			);
			
			return;
		}
		
		this.open_callback();
	},
	
	open_callback: function(adjust)
	{
		if (adjust)
		{
			adjust.addEvent
			(
				'closeRequest', this.close.bind(this)
			);
			
			this.adjust = adjust;
		}
		
		this.adjust.attachTarget(this.node);
		
		this.adjust.open();
	}
});

MooEditable.Actions.extend
({	
	image:
	{
		title: 'Add/Edit Image',
		
		options:
		{
			shortcut: 'm'
		},
		
		dialogs:
		{
			prompt: function(editor)
			{
				return new MooEditable.UI.ImageDialog(editor);
			}
		},
		
		command: function()
		{
			this.dialogs.image.prompt.open();
		}
	}
});