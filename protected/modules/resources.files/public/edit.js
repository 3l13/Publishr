/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

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

		//
		//
		//

		var form = $(document.body).getElement('form.edit');
		var destination = form.elements['#destination'].get('value'); 
		
		$$('div.file-upload-element').each
		(
			function(el)
			{
				var options = JSON.decode(el.getElement('var.options').get('html'));
				
				options.destination = destination;
				
				uploader = new WdFileUploadElement(el, options);
				
				uploader.addEvent
				(
					'change', function(ev)
					{
						//
						// update fields values
						//

						$each
						(
							ev.rc.fields, function(value, key)
							{
								var input = form.elements[key];
								
								if (!input || !input.hasClass('was-empty'))
								{
									return;
								}

								input.value = value;
							}
						);
						
						//
						// slimbox
						//
						
						if (typeof Slimbox != 'undefined')
						{
							Slimbox.scanPage();
						}
					}
				);
			}
		);
	}
);