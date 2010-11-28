<?php

return array
(
	'patron.markups' => array
	(
		'agenda:home' => array
		(
//			array('contents_agenda_WdMarkups', 'home'), array
			'o:contents_agenda_home_WdMarkup', array
			(
				'constructor' => 'contents.agenda'
			)
		),

		'agenda:dates' => array
		(
			'o:contents_agenda_list_WdMarkup', array
			(
				'select' => array('expression' => true),
				'limit' => 10,
				'page' => 0
			)
		),

		'agenda:date' => array
		(
			'o:contents_view_WdMarkup', array
			(
				'select' => array('expression' => true, 'required' => true),
				'constructor' => 'contents.agenda'
			)
		)
	)
);