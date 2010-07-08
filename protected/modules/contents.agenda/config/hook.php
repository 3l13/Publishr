<?php

return array
(
	'patron.markups' => array
	(
		'agenda:home' => array
		(
			array('contents_agenda_WdMarkups', 'home'), array
			(

			)
		),

		'agenda:dates' => array
		(
			array('contents_agenda_WdMarkups', 'dates'), array
			(
				'select' => array('expression' => true),
				'limit' => 10,
				'page' => 0
			)
		),

		'agenda:date' => array
		(
			'o:contents_agenda_view_WdMarkup', array
			(
				'select' => array('expression' => true, 'mandatory' => true)
			)
		)
	)
);