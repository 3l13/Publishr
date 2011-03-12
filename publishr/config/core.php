<?php

$includes = $path . '/includes/';

return array
(
	'autoload' => array
	(
		'WdConfigException' => $includes . 'wdconfigexception.php',
		'WdConstructorModel' => $includes . 'wdconstructormodel.php',
		'WdPModule' => $includes . 'wdpmodule.php',
		'WdPublisher' => $includes . 'wdpublisher.php',
		'WdSectionedForm' => $includes . 'wdsectionedform.php',

		'WdEMailNotifyElement' => $includes . 'wdemailnotifyelement.php',
		'WdManager' => $includes . 'wdmanager.php',
		'WdPDashboard' => $includes . 'wdpdashboard.php',
		'WdPDocument' => $includes . 'wdpdocument.php',
		'WdResume' => $includes . 'resume.php',

		'WdKses' => $includes . 'external/kses/kses.php',
		'WdWidget' => $includes . 'wdwidget.php',

		'publisher_WdHooks' => $includes . 'hooks.php'
	),

	'connections' => array
	(
		'local' => array
		(
			'dsn' => 'sqlite:' . $_SERVER['DOCUMENT_ROOT'] . '/repository/lib/local.sq3'
		)
	),

	'modules' => array
	(
		$path . 'modules'
	)
);