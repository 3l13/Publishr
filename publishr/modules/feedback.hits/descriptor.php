<?php

return array
(
	WdModule::T_TITLE => 'Hits',
	WdModule::T_DESCRIPTION => 'Counter for your resources',
	WdModule::T_CATEGORY => 'feedback',
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'nid' => 'primary',
					'hits' => array('integer', 'unsigned' => true, 'default' => 1),
					'first' => array('timestamp', 'default' => 'current_timestamp()'),
					'last' => array('timestamp', 'default' => 0)
				)
			)
		)
	)
);