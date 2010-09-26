<?php

return array
(
	WdModule::T_TITLE => 'Terms',
	WdModule::T_DESCRIPTION => 'Manage vocabulary terms',
	WdModule::T_CATEGORY => 'organize',
//	WdModule::T_MANDATORY => true,

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_IMPLEMENTS => array
			(
				array('model' => 'taxonomy.vocabulary/primary')
			),

			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'vtid' => 'serial',
					'vid' => 'foreign',
					'term' => 'varchar',
					'termslug' => 'varchar',
					'weight' => array('integer', 'unsigned' => true)
				)
			)
		),

		'nodes' => array
		(
			WdModel::T_IMPLEMENTS => array
			(
				array('model' => 'taxonomy.terms/primary')
			),

			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'vtid' => 'foreign',
					'nid' => 'foreign'
				),

				'primary-key' => array('vtid', 'nid')
			)
		)
	)
);