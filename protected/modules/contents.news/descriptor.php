<?php

return array
(
	WdModule::T_TITLE => 'Actualités',
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'system.nodes',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'contents' => 'text',
					'excerpt' => 'text',
					'date' => 'date',
					'imageid' => 'foreign'
				)
			)
		)
	)
);