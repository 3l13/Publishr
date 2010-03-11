<?php

return array
(
	array
	(
		'manage' => array
		(

		),

		'/{self}/<[^/]+>/install' => array
		(
			'title' => 'Install',
			'block' => 'install',
			'visibility' => 'auto'
		)
	),

	'defaults' => array
	(
		'module' => 'system.modules'
	)
);