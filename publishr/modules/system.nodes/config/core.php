<?php

return array
(
	'autoload' => array
	(
		'system_nodes_view_WdMarkup' => $root . 'markups.php',
		'system_nodes_list_WdMarkup' => $root . 'markups.php',

		'WdAdjustNodeElement' => $root . 'elements/adjustnode.php',
		'WdPopNodeElement' => $root . 'elements/pop-node.php',
		'WdTitleSlugComboElement' => $root . 'elements/titleslugcombo.php',
		'adjustnode_WdEditorElement' => $root . 'elements/adjustnode.editor.php'
	),

	'classes aliases' => array
	(
		'Node' => 'system_nodes_WdActiveRecord'
	)
);