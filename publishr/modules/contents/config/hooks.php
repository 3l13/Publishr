<?php

return array
(
	'events' => array
	(
		'alter.block.manage' => array
		(
			array('contents_WdHooks', 'alter_block_manage'),

			'instanceof' => 'system_cache_WdModule'
		)
	),

	'objects.methods' => array
	(
		'enable_contents_body' => array
		(
			array('contents_WdHooks', 'enable_cache'),

			'instanceof' => 'system_cache__enable_WdOperation'
		),

		'disable_contents_body' => array
		(
			array('contents_WdHooks', 'disable_cache'),

			'instanceof' => 'system_cache__disable_WdOperation'
		),

		'stat_contents_body' => array
		(
			array('contents_WdHooks', 'stat_cache'),

			'instanceof' => 'system_cache__stat_WdOperation'
		),

		'clear_contents_body' => array
		(
			array('contents_WdHooks', 'clear_cache'),

			'instanceof' => 'system_cache__clear_WdOperation'
		)
	),

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