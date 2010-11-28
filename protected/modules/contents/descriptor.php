<?php

return array
(
	WdModule::T_TITLE => 'Contents',
	WdModule::T_DESCRIPTION => 'Code de base pour gérer les contenus éditoriaux',
	WdModule::T_CATEGORY => 'contents',

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'system.nodes',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'subtitle' => array('varchar', 200),
					'body' => 'text',
					'excerpt' => 'text',
					'date'=> 'datetime',
					'editor' => array('varchar', 32),
					'is_home_excluded' => array('boolean', 'indexed' => true)
				)
			)
		)
	)
);