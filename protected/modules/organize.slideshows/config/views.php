<?php

return array
(
	'/list' => array
	(
		'title' => "Liste des diaporama",
//		'description' => 'Affiche la liste des diaporamas',
		'file' => $root . 'views' . DIRECTORY_SEPARATOR . 'list.html'
	),

	'/view' => array
	(
		'title' => "Détail d'un diaporama",
//		'description' => "Affiche le détail d'un diaporama",
		'file' => $root . 'views' . DIRECTORY_SEPARATOR . 'view.html'
	)
);