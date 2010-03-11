//
// we do this on 'load' so that the page doesn't _flash_
//

window.addEvent
(
	'load', function()
	{
		var form = $(document.body).getElement('form.edit');

		form.getElements('input[type=text]').each
		(
			function (el)
			{
				if (!el.value)
				{
					el.addClass('was-empty');
				}
			}
		);

		/*
		var title_el = form.getElement('input[name=title]');
		var title_initial = title_el.get('value');
		*/

		var createUploader = function(el)
		{
			el = $(el);

			var link = el.getElement('button');
			var reminder = el.getElement('input.file-reminder');

			var progress = el.getElement('small.file-size-limit');
			var progressIdle = progress.get('html');

			var destination = link.form['#destination'].value;
			var url = '/?do=' + destination + '.upload';

			var options =
			{
				path: 'Swiff.Uploader.swf',
				url: url,
				verbose: false,
				queued: false,
				multiple: false,
				target: link,
				instantStart: true,

				/*
				typeFilter:
				{
					'Images (*.jpg, *.jpeg, *.gif, *.png)': '*.jpg; *.jpeg; *.gif; *.png'
				},
				*/

				fileSizeMax: 2 * 1024 * 1024,

				onSelectSuccess: function(files)
				{
					if (Browser.Platform.linux)
					{
						window.alert('Warning: Due to a misbehaviour of Adobe Flash Player on Linux,\nthe browser will probably freeze during the upload process.\nSince you are prepared now, the upload will start right away ...');
					}

					//log.alert('Starting Upload', 'Uploading <em>' + files[0].name + '</em> (' + Swiff.Uploader.formatUnit(files[0].size, 'b') + ')');

					this.setEnabled(false);
				},

				onSelectFail: function(files)
				{
					alert('"' + files[0].name + '" was not added!', 'Please select an image smaller than 2 Mb. (Error: #' + files[0].validationError + ')');
				},

				appendCookieData: true,

				onQueue: function()
				{
					if (!this.uploading)
					{
						return;
					}

					var size = Swiff.Uploader.formatUnit(this.size, 'b');

					progress.set('html', this.percentLoaded + '% of ' + size);
				},

				onFileComplete: function(file)
				{
					file.remove();

					this.setEnabled(true);
					
					//console.log('response: %a', file.response);

					if (!file.response.text)
					{
						alert('Unable to upload file, response is null');

						return;
					}

					try
					{
						var response = JSON.decode(file.response.text);
					}
					catch ($e)
					{
						alert('Response was truncated: ' + file.response.text);

						return;
					}

					var uploadId = response.rc;

					//console.info('destination: %s', destination);

					var op = new WdOperation
					(
						destination, 'uploadResponse',
						{
							onComplete: function(response)
							{
								var element = Elements.from(response.rc.element)[0];

								element.replaces(el);

								el = element;

								link = el.getElement('button');

								link.addEvents
								({
									click: function() {
										return false;
									},
									mouseenter: function() {
										this.addClass('hover');
										swf.reposition();
									},
									mouseleave: function() {
										this.removeClass('hover');
										this.blur();
									},
									mousedown: function() {
										this.focus();
									}
								});

								reminder = el.getElement('input.file-reminder');

								progress = el.getElement('small.file-size-limit');

								//
								// update fields values
								//

								var fields = response.rc.fields;

								for (key in fields)
								{
									var input = form.elements[key];

									if (!input/* || !input.hasClass('was-empty')*/)
									{
										continue;
									}

									input.value = fields[key];
								}

								//
								// finish
								//

								swf.wdAttach(el.getElement('button'));

								if (typeof Slimbox != 'undefined')
								{
									Slimbox.scanPage();
								}
							}
						}
					);

					op.post({ uploadId: uploadId });
				},

				onComplete: function()
				{
					progress.set('html', progressIdle);
				}
			};

			$extend(options, JSON.decode(el.getElement('var.options').get('html')));

			//console.info('options: %a', options);

			// Uploader instance

			var swf = new Swiff.Uploader(options);

			//console.info('swf: %a', swf);

			// Button state

			link.addEvents
			({
				click: function() {
					return false;
				},
				mouseenter: function() {
					this.addClass('hover');
					swf.reposition();
				},
				mouseleave: function() {
					this.removeClass('hover');
					this.blur();
				},
				mousedown: function() {
					this.focus();
				}
			});
		};

		/*
		$$('form.edit div.file-upload-element').each
		(
			function(el)
			{
				console.info('el: %a', el);

				createUploader(el);
			}
		)
		*/

		createUploader($(document.body).getElement('form.edit div.file-upload-element'));
	}
);