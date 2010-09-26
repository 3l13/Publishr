<?php

return array
(
	'objects.methods' => array
	(
		'__get_comments' => array
		(
			array('feedback_comments_WdHooks', 'get_comments'),

			'instancesof' => 'system_nodes_WdActiveRecord'
		),

		'__get_comments_count' => array
		(
			array('feedback_comments_WdHooks', 'get_comments_count'),

			'instancesof' => 'system_nodes_WdActiveRecord'
		),
	),

	'patron.markups' => array
	(
		'feedback:comments' => array
		(
			array('feedback_comments_WdMarkups', 'comments'), array
			(
				'node' => null,
				'order' => 'asc',
				'by' => 'created',
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
				'select' => array('expression' => true, 'default' => 'this', 'mandatory' => true)
			)
		)
	)
);