<?php

class WdAdjustNodesList extends WdElement
{
	const T_SCOPE = '#adjust-scope';
	const T_SEARCH_DESCRIPTION = '#adjust-search-description';
	const T_LIST_DESCRIPTION = '#adjust-list-description';
	const T_LIST_ID = '#adjust-list-id';

	public function __construct($tags=array(), $dummy=null)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				self::T_SEARCH_DESCRIPTION => "Ci-dessus, les entrées qui peuvent être
				utilisées pour composer votre liste. Cliquez sur une entrée pour l'ajouter.
				Utilisez le champ de recherche pour filtrer les entrées.",

				self::T_LIST_DESCRIPTION => "Ci-dessus, les entrées qui composent la liste.
				L'ordre peut-être modifié par glissé-déposé.",

				'class' => 'wd-adjustnodeslist'
			)
		);

		global $document;

		$document->js->add('wdadjustnodeslist.js');
		$document->css->add('wdadjustnodeslist.css');
	}

	protected function getInnerHTML()
	{
		global $core;

		$scope = $this->get(self::T_SCOPE, 'system.nodes');
		$module = $core->getModule($scope);

		$search_description = $this->get(self::T_SEARCH_DESCRIPTION);
		$list_description = $this->get(self::T_LIST_DESCRIPTION);

		$options = array
		(
			'scope' => $scope,
			'name' => $this->get('name'),
			'listId' => $this->get(self::T_LIST_ID)
		);

		$rc = '<div class="search">' .
		'<h4>Ajouter des entrées</h4>' .

		'<div>' .
		'<input type="text" class="search" />' .
		'<input type="hidden" class="wd-element-options" value="' . wd_entities(json_encode($options)) . '" />' .
		'</div>' .

		$this->getResults($module) .

		($search_description ? '<div class="element-description">' . $search_description . '</div>' : '') .

		'</div>';

		$rc .= '<div class="list">' .
		'<h4>Entrées qui composent la liste</h4>' .
		'<ul>' . $this->getEntries($module) . '</ul>' .

		($list_description ? '<div class="element-description">' . $list_description . '</div>' : '') .

		'</div>';

		return $rc;
	}

	protected function getResults($module)
	{
		return $module->getBlock('adjustResults');
	}

	protected function getEntries($module)
	{
		$nodes = array();
		$ids = $this->get('value');

		if ($ids)
		{
			$nodes = $module->model()->loadAll
			(
				'WHERE nid IN(' . implode(',', $ids) . ')'
			)
			->fetchAll();

			$nodes = WdArray::reorder_by_property($nodes, $ids, Node::NID);
		}

		#
		# labels
		#

		$list_id = $this->get(self::T_LIST_ID);

		if ($list_id)
		{
			global $core;

			$nodes_by_nid = array();

			foreach ($nodes as $node)
			{
				$nodes_by_nid[$node->nid] = $node;
			}

			$entries = $core->models['organize.lists/nodes']->loadAll('WHERE listid = ?', array($list_id))->fetchAll();

			foreach ($entries as $entry)
			{
				$nodes_by_nid[$entry->nodeid]->label = $entry->label;
			}
		}

		#
		#
		#

		$rc = '<li class="holder">Déposez ici les objets de la liste</li>';

		foreach ($nodes as $node)
		{
			$rc .= '<li class="sortable">' . self::create_entry($node, null, $module) . '</li>';
		}

		return $rc;
	}

	static protected function create_entry($node, $entry, $module)
	{
		$title = $node->title;
		$label = isset($node->label) ? $node->label : $node->title;

		$rc  = '<span class="handle">↕</span>' . PHP_EOL;
		$rc .= $module->adjust_createEntry($node);
		$rc .= '<input type="text" name="labels[]" value="' . wd_entities($label) . '" title="Titre original&nbsp;: ' . wd_entities($title) . '" />';

		return $rc;
	}

	static public function operation_add(WdOperation $operation)
	{
		global $core;

		$nid = $operation->params['nid'];

		$node = $core->models['system.nodes']->load($nid);
		$module = $core->getModule($node->constructor);

		return self::create_entry($node, null, $module);
	}
}