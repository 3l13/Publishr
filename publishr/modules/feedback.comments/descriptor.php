<?php

return array
(
	WdModule::T_TITLE => 'Comments',
	WdModule::T_DESCRIPTION => 'Implements comments for nodes',
	WdModule::T_CATEGORY => 'feedback',
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'commentid' => 'serial',
					'nid' => 'foreign',
					'parentid' => 'foreign', // for nested comments
					'uid' => 'foreign',
					'author' => array('varchar', 32),
					'author_email' => array('varchar', 64),
					'author_url' => 'varchar',
					'author_ip' => array('varchar', 45),
					'contents' => 'text',
					'status' => array('enum', array('pending', 'approved', 'spam'), 'indexed' => true),
					'notify' => array('enum', array('no', 'yes', 'author', 'done'), 'indexed' => true),
					'created' => array('timestamp', 'default' => 'current_timestamp()'),
				)
			)
		)
	)
);