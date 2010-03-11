<?php

return array
(
	WdModule::T_TITLE => 'Attachments',
	WdModule::T_DESCRIPTION => 'Allows nodes to be attached to other nodes',

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_CONNECTION => 'local',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'attachmentid' => 'serial',
					'id' => array('varchar', 32, 'indexed' => true),
					'title' => array('varchar', 64),
					'description' => 'text',
					'scope' => 'varchar',
					'target' => array('varchar', 'indexed' => true),
					'is_mandatory' => 'boolean'
				)
			)
		),

		'nodes' => array
		(
			WdModel::T_CONNECTION => 'local',
			WdModel::T_IMPLEMENTS => array
			(
				array
				(
					'model' => 'system.nodes.attachments/primary'
				)
			),

			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'attachmentid' => 'foreign',
					'nid' => 'foreign',
					'targetid' => 'foreign'
				),

				'primary-key' => array('attachmentid', 'nid')
			)
		)
	)
);

/*

The Attachments module allows nodes to be attached to other nodes e.g. image nodes or page nodes
can be attached to an article node. The attachment has an ID and sould be retrievable from the node
active record. $node->attachments->ID.

Similar to vocabularies, attachments are defined per module, and can be mandatory too.

Per module, one can choose which kind of nodes can be attached

*/