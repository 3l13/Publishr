<?php

return array
(
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