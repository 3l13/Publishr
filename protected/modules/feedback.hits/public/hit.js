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

			url: '/api/feedback.hits/' + feedback_hits_nid + '/hit'

		});

		op.send();
	}
);