<?php

return array
(
	'/list' => array
	(
		'title' => 'Diaporamas : liste',
		'description' => 'Affiche la liste des diaporamas',
		'file' => $root . 'views' . DIRECTORY_SEPARATOR . 'list.html'
	),

	'/view' => array
	(
		'title' => 'Diaporamas : entrée',
		'description' => "Affiche le détail d'un diaporama",
		'file' => $root . 'views' . DIRECTORY_SEPARATOR . 'view.html'
	)
);