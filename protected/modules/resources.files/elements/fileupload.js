/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

var WdFileUploadElement = new Class
({

	Implements: [ Options, Events ],

	options:
	{
		path: 'Swiff.Uploader.swf',
		destination: null,
		verbose: false,
		fileSizeMax: 2 * 1024 * 1024
	},

	initialize: function(el, options)
	{
		this.setOptions(options);

		this.uploader = new Swiff.Uploader
		(
			$merge
			(
				{
					queued: false,
					multiple: false,
					instantStart: true,
					appendCookieData: true,

					url: '/do/' + this.options.destination + '/upload',

					onSelectSuccess: this.onSelectSuccess.bind(this),
					onSelectFail: this.onSelectFail.bind(this),
					onQueue: this.onLoaderQueue.bind(this),
					onFileComplete: this.onLoaderFileComplete.bind(this)
				},

				this.options
			)
		);

		this.attach(el);
	},

	attach: function(el)
	{
		this.element = $(el);

		this.trigger = this.element.getElement('button');
		this.reminder = this.element.getElement('.file-reminder');
		this.progress = this.element.getElement('.file-size-limit');
		this.progressIdle = this.progress.get('html');

		//
		//
		//

		var uploader = this.uploader;

		this.trigger.addEvents
		({
			click: function()
			{
				return false;
			},

			mouseenter: function()
			{
				this.addClass('hover');

				uploader.reposition();
			},

			mouseleave: function()
			{
				this.removeClass('hover');
				this.blur();
			},

			mousedown: function()
			{
				this.focus();
			}
		});

		uploader.wdAttach(this.trigger);
	},

	setProgress: function(html, error)
	{
		if (!html)
		{
			html = this.progressIdle;
		}

		this.progress[(error ? 'add' : 'remove') + 'Class']('error');
		this.progress.set('html', html);
	},

	onSelectSuccess: function(uploader)
	{
		this.setProgress();
		this.uploader.setEnabled(false);
	},

	onSelectFail: function(files)
	{
		alert('"' + files[0].name + '" was not added!', 'Please select an image smaller than 2 Mb. (Error: #' + files[0].validationError + ')');
	},

	onLoaderQueue: function(uploader)
	{
		if (!uploader.uploading)
		{
			return;
		}

		var size = Swiff.Uploader.formatUnit(uploader.size, 'b');

		this.setProgress(uploader.percentLoaded + '% of ' + size);
	},

	onLoaderFileComplete: function(file)
	{
		file.remove();

		this.uploader.setEnabled(true);

		if (!file.response.text)
		{
			this.setProgress('An error occured, the response is null', true);

			return;
		}

		try
		{
			var response = JSON.decode(file.response.text);
		}
		catch ($e)
		{
			alert('Response was truncated by Flash: ' + file.response.text);

			return;
		}

		//console.log('response: %a', response);

		if (response.log.error.length)
		{
			this.setProgress(response.log.error.join('<br />'), true);

			return;
		}

		if (!response.rc)
		{
			return;
		}

		var uploadId = response.rc;

		//console.info('destination: %s', destination);

		var op = new WdOperation
		(
			this.options.destination, 'uploadResponse',
			{
				onComplete: function(response)
				{
					var element = Elements.from(response.rc.element).shift();

					element.replaces(this.element);

					this.attach(element);

					this.fireEvent('change', { target: this.element, rc: response.rc });
				}
				.bind(this)
			}
		);

		op.get({ uploadId: uploadId, name: this.options.name });
	}
});