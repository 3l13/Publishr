window.addEvent
(
	'domready', function()
	{
		var dates = $$('input.date');

		if (dates.length)
		{
			/*
			dates.each
			(
				function(el)
				{
					el.value = el.value.substring(0, 10);
				}
			);

			new DatePicker
			(
				dates,
				{
					format: '%d %b %Y',
					allowEmpty: true,
					positionOffset: { x: 0, y: 10 },
					debug: false
				}
			);
			*/

			dates.each
			(
				function(el)
				{
					new CalendarEightysix
					(
						el,
						{
							offsetY: 10,
							createHiddenInput: true,
							format: '%d %b %Y',
							hiddenInputFormat: '%Y-%m-%d'
						}
					);
				}
			);
		}

		/*
		new DatePicker
		(
			$$('input.datetime'),
			{
				format: '%d %b %Y à %H:%M',
				//inputOutputFormat: 'Y-m-d H-i-s',
				allowEmpty: true,
				positionOffset: { x: 0, y: 10 },
				timePicker: true,
				debug: false
			}
		);

		new DatePicker
		(
			$$('input.time'),
			{
				format: 'H:i',
				//inputOutputFormat: 'H-i-s',
				allowEmpty: true,
				positionOffset: { x: 0, y: 10 },
				timePicker: true,
				timePickerOnly: true,
				debug: false
			}
		);
		*/
	}
);