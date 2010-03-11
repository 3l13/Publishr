manager.addEvent
(
	'ready', function()
	{
		manager.element.getElements('a.play').each
		(
			function(el)
			{
				el.addEvent
				(
					'click', function()
					{
						this.toggleClass('playing');

						if (!this.hasClass('playing'))
						{
							return;
						}

						player.
					}
				);
			}
		);
	}
);