<?php

$includes = $root . '/includes/';

return array
(
	'autoload' => array
	(
		'WdPModule' => $includes . 'wdpmodule.php',
		'WdPublisher' => $includes . 'wdpublisher.php',
		'WdRoute' => $includes . 'wdroute.php',
		'WdSectionedForm' => $includes . 'wdsectionedform.php',

		'WdEMailNotifyElement' => $includes . 'wdemailnotifyelement.php',
		'WdManager' => $includes . 'wdmanager.php',
		'WdPDashboard' => $includes . 'wdpdashboard.php',
		'WdPDocument' => $includes . 'wdpdocument.php',
		'WdResume' => $includes . 'resume.php',

		'WdKses' => $includes . 'external/kses/kses.php',

		'publisher_WdHooks' => $includes . 'hooks.php'
	),

	'connections' => array
	(
		'local' => array
		(
			'dsn' => 'sqlite:' . $_SERVER['DOCUMENT_ROOT'] . '/repository/db/local.sq3'
		)
	),

	'modules' => array
	(
		$root . 'modules'
	)
);