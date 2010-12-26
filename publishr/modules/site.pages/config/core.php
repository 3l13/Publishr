<?php

return array
(
	'autoload' => array
	(
		'WdPageSelectorElement' => $root . 'elements/pageselector.php',
		'WdAdjustTemplateElement' => $root . 'elements/adjusttemplate.php',
		'view_WdEditorElement' => $root . 'elements/view.editor.php',

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