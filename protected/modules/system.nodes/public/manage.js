manager.addEvent
(
	'ready', function()
	{
		manager.element.getElements('td.is_online input[type="checkbox"]').each
		(
			function(el)
			{
				el.addEvent
				(
					'click', function(ev)
					{
						var destination = this.form['#destination'].value;

						var operation = new WdOperation
						(
							destination, this.checked ? 'online' : 'offline',
							{
								onRequest: function()
								{
									this.disabled = true;
								},

								onSuccess: function(response)
								{
									this.disabled = false;

									if (!response.rc)
									{
										//
										// if for some reason the operation failed,
										// we reset the checkbox
										//

										this.checked = !this.checked;

										this.fireEvent('change', {});
									}
								}
								.bind(this)
							}
						);

						operation.post({ '#key': this.value });
					}
				);
			}
		);
	}
);