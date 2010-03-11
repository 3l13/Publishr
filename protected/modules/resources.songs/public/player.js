var WdPlayer= new Class
({
	Implements: [ Events ],

	initialize: function(el)
	{
		this.element = $(el);
		this.element.set('slide', { wrapper: this.element.getParent(), hiveOverflow: false });

		this.title_el = this.element.getElement('.title strong');
		this.artist_el = this.element.getElement('.title .artist');
		this.position_el = this.element.getElement('.views .position');
		this.duration_el = this.element.getElement('.views .duration');
		this.progress_el = this.element.getElement('div.progress');

		this.progress_el.addEvent
		(
			'click', function(ev)
			{
				var coords = this.progress_el.getCoordinates();
				var percent = 1 - ((coords.right - ev.client.x) / coords.width);

				if (this.current)
				{
					this.current.setPosition(this.current.duration * percent);
				}
			}
			.bind(this)
		);

		this.progress_load_el = this.progress_el.getElement('.load');
		this.progress_play_el = this.progress_el.getElement('.play');
	},

	play: function(nid)
	{
		var op = new WdOperation
		(
			'resources.songs', 'load',
			{
				onComplete: function(response)
				{
					this.update(response.rc);
				}
				.bind(this)
			}
		);

		op.post({ '#key': nid });
	},

	stop: function()
	{
		if (!this.current)
		{
			return;
		}

		soundManager.destroySound(this.current.sID);

		this.current = null;
	},

	reset: function()
	{
		this.stop();

		this.title_el.set('html', '');
		this.artist_el.set('html', '');

		this.progress_load_el.setStyle('width', 0);
		this.progress_play_el.setStyle('width', 0);

		this.position_el.set('html', '-:--');
		this.duration_el.set('html', '-:--');
	},

	update: function(entry)
	{
		//console.log('slide: %a', this.element.get('slide'));

		this.element.get('slide').slideOut().chain
		(
			function()
			{
				this.reset();

				this.title_el.set('html', entry.title);
				this.artist_el.set('html', entry.artist || 'unknown');

				this.current = soundManager.createSound
				(
					{
						id: entry.nid,
						url: entry.path,

						whileloading: function()
						{
							var current = this.current;

							this.progress_load_el.setStyle('width', current.bytesLoaded / current.bytesTotal * 100 + '%');
						}
						.bind(this),

						whileplaying: function()
						{
							var current = this.current;
							var position = current.position;
							var duration = current.duration;

							this.progress_play_el.setStyle('width', position / duration * 100 + '%');

							position = this.formatTime(position);
							duration = this.formatTime(duration);

							this.position_el.set('html', position);
							this.duration_el.set('html', duration);
						}
						.bind(this),

						onfinish: function()
						{
							this.fireEvent('finishedSong');
						}
						.bind(this)
					}
				);

				this.current.play();

				this.element.get('slide').slideIn();
			}
			.bind(this)
		);
	},

	formatTime: function(time)
	{
		var minutes = (time / 60 / 1000).floor();
		var seconds = ((time / 1000) - minutes * 60).floor();

		if (seconds < 10)
		{
			seconds = '0' + seconds;
		}

		return minutes + ':' + seconds;
	}
});

var player = null;

window.addEvent
(
	'load', function()
	{
		window.soundManager = new SoundManager();

		soundManager.url = soundManagerURL;
		soundManager.debugMode = (window.location.href.match(/debug=1/i)); // disable
		soundManager.consoleOnly = true;
		soundManager.flashVersion = 9;

		soundManager.beginDelayedInit();

		//console.log('soundManager: %a', soundManager);
	}
);

manager.addEvent
(
	'ready', function()
	{
		player = new WdPlayer('player');

		player.formatTime(161697);

		var songs = $$('table.manage a.song');

		songs.each
		(
			function(el)
			{
				el.addEvent
				(
					'click', function(ev)
					{
						ev.stop();

						var uri = new URI(this.href);
						var nid = uri.get('fragment');

						if (this.hasClass('playing'))
						{
							this.removeClass('playing');

							player.element.slide('out');

							player.stop();
						}
						else
						{
							songs.removeClass('playing');

							this.addClass('playing');

							player.play(nid);
						}
					}
				);
			}
		);

		player.addEvent
		(
			'finishedSong', function()
			{
				player.element.slide('out');

				songs.removeClass('playing');
			}
		);
	}
);