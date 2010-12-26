<?php

return array
(
	'events' => array
	(
		'operation.save:before' => array
		(
			array('feedback_comments_WdHooks', 'before_operation_save'),

			'instanceof' => 'feedback_forms_WdModule'
		),

		'operation.delete' => array
		(
			array('feedback_comments_WdHooks', 'operation_delete'),

			'instanceof' => 'system_nodes_WdModule'
		),

		'alter.block.edit' => array
		(
			array('feedback_comments_WdHooks', 'alter_block_edit'),

			'instanceof' => 'feedback_forms_WdModule'
		)
	),

	'objects.methods' => array
	(
		'__get_comments' => array
		(
			array('feedback_comments_WdHooks', 'get_comments'),

			'instanceof' => 'system_nodes_WdActiveRecord'
		),

		'__get_comments_count' => array
		(
			array('feedback_comments_WdHooks', 'get_comments_count'),

			'instanceof' => 'system_nodes_WdActiveRecord'
		),
	),

	'patron.markups' => array
	(
		'feedback:comments' => array
		(
			array('feedback_comments_WdMarkups', 'comments'), array
			(
				'node' => null,
				'order' => 'created asc',
				'limit' => 0,
				'page' => 0,
				'noauthor' => false,
				'parseempty' => false
			)
		),

		'feedback:comments:form' => array
		(
			array('feedback_comments_WdMarkups', 'form'), array
			(
				'select' => array('expression' => true, 'default' => 'this', 'required' => true)
			)
		)
	)
);