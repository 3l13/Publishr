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
		$$('a[rel^=nonver]').each
		(
			function(el)
			{
				var object = new Element
				(
					'object',
					{
						'width': 120,
						'height': 100,
						'type': 'application/x-shockwave-flash',
						'data': '/public/NonverBlaster.swf',
						'style': 'width: 100%; height: 100%'
					}
				);

				var poster = el.getElement('img');

				var fv = Object.merge
				(
					{
						loop: false,
						autoPlay: false,
						defaultVolume: 100,
						buffer: 5,
						crop: false,
						controlsEnabled: true,
						controlColor: 'FFFFFF',
						mediaURL: el.get('href'),
						teaserURL: poster ? poster.get('src') : null,
						title: el.get('title')
					},

					fv
				);

				var fv_string = '';

				Object.each
				(
					fv, function(value, key)
					{
						if (value === null || value === undefined)
						{
							return;
						}

						fv_string += '&' + key + '=' + encodeURIComponent(value)
					}
				);

				fv_string = fv_string.substring(1);

				var params =
				{
					wmode: 'opaque',
					allowscriptaccess: 'always',
					allownetworking: 'all',
					allowfullscreen: 'true',
					flashvars: fv_string
				};

				for (key in params)
				{
					object.appendChild(new Element('param', { 'name': key, 'value': params[key] }));
				}

				object.replaces(el);
			}
		);
	}
);