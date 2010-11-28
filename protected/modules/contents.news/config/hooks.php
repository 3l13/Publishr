<?php

return array
(
	'patron.markups' => array
	(
		'news:home' => array
		(
			'o:contents_home_WdMarkup', array
			(
				'constructor' => 'contents.news'
			)
		),

		'news:list' => array
		(
			'o:contents_list_WdMarkup', array
			(
				'constructor' => 'contents.news',
				'select' => array('expression' => true)
			)
		),

		'news' => array
		(
			'o:contents_view_WdMarkup', array
			(
				'constructor' => 'contents.news',
				'select' => array('expression' => true, 'required' => true)
			)
		)
	)
);