<?php

return array
(
	WdModule::T_TITLE => 'Playlists',
	WdModule::T_DESCRIPTION => 'Organise your mp3 into playlists',
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'system.nodes',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'description' => 'text',
					'date' => 'date'
				)
			)
		),

		'songs' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'plid' => 'foreign',
					'songid' => 'foreign',
					'weight' => array('integer', 'unsigned' => true)
				)
			)
		)
	)
);