<?php

return array
(
	array
	(
		'manage' => array
		(
			'title' => 'Actifs'
		),

		'/{self}/<[^/]+>/install' => array
		(
			'title' => 'Install',
			'block' => 'install',
			'visibility' => 'auto',
			'workspace' => 'system'
		),

		'/{self}/inactives' => array
		(
			'title' => 'Inactifs',
			'block' => 'inactives',
			'workspace' => 'system'
		)
	)
);