<?php

return array
(
	'objects.methods' => array
	(
		'__get_user' => array
		(
			array('m:user.users', 'hook_get_user'),

			'instanceof' => 'WdCore'
		),

		'__get_user_id' => array
		(
			array('user_users_WdModule', 'hook_get_user_id'),

			'instanceof' => 'WdCore'
		)
	),

	'patron.markups' => array
	(
		'connect' => array
		(
			array('user_users_WdMarkups', 'connect')
		),

		'user' => array
		(
			array('user_users_WdMarkups', 'user'), array
			(
				'select' => array('required' => true)
			)
		)
	)
);