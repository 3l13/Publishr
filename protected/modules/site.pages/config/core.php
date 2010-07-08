<?php

$_includes_root = $root . 'includes' . DIRECTORY_SEPARATOR;

return array
(
	'autoload' => array
	(
		'WdPageSelectorElement' => $_includes_root . 'wdpageselectorelement.php',
		'WdAdjustTemplateElement' => $_includes_root . 'wdadjusttemplateelement.php'
	),

	'classes aliases' => array
	(
		'Page' => 'site_pages_WdActiveRecord'
	)
);