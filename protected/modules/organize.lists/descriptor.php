<?php

return array
(
	WdModule::T_TITLE => 'Lists',
	WdModule::T_DESCRIPTION => 'Organise nodes in lists',
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'system.nodes',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'scope' => array('varchar', 64),
					'description' => 'text'
				)
			)
		),

		'nodes' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'listid' => 'foreign',
					'nodeid' => 'foreign',
					'parentid' => 'foreign',
					'weight' => array('integer', 'unsigned' => true),
					'label' => array('varchar', 80)
				)
			)
		)
	)
);