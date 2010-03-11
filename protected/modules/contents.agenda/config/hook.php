<?php

return array
(
	'patron.markups' => array
	(
		array
		(
			'agenda:dates' => array
			(
				array('contents_agenda_WdMarkups', 'dates'), array
				(
					'select' => array('mandatory' => true, 'evaluate' => true),
					'limit' => 10,
					'page' => 0
				)
			),

			'agenda:date' => array
			(
				array('contents_agenda_WdMarkups', 'date'), array
				(
					'select' => array('mandatory' => true, 'evaluate' => true)
				)
			)
		)
	)
);