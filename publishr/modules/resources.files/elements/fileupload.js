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
	Implements: [ Options, Events, Dataset ],

	options:
	{
		path: 'Swiff.Uploader.swf',
		constructor: 'resources.files', // TODO-20101227: rename as 'constructor'
		maxFileSize: 2 * 1024 * 1024
	},

	initialize: function(el, options)
	{
		this.element = el = $(el);
		this.element.store('uploader', this);

		this.setOptions(options);
		this.setOptions(this.getDataset(el));

		this.trigger = el.getElement('input[type=file]');
		this.trigger.addEvent('change', this.onChange.bind(this));
	},

	onChange: function(ev)
	{
		var files = ev.target.files;

		if (!files.length)
		{
			return;
		}

		this.readAndUpload(files[0]);
	},

	readAndUpload: function(file)
	{
		this.reader = new FileReader();
		var self = this;

		//console.log('starting loading file: %s', file.name);

		this.reader.onloadend = function(ev)
		{
			//console.log('%a- loading complete for file "%s", uploading now', ev, file.name);

			this.onProgress(ev);

			this.positionTween.set(0);
			this.element.removeClass('reading');

			this.upload(file, ev.target.result);
		}
		.bind(this);

		this.reader.onprogress = this.onProgress.bind(this);

		if (!this.positionTween)
		{
			this.positionElement = this.element.getElement('.progress .position');
			this.positionLabelElement = this.positionElement.getElement('.label');
			this.positionTween = new Fx.Tween(this.positionElement, { property: 'width', link: 'cancel', unit: '%', duration: 'short' });
			this.cancelElement = this.element.getElement('button.cancel');

			this.cancelElement.addEvent('click', this.cancel.bind(this));
		}

		this.positionTween.set(0);
		this.element.addClass('uploading');
		this.element.addClass('reading');

		this.reader.readAsBinaryString(file);
	},

	upload: function(file, data)
	{
		var xhr = this.xhr = new XMLHttpRequest();
		var	fileUpload = xhr.upload;
		var self = this;

		xhr.onreadystatechange = function(ev)
		{
			if (this.readyState == 4 && this.status == 200)
			{
				response = JSON.parse(this.responseText);

				//console.log('%a- transfer complete with the following response: %a', ev, response);

				var reminder = self.element.getElement('.reminder');

				reminder.setAttribute('value', response.file.location);

				var el = self.element;

				if (response.log.error.length)
				{
					el.getElement('div.error').innerHTML = response.log.error.join('<br />');
					el.addClass('has-error');
					el.getElement('input.reminder').removeAttribute('value');
				}
				else
				{
					el.removeClass('has-error');
				}

				if (response.infos)
				{
					el.getElement('div.infos').innerHTML = response.infos;
					el.addClass('has-info');
				}
				else
				{
					el.getElement('div.infos').innerHTML = '';
					el.removeClass('has-info');
				}

				self.finish(response);
			}
			else
			{
				//console.log('%a- readyState: %d, status: %d', ev, this.readyState, this.status);
			}
		};

		fileUpload.onprogress = this.onProgress.bind(this);
		fileUpload.onload = this.onProgress.bind(this);

		fileUpload.onerror = function(ev)
		{
			//console.log('%a- transfert error !', ev);
		};

		var inputName = 'Filedata';
		var boundary = '--------------------------------';

		for (var i = 0 ; i < 32 ; i++)
		{
			boundary += Math.round(Math.random() * 9);
		}

		var body = "--" + boundary + "\r\n";

		body += 'Content-Disposition: form-data; name="' + inputName + '"; filename=' + encodeURIComponent(file.name) + '\r\n';
		body += 'Content-Type: ' + (file.type ? file.type : 'application/octet-stream') + '\r\n\r\n';

		body += data + '\r\n';
		body += '--' + boundary + '--';

		xhr.open("POST", '/api/' + this.options.constructor + '/upload');

		xhr.setRequestHeader('Accept', 'applocation/json');
		xhr.setRequestHeader('Content-Type', 'multipart/form-data, boundary=' + boundary); // simulate a file MIME POST request.
		xhr.setRequestHeader('Content-Length', body.length);
		xhr.setRequestHeader('X-Using-File-API', true);

		xhr.sendAsBinary(body);
	},

	cancel: function()
	{
		if (this.reader)
		{
			this.reader.abort();
			delete this.reader;
			this.reader = null;
		}

		if (this.xhr)
		{
			this.xhr.abort();
			delete this.xhr;
			this.xhr = null;
		}

		this.finish();
	},

	finish: function(response)
	{
		this.element.removeClass('uploading');

		if (response)
		{
			this.fireEvent('change', response);
		}
	},

	onProgress: function(ev)
	{
		if (!ev.lengthComputable)
		{
			return;
		}

		var position = 100 * ev.loaded / ev.total;

		this.positionTween.set(position);
		this.positionLabelElement.innerHTML = Math.round(position) + '%';
	},

	onSuccess: function(ev)
	{
		this.finish();
	}

});

window.addEvent
(
	'domready', function()
	{
		$$('.file-upload-element').each
		(
			function(el)
			{
				if (el.retrieve('uploader'))
				{
					return;
				}

				new WdFileUploadElement(el);
			}
		);
	}
);

/*
var WdFileUploadElement = new Class
({

	Implements: [ Options, Events, Dataset ],

	options:
	{
		path: 'Swiff.Uploader.swf',
		constructor: null,
		verbose: false,
		maxFileSize: 2 * 1024 * 1024
	},

	initialize: function(el, options)
	{
		this.setOptions(options);
		this.setOptions(this.getDataset(el));

		el = $(el);

		this.uploader = new Swiff.Uploader
		(
			Object.merge
			(
				{
					queued: false,
					multiple: false,
					instantStart: true,
					appendCookieData: true,
					fileSizeMax: this.options.maxFileSize,

					url: '/api/' + this.options.constructor + '/upload',

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
		//this.trigger = this.element.getElement('input[type=file]');
		this.reminder = this.element.getElement('.file-reminder');
		this.progress = this.element.getElement('.file-size-limit');
		this.progressIdle = this.progress ? this.progress.get('html') : '';

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
		if (!this.progress)
		{
			return;
		}

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

		//console.info('constructor: %s', constructor);

		var op = new WdOperation
		(
			this.options.constructor, 'uploadResponse',
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
*/