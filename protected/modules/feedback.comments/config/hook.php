<?php

return array
(
	'patron.markups' => array
	(
		array
		(
			'feedback:comments' => array
			(
				array('feedback_comments_WdMarkups', 'comments'), array
				(
					'node' => array('mandatory' => true),
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
					'node' => array('mandatory' => true)
				)
			)
		)
	)
);