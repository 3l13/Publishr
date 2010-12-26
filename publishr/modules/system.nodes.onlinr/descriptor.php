<?php

return array
(
	WdModule::T_TITLE => 'Onlinr',
	WdModule::T_DESCRIPTION => 'Manage the online state of your nodes',
	WdModule::T_PERMISSION => WdModule::PERMISSION_NONE,
	WdModule::T_STARTUP => 0,

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_CONNECTION => 'local',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'nid' => array('foreign', 'primary' => true),
					'publicize' => array('date', 'indexed' => true),
					'privatize' => array('date', 'indexed' => true)
				)
			)
		)
	)
);