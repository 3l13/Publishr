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
					'title', 'uid', 'is_online', 'modified'
				)
			)
		);
	}

	protected function block_edit(array $properties, $permission)
	{
		$value = array();

		if (isset($properties['nodes']))
		{
			$value = array_map('intval', $properties['nodes']);
		}
		else if ($properties[Node::NID])
		{
			$value = $this->model('nodes')->select('nodeid', 'WHERE listid = ? ORDER BY weight', array($properties[Node::NID]))->fetchAll(PDO::FETCH_COLUMN);
		}

		$scope = $properties['scope'] ? $properties['scope'] : 'system.nodes';
		$scopes = $this->getScopes();

		$rc = wd_array_merge_recursive
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
							des entrées qui composent la liste.",

							'value' => $scope
						)
					),

					'nodes' => new WdAdjustNodesList
					(
						array
						(
							WdForm::T_LABEL => 'Entrées',
							WdAdjustNodesList::T_SCOPE => $scope,

							'value' => $value
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

		global $document;

		$document->js->add('public/edit.js');

		return $rc;
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