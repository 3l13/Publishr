<?php

return array
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

	'/admin/profile' => array
	(
		'title' => 'Profil',
		'block' => 'profile',
		'visibility' => 'auto',
		'module' => 'user.users',
		'workspace' => ''
	),

	'/admin/authenticate' => array
	(
		'title' => 'Connection',
		'block' => 'connect',
		'workspace' => '',
		'visibility' => 'auto',
		'module' => 'user.users'
	),

	'/admin/users' => array
	(
		'location' => '/admin/user.users'
	)
);