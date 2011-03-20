<?php

$includes = $path . 'includes' . DIRECTORY_SEPARATOR;
$operations = $includes . 'operations' . DIRECTORY_SEPARATOR;

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

		'publisher_WdHooks' => $includes . 'hooks.php',

		'publishr_save_WdOperation' => $operations . 'save.php',
		'constructor_save_WdOperation' => $operations . 'constructor_save.php',
		'widget_get_WdOperation' => $operations . 'widget_get.php',
		'config_WdOperation' => $operations . 'config.php',
		'lock_WdOperation' => $operations . 'lock.php',
		'unlock_WdOperation' => $operations . 'unlock.php',
		'query_operation_WdOperation' => $operations . 'query-operation.php',
		'blocks_WdOperation' => $operations . 'blocks.php'
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