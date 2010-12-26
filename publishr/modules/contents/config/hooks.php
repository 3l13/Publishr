<?php

return array
(
	'patron.markups' => array
	(
		'contents' => array
		(
			'o:contents_view_WdMarkup', array
			(
				'constructor' => 'contents',
				'select' => array('expression' => true, 'required' => true)
			)
		),

		'contents:home' => array
		(
			'o:contents_home_WdMarkup', array
			(
				'constructor' => 'contents'
			)
		),

		'contents:list' => array
		(
			'o:contents_list_WdMarkup', array
			(
				'constructor' => 'contents',
				'select' => array('expression' => true)
			)
		)
	)
);