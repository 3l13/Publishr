<?php

return array
(
	WdModule::T_TITLE => 'Forms',
	WdModule::T_DESCRIPTION => 'Create forms based on models',
	WdModule::T_CATEGORY => 'feedback',
	WdModule::T_EXTENDS => 'system.nodes',
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'system.nodes',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'modelid' => array('varchar', 32),

					'before' => 'text',
					'after' => 'text',
					'complete' => 'text',

					'is_notify' => 'boolean',
					'notify_destination' => 'varchar',
					'notify_from' => 'varchar',
					'notify_bcc' => 'varchar',
					'notify_subject' => 'varchar',
					'notify_template' => 'text',

					'pageid' => 'foreign'
				)
			)
		)
	)
);