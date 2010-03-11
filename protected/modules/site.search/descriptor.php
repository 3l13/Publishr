<?php

return array
(
	WdModule::T_TITLE => 'Search',
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'sid' => 'serial',
					'search' => 'varchar',
					'timestamp' => array('timestamp', 'default' => 'current_timestamp()')
				)
			)
		)
	)
);