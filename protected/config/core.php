<?php

#
# watchout for variable collisions
#

$_includes = dirname($root) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
$_protected_root = $root;
$_protected_includes = $_protected_root . 'includes' . DIRECTORY_SEPARATOR;

return array
(
	'autoconfig' => array
	(
		'route' => 'WdRoute'
	),

	'autoload' => array
	(
		'WdPElement' => $_includes . 'wdpelement.php',
		'WdPForm' => $_includes . 'wdpform.php',
		'WdPModule' => $_includes . 'wdpmodule.php',
		'WdPublisher' => $_includes . 'wdpublisher.php',
		'WdRoute' => $_includes . 'wdroute.php',
		'WdSectionedForm' => $_includes . 'wdsectionedform.php',

		'WdDocument' => $_protected_includes . 'wddocument.php',
		'WdEMailNotifyElement' => $_protected_includes . 'wdemailnotifyelement.php',
		'WdManager' => $_protected_includes . 'wdmanager.php',
		'WdPDocument' => $_protected_includes . 'wddocument.php',
		'WdResume' => $_protected_includes . 'resume.php',
	),

	'connections' => array
	(
		'local' => array
		(
			'dsn' => 'sqlite:' . $_SERVER['DOCUMENT_ROOT'] . '/repository/$db/local.sq3'
		)
	),

	'modules' => array
	(
		$root . 'modules'
	)
);