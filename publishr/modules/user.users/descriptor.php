<?php

return array
(
	WdModule::T_TITLE => 'Users',
	WdModule::T_DESCRIPTION => 'User management',
	WdModule::T_CATEGORY => 'users',
	WdModule::T_REQUIRED => true,

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'uid' => 'serial',
					'rid' => array('varchar', 32),

					'email' => array('varchar', 64, 'unique' => true),
					'password' => array('char', 40),

					'username' => array('varchar', 32, 'unique' => true),
					'firstname' => array('varchar', 32),
					'lastname' => array('varchar', 32),
					'display' => array('integer', 1),

					'created' => array('timestamp', 'default' => 'current_timestamp()'),
					'lastconnection' => 'datetime',

					'constructor' => array('varchar', 64, 'indexed' => true),
					'is_activated' => array('boolean', 'indexed' => true),

					'language' => array('varchar', 8),
					'timezone' => array('varchar', 32)
				)
			)
		)
	),

	WdModule::T_PERMISSIONS => array
	(
		'modify own profile'
	)
);