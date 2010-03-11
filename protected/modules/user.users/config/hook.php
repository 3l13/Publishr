<?php

return array
(
	'patron.markups' => array
	(
		array
		(
			'connect' => array
			(
				array('user_users_WdMarkups', 'connect')
			),

			'user' => array
			(
				array('user_users_WdMarkups', 'user'), array
				(
					'select' => array('mandatory' => true)
				)
			)
		)
	)
);