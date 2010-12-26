<?php

return array
(
	WdModule::T_TITLE => 'Registry',
	WdModule::T_DESCRIPTION => '',
	WdModule::T_PERMISSION => WdModule::PERMISSION_NONE,

	WdModule::T_STARTUP => 250,
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