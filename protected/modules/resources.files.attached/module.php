<?php

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

		$file = new WdUploaded('Filedata', null, true);

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
			$path = WdCore::getConfig('repository.temp') . '/' . basename($file->location) . $file->extension;

			$destination = $_SERVER['DOCUMENT_ROOT'] . $path;

			$file->move($destination, true);
		}

		#
		#
		#

		$operation->terminus = true;

		return $this->create_attached_entry($file);
	}

	protected function create_attached_entry($entry)
	{
		$hiddens = null;
		$links = array();

		$i = uniqid();
		$size = wd_format_size($entry->size);

		// FIXME-201000624: The selector of MooTools 1.2.4 doesn't work correctly with '#remove'
		// thus we use the 'remove' class which should disapear as soon as the bug is fixed.

		if ($entry instanceof WdUploaded)
		{
			$title = $entry->name;
			$extension = $entry->extension;

			$hiddens .= '<input type="hidden" class="file" name="resources_files_attached[' . $i .'][file]" value="' . wd_entities(basename($entry->location)) . '" />' . PHP_EOL;
			$hiddens .= '<input type="hidden" name="resources_files_attached[' . $i .'][mime]" value="' . wd_entities($entry->mime) . '" />' . PHP_EOL;

			$links = array
			(
				'<a href="#remove" class="remove">Retirer</a>'
			);
		}
		else
		{
			$fid = $entry->nid;
			$title = $entry->title;
			$extension = substr($entry->path, strpos($entry->path, '.'));

			$hiddens .= '<input type="hidden" name="resources_files_attached[' . $i .'][fileid]" value="' . $fid . '" />';

			$links = array
			(
				'<a href="' . WdRoute::encode('/resources.files/' . $fid . '/edit') . '">Éditer</a>',
				'<a href="/do/resources.files/' . $fid . '/download">Télécharger</a>',
				'<a href="#remove" class="remove warn">Briser le lien</a>'
			);
		}

		$title = wd_entities($title);
		$links = empty($links) ? '' : (' &ndash; ' . implode(', ', $links));

		return <<<EOT
<li>
	<span class="handle">↕</span><input type="text" name="resources_files_attached[$i][title]" value="$title" />
	<span class="small">
		<span class="info light">$size ($extension)</span> $links
	</span>

	$hiddens
</li>
EOT;
	}

	public function event_alter_block_config(WdEvent $event)
	{
		if ($event->module->id != 'resources.files')
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

		if ($event->module instanceof resources_files_WdModule || !$event->module instanceof system_nodes_WdModule)
		{
			return;
		}

		$scope = $registry['resources_files_attached.scope.'];

		if (empty($scope[strtr($event->module->id, '.', '_')]))
		{
			return;
		}

		$document->css->add('public/attached.css');
		$document->js->add('public/attached.js');
		$document->js->add('../resources.files/public/fancyupload/Swiff.Uploader.js');

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
				$lines .= $this->create_attached_entry($entry);
			}
		}


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

<button type="button">Attacher un nouveau fichier</button>
<div class="element-description">La taille maximum du fichier est de 2 Mo. Seuls les fichiers avec les
extensions suivantes peuvent être transférés : jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp.</div>

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
						'title' => 'Pièces attachées'
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

		$model = $this->model();
		$files_model = $core->models['resources.files'];

//		var_dump($params['resources_files_attached']);

		$weight = 0;
		$attached_fileids = array();

		foreach ($params['resources_files_attached'] as $attached_params)
		{
			if (isset($attached_params['file']))
			{
				#
				# create
				#

				$attached_params['path'] = WdCore::getConfig('repository.temp') . '/' . $attached_params['file'];
				$attached_params['is_online'] = true;

				$fileid = $files_model->save($attached_params);

				if (!$fileid)
				{
					wd_log_error('Unable to save file: \1', array($attached_params));

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

				$attached_fileids[] = $fileid;
			}
			else if (isset($attached_params['fileid']))
			{
				#
				# update
				#

				$fileid = $attached_params['fileid'];

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

		if ($event->module instanceof resources_files_WdModule)
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
		else if ($event->module instanceof system_nodes_WdModule)
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
	 *
	 * Clear the current registry values for the 'resources_files_attached.scope' key, before the
	 * new one are saved. This is beacause unchecked values don't return 'off', they are just not
	 * defined.
	 *
	 * @param WdEvent $event
	 */

	public function event_operation_config_before(WdEvent $event)
	{
		if ($event->module->id != 'resources.files')
		{
			return;
		}

		global $registry;

		$registry['resources_files_attached.scope'] = null;
	}

	static public function event_ar_property(WdEvent $event)
	{
		global $core;

		if ($event->property != 'attached_files' || !$event->ar instanceof system_nodes_WdActiveRecord)
		{
			return;
		}

		$event->value = $core->db()->query
		(
			'SELECT node.*, file.*, IF(attached.title, attached.title, node.title) AS title FROM {prefix}system_nodes node
			INNER JOIN {prefix}resources_files file USING(nid)
			INNER JOIN {prefix}resources_files_attached attached ON attached.fileid = file.nid
			WHERE attached.nodeid = ? AND attached.fileid = file.nid ORDER BY attached.weight', array
			(
				$event->ar->nid
			)
		)
		->fetchAll(PDO::FETCH_CLASS, 'resources_files_WdActiveRecord');
	}
}