<?php

return array
(
	WdModule::T_TITLE => 'Vocabulary',
	WdModule::T_DESCRIPTION => 'Manage vocabulary',
//	WdModule::T_MANDATORY => true,

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'vid' => 'serial',
					'vocabulary' => 'varchar',
					'vocabularyslug' => 'varchar',
					'weight' => array('integer', 'tiny', 'indexed' => true),
					'is_tags' => 'boolean',
					'is_multiple' => 'boolean'
				)
			)
		),

		'scope' => array
		(
			WdModel::T_NAME => 'taxonomy_vocabulary_scope',

			WdModel::T_IMPLEMENTS => array
			(
				array('model' => 'taxonomy.vocabulary/primary')
			),

			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'vid' => 'foreign',
					'scope' => 'varchar',
					'is_mandatory' => 'boolean'
				),

				'primary-key' => array('vid', 'scope')
			)
		)
	)
);