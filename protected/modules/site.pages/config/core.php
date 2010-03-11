<?php

$_includes_root = $root . 'includes' . DIRECTORY_SEPARATOR;

return array
(
	'autoload' => array
	(
		'site_pages_WdEvents' => $root . 'events.php',
		'site_pages_WdManager' => $root . 'manager.php',
		'site_pages_WdMarkups' => $root . 'markups.php',

		'WdPageSelectorElement' => $_includes_root . 'wdpageselectorelement.php'
	),

	'classes aliases' => array
	(
		'Page' => 'site_pages_WdActiveRecord'
	)
);