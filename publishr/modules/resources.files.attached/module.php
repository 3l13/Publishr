<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_files_attached_WdModule extends WdPModule
{
	const OPERATION_UPLOAD = 'upload';

	protected function validate_operation_upload(WdOperation $operation)
	{
//		self::cleanRepository(self::repository('temp'), 3600);

		#
		# we set the HTTP_ACCEPT ourselves to force JSON output
		#

		$_SERVER['HTTP_ACCEPT'] = 'application/json';

		#
		# TODO-20100624: we use 'Filedata' because it's used by Swiff.Uploader, we have to change
		# that as soon as possible.
		#

		#
		# TODO-20100624: we should use the `accept` parameter.
		#

		$file = new WdUploaded
		(
			'Filedata', /*array
			(
				'image/jpeg',
				'image/gif',
				'image/png',

				'txt' => 'text/plain',
				'doc' => 'application/msword',
				'xls' => 'application/vnd.ms-excel',
				'pdf' => 'application/pdf',
				'ppt' => 'application/vnd.ms-powerpoint',
				'pps' => 'application/vnd.ms-powerpoint',

				'odt' => 'application/vnd.oasis.opendocument.text', // Texte formaté
				'ods' => 'application/vnd.oasis.opendocument.spreadsheet', // Tableur
				'odp' => 'application/vnd.oasis.opendocument.presentation', // Présentation
				'odg' => 'application/vnd.oasis.opendocument.graphics', // Dessin
				'odc' => 'application/vnd.oasis.opendocument.chart', // Diagramme
				'odf' => 'application/vnd.oasis.opendocument.formula', // Formule
				'odb' => 'application/vnd.oasis.opendocument.database', // Base de données
				'odi' => 'application/vnd.oasis.opendocument.image', // Image
				'odm' => 'application/vnd.oasis.opendocument.text-master' // Document principal
			)*/ null,

			true
		);

		if ($file->er)
		{
			wd_log_error($file->er_message);

			$operation->response->file = $file;

			return false;
		}

		$operation->file = $file;

		return true;
	}

	protected function operation_upload(WdOperation $operation)
	{
		$file = $operation->file;
		$path = null;

		if ($file->location)
		{
			$uniqid = uniqid('', true);

			$destination = WdCore::$config['repository.temp'] . '/' . $uniqid . $file->extension;

			$file->move($_SERVER['DOCUMENT_ROOT'] . $destination, true);
		}

		#
		#
		#

		$operation->terminus = true;

		return WdAttachedFilesElement::create_attached_entry($file);
	}

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
					'attached' => array
					(
						'title' => 'Pièces jointes',
						'class' => 'form-section flat'
					)
				),

				WdElement::T_CHILDREN => array
				(
					"global[$this->flat_id.scope]" => new WdElement
					(
						WdElement::E_CHECKBOX_GROUP, array
						(
							WdForm::T_LABEL => 'Activer les pièces jointes pour les modules suivants',
							WdElement::T_OPTIONS => $scope,
							WdElement::T_GROUP => 'attached',

							'class' => 'list combo',
							'value' => $scope_value
						)
					)
				)
			)
		);
	}

	public function event_alter_block_edit(WdEvent $event)
	{
		global $registry, $document;

		$target = $event->target;

		if ($target instanceof resources_files_WdModule)
		{
			return;
		}

		$scope = $registry['resources_files_attached.scope'];

		if (empty($scope[$target->flat_id]))
		{
			return;
		}

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdElement::T_GROUPS => array
				(
					'attached_files' => array
					(
						'title' => 'Pièces jointes',
						'class' => 'form-section flat'
					)
				),

				WdElement::T_CHILDREN => array
				(
					new WdAttachedFilesElement
					(
						array
						(
							WdElement::T_GROUP => 'attached_files',

							WdAttachedFilesElement::T_NODEID => $event->key,
							WdAttachedFilesElement::T_HARD_BOND => true
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

//		var_dump($params['resources_files_attached']);

		$root = $_SERVER['DOCUMENT_ROOT'];
		$repository = WdCore::$config['repository.temp'] . '/';

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

					$delete_operation = new WdOperation
					(
						$file->constructor, self::OPERATION_DELETE, array
						(
							WdOperation::KEY => $fileid
						)
					);

					$delete_operation->dispatch();

					continue;
				}

				#
				# update
				#

				// FIXME-20100624: There is a bug the update method, it doesn't seam to work with
				// multiple keys

				/*
				$model->update
				(
					array
					(
						$nid, $fileid
					),

					array
					(
						'title' => $attached_params['title'],
						'weight' => $weight
					)
				);
				*/

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

//		var_dump('attached_file_ids: \1', array($attached_fileids));

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

	private $config_scope;

	public function event_operation_config_before(WdEvent $event)
	{
		if ($event->target->id != 'resources.files')
		{
			return;
		}

		$params = &$event->operation->params;

		if (isset($params['global']["$this->flat_id.scope"]))
		{
			$this->config_scope = $params['global']["$this->flat_id.scope"];
		}

		unset($params['global']["$this->flat_id.scope"]);
	}

	public function event_operation_config(WdEvent $event)
	{
		global $registry;

		$scope = null;

		if ($this->config_scope)
		{
			$scope = array_keys($this->config_scope);
			$scope = implode(',', $scope);
		}

		$registry["$this->flat_id.scope"] = $scope;
	}
}