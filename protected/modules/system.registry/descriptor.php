<?php

return array
(
	WdModule::T_TITLE => 'Registry',
	WdModule::T_DESCRIPTION => '',
	WdModule::T_PERMISSION => PERMISSION_NONE,

	WdModule::T_STARTUP => 250,
	WdModule::T_MANDATORY => true,

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
		)
	)
);