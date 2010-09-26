<?php

return array
(
	array
	(
		'manage' => array
		(

		),

		'/{self}/{block}' => array
		(
			'title' => 'Galerie',
			'block' => 'gallery',
			'workspace' => 'resources'
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

		'/resources' => array
		(
			'location' => '/resources.images'
		)
	),

	'defaults' => array
	(
		'module' => 'resources.images'
	)
);