<?php

$_includes_root = $root . 'includes' . DIRECTORY_SEPARATOR;

return array
(
	'autoload' => array
	(
		'WdPageSelectorElement' => $_includes_root . 'wdpageselectorelement.php',
		'WdAdjustTemplateElement' => $_includes_root . 'wdadjusttemplateelement.php',

		'site_pages_WdMarkups' => $root . 'markups.php',
		'site_pages_languages_WdMarkup' => $root . 'markups.php',
		'site_pages_navigation_WdMarkup' => $root . 'markups.php',
		'site_pages_sitemap_WdMarkup' => $root . 'markups.php'
	),

	'classes aliases' => array
	(
		'Page' => 'site_pages_WdActiveRecord'
	)
);