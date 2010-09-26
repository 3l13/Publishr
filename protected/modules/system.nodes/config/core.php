<?php

return array
(
	'autoload' => array
	(
		'system_nodes_WdEvents' => $root . 'events.php',
		'system_nodes_WdMarkups' => $root . 'markups.php',
		'system_nodes_WdManager' => $root . 'manager.php',

		'system_nodes_view_WdMarkup' => $root . 'markups.php',

		'WdAdjustNodeElement' => $root . 'elements/wdadjustnodeelement.php',
		'WdPopNodeElement' => $root . 'elements/wdpopnodeelement.php',
		'WdTitleSlugComboElement' => $root . 'elements/titleslugcombo.element.php'
	),

	'classes aliases' => array
	(
		'Node' => 'system_nodes_WdActiveRecord'
	)
);