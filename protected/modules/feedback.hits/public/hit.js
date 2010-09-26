window.addEvent
(
	'load', function()
	{
		if (!feedback_hits_nid)
		{
			return;
		}

		var op = new Request.JSON
		({

			url: '/do/feedback.hits/' + feedback_hits_nid + '/hit'

		});

		op.get();
	}
);