<?php

return array
(
	WdModule::T_TITLE => 'Nodes',
	WdModule::T_DESCRIPTION => 'Centralized node system base',
	WdModule::T_PERMISSION => PERMISSION_NONE,
	WdModule::T_MANDATORY => true,

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
					'slug' => array('varchar', 'indexed' => true),
					'constructor' => array('varchar', 64, 'indexed' => true),
					'created' => array('timestamp', 'default' => 'current_timestamp()'),
					'modified' => 'timestamp',
					'is_online' => array('boolean', 'indexed' => true),

					#
					# i18n
					#

					'language' => array('varchar', 8),
					'tnid' => 'foreign',
					'is_translation_deprecated' => 'boolean'
				)
			)
		),

		'locks' => array
		(
			WdModel::T_CONNECTION => 'local',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'nid' => 'primary',
					'uid' => 'foreign',
					'until' => 'datetime'
				)
			)
		),

		'metas' => array
		(
			WdModel::T_CONNECTION => 'local',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'nid' => 'foreign',
					'name' => array('varchar', 'indexed' => true),
					'value' => 'text'
				),

				'primary-key' => array('nid', 'name')
			)
		)
	)
);