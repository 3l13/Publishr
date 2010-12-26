<?php

return array
(
	WdModule::T_TITLE => 'Chansons',
	WdModule::T_DESCRIPTION => '@resources.songs.description',
	WdModule::T_CATEGORY => 'resources',

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'resources.files',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'artist' => 'varchar',
					'album' => 'varchar',
					'year' => array('integer', 4, 'unsigned' => true),
					'track' => array('integer', 3, 'unsigned' => true),
					'duration' => 'integer',
					'bitrate' => 'integer'
				)
			)
		)
	)
);