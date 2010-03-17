<?php

return array
(
	'autoload' => array
	(
		'system_nodes_WdMarkups' => $root . 'markups.php',
		'system_nodes_WdManager' => $root . 'manager.php',

		'WdTitleSlugComboElement' => $root . 'includes' . DIRECTORY_SEPARATOR . 'wdtitleslugcomboelement.php'
	),

	'classes aliases' => array
	(
		'Node' => 'system_nodes_WdActiveRecord'
	)
);