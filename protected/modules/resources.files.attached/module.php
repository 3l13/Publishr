<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
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
		if ($event->target->id != 'resources.files')
		{
			return;
		}

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

			$is_instance = WdModel::is_extending($model, 'system.nodes');

			if (!$is_instance)
			{
				continue;
			}

			$module_id = strtr($module_id, '.', '_');

			$scopes[$module_id] = t($descriptor[self::T_TITLE]);
		}

		asort($scopes);

		$base = 'resources_files_attached';

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdElement::T_CHILDREN => array
				(
					$base . '[scope]' => new WdElement
					(
						WdElement::E_CHECKBOX_GROUP, array
						(
							WdForm::T_LABEL => 'Activer les pièces attachées pour les modules suivants',
							WdElement::T_OPTIONS => $scopes
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

		if ($target instanceof resources_files_WdModule || !$target instanceof system_nodes_WdModule)
		{
			return;
		}

		$scope = $registry['resources_files_attached.scope.'];

		if (empty($scope[$target->flat_id]))
		{
			return;
		}

		$document->css->add('public/attached.css');
		$document->js->add('public/attached.js');
		$document->js->add('../resources.files/elements/Swiff.Uploader.js');

		$lines = null;

		if ($event->key)
		{
			$entries = $this->model()->query
			(
				'SELECT attached.*, file.nid, file.size, file.path
				FROM {self} attached
				INNER JOIN {prefix}resources_files file ON attached.fileid = file.nid
				WHERE nodeid = ?', array
				(
					$event->key
				)
			)
			->fetchAll(PDO::FETCH_OBJ);

			foreach ($entries as $entry)
			{
				$lines .= WdAttachedFilesElement::create_attached_entry($entry);
			}
		}

		$formats = null;

		//$formats = 'Seules les pièces avec les extensions suivantes sont prises en charge&nbsp;: jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp.';

		$limit = ini_get('upload_max_filesize') * 1024 * 1024;
		$limit_formated = wd_format_size($limit);

		$options = array
		(
			'path' => WdDocument::getURLFromPath('../resources.files/elements/Swiff.Uploader.swf'),
			'verbose' => false,
			'fileSizeMax' => $limit
		);

		$options_el = '<input type="hidden" class="element-options" value="' . wd_entities(json_encode($options)) . '" />';

		$children = array
		(
			new WdElement
			(
				'div', array
				(
					WdElement::T_GROUP => 'attached_files',
					WdElement::T_INNER_HTML => <<<EOT
<div class="resources-files-attached">
	<ol>
		$lines
		<li class="progress">&nbsp;</li>
	</ol>

	<button type="button">Joindre une nouvelle pièce</button>

	<div class="element-description">La taille maximum de chaque pièce est de $limit_formated.$formats</div>

	$options_el
</div>
EOT
				)
			)
		);

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

				WdElement::T_CHILDREN => $children
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
					$file = $files_model->load($fileid);

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
		#
		# since resources_files_WdModule is an instance of system_nodes_WdModule, we have to check
		# it first.
		#

		if ($event->target instanceof resources_files_WdModule)
		{
			#
			# delete attached on fileid
			#

			$this->model()->execute
			(
				'DELETE FROM {self} WHERE fileid = ?', array
				(
					$event->operation->key
				)
			);
		}
		else if ($event->target instanceof system_nodes_WdModule)
		{
			#
			# delete attached on nodeid
			#

			$this->model()->execute
			(
				'DELETE FROM {self} WHERE nodeid = ?', array
				(
					$event->operation->key
				)
			);
		}
	}

	/**
	 * Clears the current registry values for the 'resources_files_attached.scope' key, before the
	 * new one are saved. This is beacause unchecked values don't return 'off', they are just not
	 * defined.
	 *
	 * @param WdEvent $event
	 */

	public function event_operation_config_before(WdEvent $event)
	{
		if ($event->target->id != 'resources.files')
		{
			return;
		}

		global $registry;

		$registry['resources_files_attached.scope'] = null;
	}
}