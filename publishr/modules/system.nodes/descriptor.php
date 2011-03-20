<?php

return array
(
	WdModule::T_TITLE => 'Nodes',
	WdModule::T_DESCRIPTION => 'Centralized node system base',
	WdModule::T_PERMISSION => false,
	WdModule::T_REQUIRED => true,

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'nid' => 'serial',
					'uid' => 'foreign',
					'siteid' => 'foreign',
					'title' => 'varchar',
					'slug' => array('varchar', 80, 'indexed' => true),
					'constructor' => array('varchar', 64, 'indexed' => true),
					'created' => array('timestamp', 'default' => 'current_timestamp()'),
					'modified' => 'timestamp',
					'is_online' => array('boolean', 'indexed' => true),

					#
					# i18n
					#

					'language' => array('varchar', 8),
					'tnid' => 'foreign'
				)
			)
		)
	),

	WdModule::T_PERMISSIONS => array
	(
		'modify belonging site'
	)
);