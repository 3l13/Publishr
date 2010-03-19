<?php

return array
(
	WdModule::T_TITLE => 'Pages',
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'system.nodes',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'parentid' => 'foreign',
					'locationid' => 'foreign',
					'label' => array('varchar', 80),
					'pattern' => 'varchar',
					'weight' => array('integer', 'unsigned' => true),
					'layout' => array('varchar', 32),
					'is_navigation_excluded' => array('boolean', 'indexed' => true)
				)
			)
		),

		'contents' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'pageid' => 'foreign',
					'contentsid' => array('varchar', 64),
					'contents' => 'text',
					'editor' => array('varchar', 32),
					'type' => array('varchar', 32)
				),

				'primary-key' => array('pageid', 'contentsid')
			)
		)
	)
);