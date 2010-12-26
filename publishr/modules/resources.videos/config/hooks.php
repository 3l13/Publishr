<?php

return array
(
	'patron.markups' => array
	(
		'videos:home' => array
		(
			array('resources_videos_WdMarkups', 'home'), array
			(
				'limit' => null
			)
		),

		'videos' => array
		(
			array('resources_videos_WdMarkups', 'videos'), array
			(
				'select' => array('expression' => true),
				'limit' => 10,
				'page' => null
			)
		)
	)
);