<?php

return array
(
	WdModule::T_TITLE => 'Registry',
	WdModule::T_DESCRIPTION => 'Holds configuration settings for the system as well as nodes, users and sites.',
	WdModule::T_PERMISSION => false,
	WdModule::T_REQUIRED => true,

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_CONNECTION => 'local',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'name' => array('varchar', 'primary' => true),
					'value' => 'text'
				)
			)
		),

		'node' => array
		(
			WdModel::T_CONNECTION => 'local',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'targetid' => 'foreign',
					'name' => array('varchar', 'indexed' => true),
					'value' => 'text'
				),

				'primary-key' => array('targetid', 'name')
			)
		),

		'user' => array
		(
			WdModel::T_CONNECTION => 'local',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'targetid' => 'foreign',
					'name' => array('varchar', 'indexed' => true),
					'value' => 'text'
				),

				'primary-key' => array('targetid', 'name')
			)
		),

		'site' => array
		(
			WdModel::T_CONNECTION => 'local',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'targetid' => 'foreign',
					'name' => array('varchar', 'indexed' => true),
					'value' => 'text'
				),

				'primary-key' => array('targetid', 'name')
			)
		)
	)
);