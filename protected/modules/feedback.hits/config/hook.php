<?php

return array
(
	'patron.markups' => array
	(
		'feedback:hit' => array
		(
			array('feedback_hits_WdMarkups', 'hit'), array
			(
				'select' => array('expression' => true, 'mandatory' => true)
			)
		),

		'feedback:hits' => array
		(
			array('feedback_hits_WdMarkups', 'hits'), array
			(
				'scope' => array('mandatory' => true),
				'limit' => null
			)
		)
	)
);