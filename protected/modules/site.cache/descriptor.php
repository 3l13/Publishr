<?php

return array
(
	WdModule::T_TITLE => 'Cache',
	WdModule::T_DESCRIPTION => 'Enable pages caching',
	WdModule::T_PERMISSION => PERMISSION_NONE,

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_CONNECTION => 'local',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'id' => array('char', 40),
					'uid' => 'foreign',
					'contents' => 'blob',
					'created' => array('timestamp', 'default' => '(current_timestamp)')
				),

				'primary-key' => array('id', 'uid')
			)
		)
	)
);