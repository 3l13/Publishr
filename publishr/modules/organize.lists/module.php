<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class organize_lists_WdModule extends system_nodes_WdModule
{
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
		global $core;

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

					'nodes' => new WdAdjustNodesListWidget
					(
						array
						(
							//WdForm::T_LABEL => 'Entrées',
							WdAdjustNodesListWidget::T_SCOPE => $scope,
							WdAdjustNodesListWidget::T_LIST_ID => $properties[Node::NID],

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

		$core->document->js->add('public/edit.js');

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