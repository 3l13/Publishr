<?php

$widgets_path = $path . 'widgets' . DIRECTORY_SEPARATOR;

return array
(
	'autoload' => array
	(
		'system_nodes_view_WdMarkup' => $path . 'markups.php',
		'system_nodes_list_WdMarkup' => $path . 'markups.php',

		'WdAdjustNodeWidget' => $widgets_path . 'adjust-node.php',
		'WdPopNodeWidget' => $widgets_path . 'pop-node.php',
		'WdTitleSlugComboWidget' => $widgets_path . 'title-slug-combo.php',
		'adjustnode_WdEditorElement' => $widgets_path . 'adjust-node.editor.php'
	),

	'classes aliases' => array
	(
		'Node' => 'system_nodes_WdActiveRecord'
	)
);