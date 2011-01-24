<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class organize_lists_WdModule extends system_nodes_WdModule
{
	protected function operation_save(WdOperation $operation)
	{
		$rc = parent::operation_save($operation);

		try
		{
			$listid = $rc['key'];
			$model = $this->model('nodes');
			$model->where('listid = ?', $listid)->delete();

			$params = &$operation->params;

			if (isset($params['nodes']))
			{
				$nodes = $params['nodes'];
				$labels = $params['labels'];

				$weight = 0;

				foreach ($nodes as $i => $nodeid)
				{
					$model->insert
					(
						array
						(
							'listid' => $listid,
							'nodeid' => $nodeid,
							'weight' => $weight++,
							'label' => $labels[$i]
						)
					);
				}
			}
		}
		catch (Exception $e)
		{
			wd_log_error($e->getMessage());
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
			$value = $this->model('nodes')->select('nodeid')->where('listid = ?', $properties[Node::NID])->order('weight')->all(PDO::FETCH_COLUMN);
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
							//WdForm::T_LABEL => 'Entrées',
							WdAdjustNodesList::T_SCOPE => $scope,
							WdAdjustNodesList::T_LIST_ID => $properties[Node::NID],

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

		foreach ($core->modules->descriptors as $module_id => $descriptor)
		{
			if (empty($descriptor[self::T_MODELS]['primary']))
			{
				continue;
			}

			if (empty($core->modules[$module_id]))
			{
				continue;
			}

			$model = $descriptor[self::T_MODELS]['primary'];

			$is_instance = WdModel::is_extending($model, 'system.nodes');

			if (!$is_instance)
			{
				continue;
			}

			$scopes[$module_id] = t($descriptor[self::T_TITLE]);
		}

		asort($scopes);

		return $scopes;
	}
}