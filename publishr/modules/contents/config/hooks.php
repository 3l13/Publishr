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
		'operation_activate_for_contents_body' => array
		(
			array('contents_WdHooks', 'operation_activate_for_contents_body'),

			'instanceof' => 'system_cache_WdModule'
		),

		'operation_deactivate_for_contents_body' => array
		(
			array('contents_WdHooks', 'operation_deactivate_for_contents_body'),

			'instanceof' => 'system_cache_WdModule'
		),

		'operation_usage_for_contents_body' => array
		(
			array('contents_WdHooks', 'operation_usage_for_contents_body'),

			'instanceof' => 'system_cache_WdModule'
		),

		'operation_clear_for_contents_body' => array
		(
			array('contents_WdHooks', 'operation_clear_for_contents_body'),

			'instanceof' => 'system_cache_WdModule'
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