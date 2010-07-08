<?php

return array
(
	WdModule::T_TITLE => 'Fichiers attachés',
	WdModule::T_DESCRIPTION => "Permet d'attacher des fichiers à des entrées",
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'nodeid' => 'foreign',
					'fileid' => 'foreign',
					'title' => 'varchar',
					'weight' => array('integer', 'tiny', 'unsigned' => true)
				),

				'primary-key' => array('nodeid', 'fileid')
			)
		)
	)
);