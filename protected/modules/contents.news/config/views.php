<?php

return array
(
	'/home' => array
	(
		'title' => 'Accueil des actualités',
		'provider' => true,
		'assets' => array
		(
			'css' => 'public/base.css'
		)
	),

	'/list' => array
	(
		'title' => 'Liste des actualités',
		'provider' => true,
		'assets' => array
		(
			'css' => 'public/base.css'
		)
	),

	'/view' => array
	(
		'title' => "Détail d'une actualité",
		'provider' => true,
		'assets' => array
		(
			'css' => array
			(
				'../resources.images/public/slimbox.css',
				'public/base.css'
			),

			'js' => '../resources.images/public/slimbox.js'
		)
	),

	'/category' => array
	(
		'title' => "Liste des actualités pour une catégorie"
	)
);