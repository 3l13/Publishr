var pl = null;

var search =
{
	page: function(n)
	{
		var form = $(document.body).getElement('form.edit');
		var destination = form['#destination'].value;

		var op = new WdOperation
		(
			destination, 'search',
			{
				onComplete: function(response)
				{
					if (!response.rc)
					{
						return;
					}

					var el = Elements.from(response.rc);

					el[0].replaces($('song-results'));
				}
			}
		);

		var search = form.getElement('#song-search input.search');

		search = search.hasClass('empty') ? '' : search.value;

		op.get({ page: n, search: search });
	}
};

var WdPlaylist = new Class
({
	initialize: function(el)
	{
		this.element = $(el);
		this.list = this.element.getElement('ul.song');
		this.sortable = new Sortables
		(
			this.list,
			{
				clone: true,
				constrain: true,
				//revert: { duration: 500, transition: 'elastic:out' },
				opacity: 0.2,

				onStart: function(el, clone)
				{
					clone.setStyle('z-index', 10000);
				}
			}
		);

		var form = $(document.body).getElement('form.edit');

		this.destination = form.elements['#destination'].value;
	},

	add: function(nid)
	{
		var op = new WdOperation
		(
			this.destination, 'add',
			{
				onComplete: function(response)
				{
					if (!response.rc)
					{
						return;
					}

					var els = Elements.from(response.rc);

					els.inject(this.list);

					this.sortable.addItems(els);
				}
				.bind(this)
			}
		);

		op.get({ song: nid });
	},

	remove: function(el)
	{
		var el = el.getParent('li');

		this.sortable.removeItems(el).destroy();
	}
});

window.addEvent
(
	'domready', function()
	{
		pl = new WdPlaylist('playlist');

		var search = $('song-search').getElement('input.search');

		//
		// prevent form submition
		//

		search.addEvent
		(
			'keypress', function(ev)
			{
				if (ev.key == 'enter')
				{
					ev.stop();
				}
			}
		);

		//
		// search as you type
		//

		search.addEvent
		(
			'keyup', function(ev)
			{
				if (ev.key == 'esc')
				{
					this.value = '';
				}

				var form = this.form;
				var destination = form['#destination'].value;

				var op = new WdOperation
				(
					destination, 'search',
					{
						onComplete: function(response)
						{
							if (!response.rc)
							{
								return;
							}

							var el = Elements.from(response.rc);

							el[0].replaces($('song-results'));
						}
					}
				);

				op.get({ page: 0, search: this.value });
			}
		);
	}
);