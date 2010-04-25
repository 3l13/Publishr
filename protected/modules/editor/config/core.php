<?php

$_includes_root = $root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;

return array
(
	'autoconfig' => array
	(
		'views' => 'view_WdEditorElement',
		'widgets' => 'widgets_WdEditorElement'
	),

	'autoload' => array
	(
		'WdEditorElement' => $_includes_root . 'wdeditorelement.php',
		'WdMultiEditorElement' => $_includes_root . 'wdmultieditorelement.php',

		'moo_WdEditorElement' => $_includes_root . 'moo_wdeditorelement.php',
		'patron_WdEditorElement' => $_includes_root . 'patron_wdeditorelement.php',
		'raw_WdEditorElement' => $_includes_root . 'raw_wdeditorelement.php',
		'textmark_WdEditorElement' => $_includes_root . 'textmark_wdeditorelement.php',
		'php_WdEditorElement' => $_includes_root . 'php_wdeditorelement.php',
		'view_WdEditorElement' => $_includes_root . 'view_wdeditorelement.php',
		'widgets_WdEditorElement' => $_includes_root . 'widgets_wdeditorelement.php',

		'nodeadjust_WdEditorElement' => $_includes_root . 'nodeadjust_wdeditorelement.php',
	)
);