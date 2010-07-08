<?php

return array
(
	'patron.markups' => array
	(
		'slideshows:home' => array
		(
			array('organize_slideshows_WdMarkups', 'home')
		),

		'slideshows' => array
		(
			'o:organize_slideshows_list_WdMarkup', array
			(
				'select' => array('evaluate' => true),
				'page' => 0,
				'limit' => null
			)
		)
	)
);