<?php

return array
(
	'autoload' => array
	(
		'system_nodes_view_WdMarkup' => $root . 'markups.php',
		'system_nodes_list_WdMarkup' => $root . 'markups.php',

		'WdAdjustNodeElement' => $root . 'elements/wdadjustnodeelement.php',
		'WdPopNodeElement' => $root . 'elements/wdpopnodeelement.php',
		'WdTitleSlugComboElement' => $root . 'elements/titleslugcombo.element.php'
	),

	'classes aliases' => array
	(
		'Node' => 'system_nodes_WdActiveRecord'
	)
);