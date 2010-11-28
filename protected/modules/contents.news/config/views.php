<?php

return array
(
	'/home' => array
	(
		'title' => 'Accueil des actualités',
		'assets' => array
		(
			'css' => 'public/base.css'
		)
	),

	'/list' => array
	(
		'title' => 'Liste des actualités',
		'assets' => array
		(
			'css' => 'public/base.css'
		)
	),

	'/view' => array
	(
		'title' => "Détail d'une actualité",
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