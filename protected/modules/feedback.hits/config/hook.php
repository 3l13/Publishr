<?php

return array
(
	'patron.markups' => array
	(
		array
		(
			'hit' => array
			(
				array('feedback_hits_WdMarkups', 'hit'), array
				(
					'select' => array('mandatory' => true)
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
	)
);