<?php

return array
(
	WdModule::T_TITLE => 'Articles',
	WdModule::T_DESCRIPTION => 'Articles management',

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'system.nodes',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'contents' => 'text',
					'excerpt' => 'text',
					'date'=> 'datetime',
					'editor' => array('varchar', 32)
				)
			)
		)
	)
);