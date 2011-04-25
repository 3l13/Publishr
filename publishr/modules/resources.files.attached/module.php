<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class resources_files_attached_WdModule extends WdPModule
{
	const OPERATION_UPLOAD = 'upload';

	public function event_alter_block_config(WdEvent $event)
	{
		global $core;

		if ($event->target->id != 'resources.files')
		{
			return;
		}

		$scope = array();

		foreach ($core->modules->descriptors as $constructor => $descriptor)
		{
			if (empty($core->modules[$constructor]) || $constructor == 'system.nodes')
			{
				continue;
			}

			if (!WdModule::is_extending($constructor, 'system.nodes'))
			{
				continue;
			}

			$constructor = strtr($constructor, '.', '_');
			$scope[$constructor] = t($descriptor[self::T_TITLE]);
		}

		asort($scope);

		$scope_value = $core->registry["$this->flat_id.scope"];

		if ($scope_value)
		{
			$scope_value = explode(',', $scope_value);
			$scope_value = array_combine($scope_value, array_fill(0, count($scope_value), true));
		}

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdElement::T_GROUPS => array
				(
					'attachments' => array
					(
						'title' => '.attachments',
						'class' => 'form-section flat'
					)
				),

				WdElement::T_CHILDREN => array
				(
					"global[$this->flat_id.scope]" => new WdElement
					(
						WdElement::E_CHECKBOX_GROUP, array
						(
							WdForm::T_LABEL => t('resources_files_attached.element.label.scope'),
							WdElement::T_OPTIONS => $scope,
							WdElement::T_GROUP => 'attachments',

							'class' => 'list combo',
							'value' => $scope_value
						)
					)
				)
			)
		);
	}

	public function event_operation_save(WdEvent $event)
	{
		global $core;

		$operation = $event->operation;
		$params = &$operation->params;
		$nid = $operation->response->rc['key'];

		if (empty($params['resources_files_attached']))
		{
			return;
		}

		$model = $this->model;

		$files_model = $core->models['resources.files'];
		$images_model = $core->models['resources.images'];

		$root = $_SERVER['DOCUMENT_ROOT'];
		$repository = $core->config['repository.temp'] . '/';

		$weight = 0;
		$attached_fileids = array();

		foreach ($params['resources_files_attached'] as $attached_params)
		{
			if (isset($attached_params['file']))
			{
				#
				# create
				#

				$path = $repository . $attached_params['file'];

				$attached_params['path'] = $path;
				$attached_params['is_online'] = true;

				if (getimagesize($root . $path))
				{
					$fileid = $images_model->save
					(
						$attached_params + array
						(
							Node::SITEID => $core->site_id,
							Node::CONSTRUCTOR => 'resources.images'
						)
					);
				}
				else
				{
					$fileid = $files_model->save
					(
						$attached_params + array
						(
							Node::SITEID => $core->site_id,
							Node::CONSTRUCTOR => 'resources.files'
						)
					);
				}

				if (!$fileid)
				{
					WdDebug::trigger('Unable to save file: \1', array($attached_params));

					continue;
				}

				$model->save
				(
					array
					(
						'nodeid' => $nid,
						'fileid' => $fileid,
						'title' => $attached_params['title'],
						'weight' => $weight
					)
				);

//				var_dump('saving: \1, \2', array($fileid, $attached_params));

				$attached_fileids[] = $fileid;
			}
			else if (isset($attached_params['fileid']))
			{
				$fileid = $attached_params['fileid'];

				if ($attached_params['title'] == '!delete')
				{
					$file = $files_model[$fileid];

					$delete_operation = WdOperation::decode("/api/{$file->constructor}/{$fileid}/delete");
					$delete_operation->__invoke();

					continue;
				}
				else if ($attached_params['title'] == '!remove')
				{
					continue;
				}

				$model->execute
				(
					'UPDATE {self} SET title = ?, weight = ? WHERE nodeid = ? AND fileid = ?', array
					(
						$attached_params['title'], $weight, $nid, $fileid
					)
				);

				$attached_fileids[] = $fileid;
			}

			$weight++;
		}

		#
		# we remove the link to unspecified files.
		#

		$model->execute
		(
			'DELETE FROM {self} WHERE nodeid = ?' . ($attached_fileids ? ' AND fileid NOT IN(' . implode(',', $attached_fileids) . ')' : ''), array
			(
				$nid
			)
		);
	}

	public function event_operation_delete(WdEvent $event)
	{
		$key = $event->operation->key;

		#
		# since resources_files_WdModule is an instance of system_nodes_WdModule, we have to check
		# it first.
		#

		if ($event->target instanceof resources_files_WdModule)
		{
			#
			# delete attached on fileid
			#

			$this->model->where('fileid = ?', $key)->delete();
		}
		else if ($event->target instanceof system_nodes_WdModule)
		{
			#
			# delete attached on nodeid
			#

			$this->model->where('nodeid = ?', $key)->delete();
		}
	}

	/**
	 * Clears the current registry values for the 'resources_files_attached.scope' key, before the
	 * new one are saved. This is beacause unchecked values don't return 'off', they are just not
	 * defined.
	 *
	 * @param WdEvent $event
	 */

	static private $config_scope;

	public function event_operation_config_before(WdEvent $event)
	{
		if ($event->target->id != 'resources.files')
		{
			return;
		}

		$params = &$event->operation->params;

		if (isset($params['global']["$this->flat_id.scope"]))
		{
			self::$config_scope = $params['global']["$this->flat_id.scope"];
		}

		unset($params['global']["$this->flat_id.scope"]);
	}

	public function event_operation_config(WdEvent $event)
	{
		global $core;

		$scope = null;

		if (self::$config_scope)
		{
			$scope = array_keys(self::$config_scope);
			$scope = implode(',', $scope);
		}

		$core->registry["$this->flat_id.scope"] = $scope;
	}
}