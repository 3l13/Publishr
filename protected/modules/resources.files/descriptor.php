<?php

return array
(
	WdModule::T_TITLE => 'Fichiers',
	WdModule::T_DESCRIPTION => 'Module de base pour le gestion de fichiers',
	WdModule::T_CATEGORY => 'resources',
	WdModule::T_EXTENDS => 'system.nodes',
	WdModule::T_REQUIRED => true,

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'system.nodes',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'path' => 'varchar',
					'mime' => 'varchar',
					'size' => array('integer', 'unsigned' => true),
					'description' => 'text'
				)
			)
		)
	)
);