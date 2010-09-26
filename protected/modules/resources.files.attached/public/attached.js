window.addEvent
(
	'load', function()
	{
		$$('div.resources-files-attached').each
		(
			function(el)
			{
				var trigger = el.getElement('button');
				var progress = el.getElement('li.progress');
				var options = JSON.decode(el.getElement('input.element-options').value);

				el.getElements('a.remove').addEvent
				(
					'click', function(ev)
					{
						ev.stop();

						this.getParent('li').destroy();
					}
				);

				var list = el.getElement('ol');

				/*
				list.addEvents
				({
					mousedown: function(ev)
					{
						var tag = ev.target.get('tag');

						if (tag == 'a' || tag == 'input')
						{
							return;
						}

						console.log('mousedown, target: %a', ev.target);

						list.setStyle('list-style-type', 'circle');
					},

					mouseup: function(ev)
					{
						list.setStyle('list-style-type', '');
					}
				})
				*/

				var sortable = new Sortables
				(
					list,
					{
						clone: true,
						constrain: true,
						opacity: 0.2,
						handle: 'span.handle',

						onStart: function(el, clone)
						{
							//console.log('arguments: ', arguments);

							clone.setStyle('z-index', 10000);
							//list.setStyle('list-style', 'none');
						}
					}
				);


				//console.log('el: %a, button: %a', el, trigger);

				var setProgressTimer=null;
				var fadeTween = progress.get('tween', { property: 'opacity', duration: 'long' });

				function fadeOutProgress()
				{
					fadeTween.start(0).chain
					(
						function()
						{
							progress.setStyle('display', '');
						}
					);
				}

				function setProgress(html, type)
				{
					if (!html)
					{
						html = progressIdle;
					}

					progress.set('opacity', 1);
					progress.setStyle('display', 'block');
					
					progress.removeClass('done');
					progress.removeClass('error');

					if (type == 'done')
					{
						progress.addClass('done');
					}
					else if (type == 'error')
					{
						progress.addClass('error');
					}

					progress.set('html', html);

					if (setProgressTimer)
					{
						$clear(setProgressTimer);
					}

					setProgressTimer = fadeOutProgress.delay(type == 'done' ? 500 : 2000);
				};

				var uploader = new Swiff.Uploader
				(
					$merge
					(
						{
							queued: false,
							multiple: false,
							instantStart: true,
							appendCookieData: true,
							url: '/do/resources.files.attached/upload',

							/*
							onSelectSuccess: function(sucess)
							{
								console.log('onSelectSuccess: ', sucess);

								//trigger.setStyle('visibility', 'hidden');
								progress.set('opacity', 1);
								progress.setStyle('display', '');
							},
							*/

							onSelectFail: function(ev)
							{
								//console.log('onSelectFail: %a, %s, %s', fail, fail.name, fail.validationError);

								// TODO-20100624: support for the 'validationError' == 'duplicate'

								setProgress('Erreur de transfert: ' + ev.validationError, 'error');
							},

							onQueue: function()
							{
								//console.log('onQueue: ', arguments);

								if (!uploader.uploading)
								{
									return;
								}

								var size = Swiff.Uploader.formatUnit(uploader.size, 'b');

								setProgress(uploader.percentLoaded + '% / ' + size);
							},

							onFileComplete: function(ev)
							{
								try
								{
									var response = JSON.decode(ev.response.text);
								}
								catch ($e)
								{
									setProgress($e, 'error');

									return;
								}

								//console.log('onFileComplete: %a, response (flash): %a, response: %a', arguments, self.response, response);

								//trigger.setStyle('visibility', '');
								//progress.setStyle('display', 'none');

								if (ev.response.code != 0)
								{
									setProgress(response.log.errors.join(), 'error');
								}

								if (!response.rc)
								{
									return;
								}

								setProgress('Transfert réussi', 'done');

								var item = Elements.from(response.rc).shift();
								var file = ev.base.fileList[ev.base.fileList.length-1];

								item.inject(progress, 'before');
								item.highlight('#FFC');

								//
								// dirty fix because 'highlight' doesn't clean the 'background-color' property when done
								//

								( function() { item.setStyle('background-color', ''); } ).delay(1000);

								item.getElement('a[href$=remove').addEvent
								(
									'click', function(ev)
									{
										var parent = this.getParent('li');

										ev.stop();

										uploader.fileRemove(file);

										parent.destroy();
									}
								);

								sortable.addItems(item);

								uploader.reposition();
							}
						},

						options
					)
				);

				trigger.addEvents
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

				uploader.wdAttach(trigger);
			}
		);
	}
);