<?php

return array
(
	WdModule::T_TITLE => 'Vocabulary',
	WdModule::T_DESCRIPTION => 'Manage vocabulary',
	WdModule::T_CATEGORY => 'organize',

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'vid' => 'serial',
					'siteid' => 'foreign',
					'vocabulary' => 'varchar',
					'vocabularyslug' => array('varchar', 80, 'indexed' => true),
					'is_tags' => 'boolean',
					'is_multiple' => 'boolean',
					'is_required' => 'boolean',

					/**
					 * Specify the weight of the element used to edit this vosabulary
					 * in the altered edit block of the constructor.
					 */

					'weight' => array('integer', 'unsigned' => true)
				)
			)
		),

		'scopes' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'vid' => 'foreign',
					'constructor' => array('varchar', 64)
				),

				'primary-key' => array('vid', 'constructor')
			)
		)
	)
);