<?php

return array
(
	'autoload' => array
	(
		'contents_articles_WdEvents' => $root . 'events.php',
		'contents_articles_WdManager' => $root . 'manager.php',
		'contents_articles_WdMarkups' => $root . 'markups.php'
	),

	'classes aliases' => array
	(
		'Article' => 'contents_articles_WdActiveRecord'
	)
);