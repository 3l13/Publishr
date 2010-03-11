<?php

return array
(
	WdModule::T_TITLE => 'Forms',
	WdModule::T_DESCRIPTION => 'Create forms',
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'system.nodes',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'modelid' => 'foreign',
					'serializedconfig' => 'text',

					'before' => 'text',
					'after' => 'text',
					'complete' => 'text',

					'pageid' => 'foreign'
				)
			)
		)
	)
);