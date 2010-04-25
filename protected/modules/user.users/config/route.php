<?php

return array
(
	array
	(
		'manage' => array
		(

		),

		'create' => array
		(

		),

		'edit' => array
		(

		),

		'config' => array
		(

		),

		'/profile' => array
		(
			'title' => 'Profil',
			'block' => 'profile',
			'visibility' => 'auto'
		),

		'/authenticate' => array
		(
			'title' => 'Connection',
			'block' => 'connect',
			'workspace' => '',
			'visibility' => 'auto'
		),

		'/users' => array
		(
			'location' => '/user.users'
		)
	),

	'defaults' => array
	(
		'module' => 'user.users',
		'workspace' => 'users'
	)
);