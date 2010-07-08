<?php

return array
(
	'patron.markups' => array
	(
		'news:home' => array
		(
			'o:contents_news_home_WdMarkup', array
			(

			)
		),

		'news:list' => array
		(
			'o:contents_news_list_WdMarkup', array
			(
				'select' => array('expression' => true),
				'limit' => null,
				'page' => 0
			)
		),

		'news' => array
		(
			'o:contents_news_view_WdMarkup', array
			(
				'select' => array('expression' => true, 'mandatory' => true)
			)
		)
	)
);