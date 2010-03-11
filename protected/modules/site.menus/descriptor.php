<?php

return array
(
	WdModule::T_TITLE => 'Menus',
	WdModule::T_DESCRIPTION => 'Organise pages into custom menus',
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'system.nodes',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'description' => 'text'
				)
			)
		),

		'pages' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'menuid' => 'foreign',
					'pageid' => 'foreign',
					'weight' => array('integer', 'unsigned' => true)
				)
			)
		)
	)
);