<?php

class organize_lists_WdModule extends system_nodes_WdModule
{
	protected function operation_save(WdOperation $operation)
	{
		$rc = parent::operation_save($operation);

		if ($rc)
		{
			$nid = $rc['key'];

			$model = $this->model('nodes');

			$model->execute
			(
				'DELETE FROM {self} WHERE listid = ?', array
				(
					$nid
				)
			);

			$params = &$operation->params;

			if (isset($params['nodes']))
			{
				$pages = $params['nodes'];

				$weight = 0;

				foreach ($pages as $pageid)
				{
					$model->insert
					(
						array
						(
							'listid' => $nid,
							'nodeid' => $pageid,
							'weight' => $weight++
						)
					);
				}
			}
		}

		return $rc;
	}

	protected function block_manage()
	{
		return new organize_lists_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'uid', 'is_online'
				)
			)
		);
	}

	protected function block_edit(array $properties, $permission)
	{
		global $document;

		$document->addJavaScript('public/wdadjustnodeslist.js');

		$document->addStyleSheet('public/edit.css');
		$document->addJavaScript('public/edit.js');

		global $core;

		$scope = $properties['scope'] ? $properties['scope'] : 'system.nodes';

		$module = $core->getModule($scope);
		$model = $module->model();

		#
		# results
		#

		$results = $module->getBlock('adjustResults');

		#
		# list entries
		#

		$nodes = array();
		$entries = '<li class="holder">Déposez ici les objets de la liste</li>';

		if (isset($properties['nodes']))
		{
			$ids = array_map('intval', $properties['nodes']);

			$nodes = $model->loadAll('WHERE nid IN(' . implode(', ', $ids) . ')');
		}
		else if ($properties[Node::NID])
		{
			$nodes = $model->loadAll
			(
				'INNER JOIN {prefix}organize_lists_nodes AS jn ON nid = nodeid
				WHERE listid = ? ORDER BY jn.weight, title', array
				(
					$properties[Node::NID]
				)
			);
		}

		foreach ($nodes as $entry)
		{
			$entries .= '<li class="sortable">' . $module->adjust_createEntry($entry) . '</li>';
		}

		#
		#
		#

		$options = array
		(
			'scope' => $scope
		);

		$scopes = $this->getScopes();

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdElement::T_CHILDREN => array
				(
					'scope' => new WdElement
					(
						'select', array
						(
							WdForm::T_LABEL => 'Portée',
							WdElement::T_OPTIONS => array('system.nodes' => '') + $scopes,
							WdElement::T_DESCRIPTION => "La « portée » permet de choisir le type
							des entrées qui composeront la liste.",

							'value' => $scope
						)
					),

					new WdElement
					(
						'div', array
						(
							WdForm::T_LABEL => 'Entrées',

							WdElement::T_CHILDREN => array
							(
								'<div id="song-search" class="search">' .
								'<h4>Ajouter des entrées</h4>' .

								'<div>' .
								'<input type="text" class="search" />' .
								'<input type="hidden" class="wd-element-options" value="' . wd_entities(json_encode($options)) . '" />' .
								'</div>' .

								$results .

								'<div class="element-description">' .
								"Ci-dessus, la liste des entrées qui peuvent être utilisées pour
								composer votre liste. Utilisez le champ de recherche pour filtrer
								les entrées." .
								'</div>' .
								'</div>',

								'<div class="list">' .
								'<h4>Entrées qui composent la liste</h4>' .
								'<ul>' . $entries . '</ul>' .
								'<div class="element-description">Les pages ci-dessus forment
								votre menu. Vous pouvez ajouter d\'autres pages
								depuis le panneau <em>Ajouter des pages</em>, ou en retirer en
								cliquant sur le bouton <em>Retirer du menu</em> situé en
								tête de chaque page.</div>' .
								'</div>'
							),

							'class' => 'wd-adjustnodeslist'
						)
					),

					'description' => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => 'Description',

							'rows' => 5
						)
					)
				)
			)
		);
	}

	protected function getScopes()
	{
		global $core;

		$scopes = array();

		foreach ($core->descriptors as $module_id => $descriptor)
		{
			if (empty($descriptor[self::T_MODELS]['primary']))
			{
				continue;
			}

			if (!$core->hasModule($module_id))
			{
				continue;
			}

			$model = $descriptor[self::T_MODELS]['primary'];

			$is_instance = self::modelInstanceof($model, 'system.nodes');

			if (!$is_instance)
			{
				continue;
			}

			$scopes[$module_id] = t($descriptor[self::T_TITLE]);
		}

		asort($scopes);

		return $scopes;
	}

	static protected function modelInstanceof($descriptor, $instanceof)
	{
		if (empty($descriptor[WdModel::T_EXTENDS]))
		{
			//wd_log('no extends in \1', array($model));

			return false;
		}

		$extends = $descriptor[WdModel::T_EXTENDS];

		if ($extends == $instanceof)
		{
			//wd_log('found instance of with: \1', array($model));

			return true;
		}

		global $core;

		if (empty($core->descriptors[$extends][WdModule::T_MODELS]['primary']))
		{
			//wd_log('no primary for: \1', array($extends));

			return false;
		}

		//wd_log('try: \1', array($extends));

		return self::modelInstanceof($core->descriptors[$extends][WdModule::T_MODELS]['primary'], $instanceof);
	}
}