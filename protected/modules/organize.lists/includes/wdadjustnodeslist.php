<?php

class WdAdjustNodesList extends WdElement
{
	const T_SCOPE = '#adjust-scope';
	const T_SEARCH_DESCRIPTION = '#adjust-search-description';
	const T_LIST_DESCRIPTION = '#adjust-list-description';

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

		$document->addJavaScript('../public/wdadjustnodeslist.js');
		$document->addStyleSheet('../public/wdadjustnodeslist.css');
	}

	protected function getInnerHTML()
	{
		global $core;

		$scope = $this->getTag(self::T_SCOPE, 'system.nodes');
		$module = $core->getModule($scope);

		$search_description = $this->getTag(self::T_SEARCH_DESCRIPTION);
		$list_description = $this->getTag(self::T_LIST_DESCRIPTION);

		$options = array
		(
			'scope' => $scope,
			'name' => $this->getTag('name')
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
		$ids = $this->getTag('value');

		if ($ids)
		{
			$nodes = $module->model()->loadAll
			(
				'WHERE nid IN(' . implode(',', $ids) . ')'
			)
			->fetchAll();

			$nodes = WdArray::reorderByProperty($nodes, $ids, Node::NID);
		}

		$rc = '<li class="holder">Déposez ici les objets de la liste</li>';

		foreach ($nodes as $entry)
		{
			$rc .= '<li class="sortable">' . $module->adjust_createEntry($entry) . '</li>';
		}

		return $rc;
	}
}