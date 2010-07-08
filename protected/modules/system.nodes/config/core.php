<?php

return array
(
	'autoload' => array
	(
		'system_nodes_WdEvents' => $root . 'events.php',
		'system_nodes_WdMarkups' => $root . 'markups.php',
		'system_nodes_WdManager' => $root . 'manager.php',

		'system_nodes_view_WdMarkup' => $root . 'markups.php',

		'WdAdjustNodeElement' => $root . 'includes/wdadjustnodeelement.php',
		'WdPopNodeElement' => $root . 'includes/wdpopnodeelement.php',
		'WdTitleSlugComboElement' => $root . 'includes/wdtitleslugcomboelement.php'
	),

	'classes aliases' => array
	(
		'Node' => 'system_nodes_WdActiveRecord'
	)
);