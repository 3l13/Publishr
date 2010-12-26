window.addEvent
(
	'domready', function()
	{
		var container = $('dashboard-panels');

		var sortables = new Sortables
		(
			'#dashboard-panels > div',
			{
				clone: true,
				handle: 'div.panel-title',
				opacity: .1,
				revert: false,

				onStart: function(el, clone)
				{
					clone.id = el.id;

					container.addClass('sorting');
				},

				onComplete: function(ev)
				{
					container.removeClass('sorting');

					container.getElements('div.panel-holder').each
					(
						function(el)
						{
							var parent = el.getParent();

							el.dispose();

							el.inject(parent);
						}
					);

					var orderByColumns = [];

					container.getChildren().each
					(
						function (column, columnIndex)
						{
							column.getChildren().each
							(
								function (panel)
								{
									var id = panel.id;

									if (!id)
									{
										return;
									}

									if (!orderByColumns[columnIndex])
									{
										orderByColumns[columnIndex] = [];
									}

									orderByColumns[columnIndex].push(panel.id);
								}
							);
						}
					);

					var req = new Request
					({
						url: '/api/components/dashboard/order'
					});

					req.post({ order: orderByColumns });
				}
			}
		);
	}
);