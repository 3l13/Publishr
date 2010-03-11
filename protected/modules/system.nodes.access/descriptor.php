<?php

return array
(
	WdModule::T_TITLE => 'Accès',
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'accessid' => 'serial',
					'title' => 'varchar',
					'loginpageid' => 'foreign',
					'userstypes' => 'text',
					'usersroles' => 'text'
				)
			)
		),

		#
		# nodes with a restricted access
		#

		'nodes' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'accessid' => 'foreign',
					'nid' => 'foreign'
				),

				'primary-key' => array('accessid', 'nid')
			)
		)
	)
);