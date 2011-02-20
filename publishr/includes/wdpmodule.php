<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

/*

The purpose of this class is to extend WdModule with user's privilege checking (mainly on operations)

*/

class WdPModule extends WdModule
{
	const OPERATION_DOWNLOAD = 'download'; // FIXME-20081223: this should be obsolete, and defined in the resources.files module

	const OPERATION_SAVE_MODE = '#operation-save-mode';
	const OPERATION_SAVE_MODE_CONTINUE = 'continue';
	const OPERATION_SAVE_MODE_LIST = 'list';
	const OPERATION_SAVE_MODE_NEW = 'new';

	const OPERATION_QUERY_OPERATION = 'queryOperation'; // TODO-20101125: rename to BATCH

	protected function controls_for_operation_queryOperation(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_AUTHENTICATION => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	/**
	 * Overrides the operation to handle the operation's save mode, which results in the request
	 * being redirected to another location:
	 *
	 * - LIST: The request is redirected to the constructor's index location, generaly its
	 * manager's location.
	 * - CONTINUE: The request is redirected to the record's edit location.
	 * - NEW: The request is redirected to an empty editor, ready to create a new record.
	 *
	 * The save mode is stored on a constructor basis, in the user's session.
	 *
	 * @see WdModule::operation_save()
	 */

	protected function operation_save(WdOperation $operation)
	{
		global $core;

		$params = &$operation->params;
		$mode = isset($params[self::OPERATION_SAVE_MODE]) ? $params[self::OPERATION_SAVE_MODE] : null;

		if ($mode)
		{
			$core->session->wdpmodule['save_mode'][$this->id] = $mode;
		}

		$rc = parent::operation_save($operation);

		#
		# choose possible redirection, depending on save mode
		#

		if ($mode)
		{
			#
			# list (default): we are done with the editing and we want to see all of our lovely entries.
			#

			$route = '/admin/' . $this->id;

			switch ($mode)
			{
				#
				# continue: we continue edition. there is no redirection unless we were creating
				# a new entry, in which case we are redirected to the url used to edit this entry
				#

				case self::OPERATION_SAVE_MODE_CONTINUE:
				{
					$route .= '/' . $rc['key'] . '/edit';
				}
				break;

				#
				# new: we are done with this entry and we want to create a new one.
				#

				case self::OPERATION_SAVE_MODE_NEW:
				{
					$route .= '/create';
				}
				break;
			}

			$operation->location = $route;
		}

		return $rc;
	}

	protected function operation_delete(WdOperation $operation)
	{
		$key = $operation->key;

		if (!$this->model->delete($key))
		{
			wd_log_error('Unable to delete the entry %key from the %module module.', array('%key' => $key, '%module' => $this->id));

			return;
		}

		wd_log_done('The entry %key has been delete from the %module module.', array('%key' => $key, '%module' => $this->id));

		return $key;
	}

	/**
	 * Used this operation to configure the module.
	 *
	 * There are two spaces for the configuration to be saved in : a local space and a global
	 * space.
	 *
	 * Configuration in the local space is saved in the `metas` of the working site object, whereas
	 * the configuration in the global space is saved in the registry.
	 *
	 */

	const OPERATION_CONFIG = 'config';

	protected function controls_for_operation_config(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_ADMINISTER,
			self::CONTROL_FORM => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_config(WdOperation $operation)
	{
		global $core;

		$params = &$operation->params;

		if (isset($params['global']))
		{
			$registry = $core->registry;

			foreach ($params['global'] as $name => $value)
			{
				$registry[$name] = $value;
			}
		}

		if (isset($params['local']))
		{
			$site = $core->working_site;

			foreach ($params['local'] as $name => $value)
			{
				if (is_array($value))
				{
					foreach ($value as $subname => $subvalue)
					{
						$site->metas[$name . '.' . $subname] = $subvalue;
					}

					continue;
				}

				$site->metas[$name] = $value;
			}
		}

		wd_log_done("La configuration a été renregistrée");

		$operation->location = $_SERVER['REQUEST_URI'];

		return true;
	}

	/**
	 * Use this operation to obtain a block from the module.
	 */

	const OPERATION_GET_BLOCK = 'getBlock'; // TODO-20101125: rename as BLOCK

	protected function controls_for_operation_getBlock(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_AUTHENTICATION => true,
			self::CONTROL_VALIDATOR => true
		);
	}

	protected function operation_getBlock(WdOperation $operation)
	{
		global $document;

		// TODO: add block access restriction

		$document = new WdPDocument();

		$name = $operation->params['name'];

		$block = $this->getBlock($name, $operation->params);

		if (is_array($block))
		{
			$block = (string) $block['element'];
		}

		$operation->terminus = true;
		$operation->response->assets = $document->get_assets();

		return (string) $block;
	}

	protected function operation_queryOperation(WdOperation $operation)
	{
		$name = $operation->params['operation'];
		$callback = 'operation_query_' . $name; // TODO-20101125: rename as 'query_operation_<operation>'

		if (!method_exists($this, $callback))
		{
			wd_log_error('The operation %operation is not queriable for the %module module', array('%operation' => $name, '%module', $this->id));

			return;
		}

		$operation->terminus = true;

		$rc =
		$sbase = 'operation.';
		$mbase = $name . '.' . $sbase;
		$lbase = $this->flat_id . '.' . $mbase;

		$t_options = array('scope' => array($this->flat_id, $name, 'operation'));

		$entries = isset($operation->params['entries']) ? $operation->params['entries'] : array();
		$count = count($entries);

		return $this->$callback($operation) + array
		(
			'title' => t('title', array(), $t_options),
			'message' => t('confirm', array(':count' => $count), $t_options),
			'confirm' => array
			(
				t('cancel', array(), $t_options),
				t('continue', array(), $t_options)
			)
		);
	}

	protected function operation_query_delete(WdOperation $operation)
	{
		$entries = $operation->params['entries'];
		$count = count($entries);

		return array
		(
			'params' => array
			(
				'entries' => $entries
			)
		);
	}

	protected function validate_operation_save(WdOperation $operation)
	{
		return true;
	}

	protected function validate_operation_getBlock(WdOperation $operation)
	{
		$params = &$operation->params;

		if (empty($params['name']))
		{
			wd_log_error('Missing block name');

			return false;
		}

		return true;
	}

	public function getBlock($name)
	{
		global $core;

		$args = func_get_args();


		if ($name == 'edit' && !$core->user->is_guest())
		{
			if (!empty($args[1]))
			{
				$key = $args[1];

				$locked = $this->lock_entry($key, $lock);

				if (!$locked)
				{
					global $core;

					$luser = $core->models['user.users'][$lock['uid']];
					$url = $_SERVER['REQUEST_URI'];

					$time = round((strtotime($lock['until']) - time()) / 60);
					$message = $time ? "Le verrou devrait disparaitre dans $time minutes." : "Le verrou devrait disparaitre dans moins d'une minutes.";

					return <<<EOT
<div class="group">
<h3>Édition impossible</h3>
<p>Impossible d'éditer l'entrée parce qu'elle est en cours d'édition par <em>$luser->name</em> <span class="small">($luser->username)</span>.</p>
<form method="get">
<input type="hidden" name="retry" value="1" />
<button class="continue">Réessayer</button> <span class="small light">$message</span>
</form>
</div>
EOT;
				}
			}
		}



		switch ($name)
		{
			case 'manage':
			{
				$permission = $core->user->has_permission(self::PERMISSION_ACCESS, $this);

				if (!$permission)
				{
					#
					# The user don't have the permission to acces this block, we redirect him to
					# the dashboard.
					#

					throw new WdHTTPException("You don't have permission to access the block type %name.", array('%name' => $name), 403);
				}
			}
			break;

			case 'edit':
			{
				global $document;

				$document->css->add('public/css/edit.css');
				$document->js->add('public/js/edit.js');

				$key = null;
				$permission = $core->user->has_permission(self::PERMISSION_CREATE, $this);
				$entry = null;
				$properties = array();

//				echo "has permission: $permission<br />";

				if (isset($args[1]))
				{
					$key = $args[1];

					$entry = $this->model[$key];

					#
					# check user ownership
					#

					if (isset($entry->uid))
					{
						// TODO-20091110: changed from hasPermission to hasOwnership, maybe I should rename the $permission
						// variable to a $ownership one ??

						$permission = $core->user->has_ownership($this, $entry);

//						echo "has ownrship: $permission<br />";
					}
				}

				if (!$key && !$permission)
				{
					throw new WdHTTPException("You don't have permission to create entries in the %id module.", array('%id' => $this->id), 403);
				}

				#
				# edit menu
				#

				if ($entry)
				{
					#
					# is the working site the good one ?
					#

					if (!empty($entry->siteid) && $entry->siteid != $core->working_site_id)
					{
						$core->change_working_site($entry->siteid);
					}

					$items = array();

					if ($this instanceof system_nodes_WdModule && $entry->url[0] != '#')
					{
						$items[] = '<a href="' . $entry->url . '">Voir</a>';
					}

					if ($items)
					{
						$items = '<li>' . implode('</li><li>', $items) . '</li>';

						$menu = <<<EOT
<div class="edit-actions">
	<ul class="items">
		$items
		<!--li><a class="danger" href="/api/$this->id/$key/delete">Supprimer</a></li-->
	</ul>
</div>
EOT;
						$document->addToBlock($menu, 'menu-options');
					}
				}








				$nulls = array();

				#
				# all values missing from the schema are defined as null
				#

				$schema = $this->model->get_extended_schema();

				if ($schema)
				{
					$nulls = array_fill_keys(array_keys($schema['fields']), null);
				}

				$properties = array_merge($nulls, (array) $entry, $_POST);

				#
				# convert arguments [$name, $id, ...] to [$name, $properties, $permission, ...]
				#

				array_shift($args);
				array_shift($args);

				array_unshift($args, $name, $properties, $permission);

				#
				# get save mode used for this module
				#

				global $core;

				$mode = isset($core->session->wdpmodule['save_mode'][$this->id]) ? $core->session->wdpmodule['save_mode'][$this->id] : self::OPERATION_SAVE_MODE_LIST;

				$tags = wd_array_merge_recursive
				(
					array
					(
						WdForm::T_VALUES => &$properties,
						WdForm::T_DISABLED => !$permission,
						WdForm::T_HIDDENS => array
						(
							WdOperation::DESTINATION => $this->id,
							WdOperation::NAME => self::OPERATION_SAVE,
							WdOperation::KEY => $key
						),

						WdElement::T_GROUPS => array
						(
							'primary' => array
							(
								'title' => 'Général',
								'class' => 'form-section flat'
							),

							'admin' => array
							(
								'title' => 'Administration',
								'class' => 'form-section flat',
								'weight' => 900
							),

							'save' => array
							(
								'weight' => 1000,
								'no-panels' => true
							)
						),

						// TODO-20091228: create an element for this lovely submit-save-mode-combo

						WdElement::T_CHILDREN => $permission ? array
						(
							self::OPERATION_SAVE_MODE => new WdElement
							(
								WdElement::E_RADIO_GROUP, array
								(
									WdElement::T_GROUP => 'save',
									WdElement::T_OPTIONS => array
									(
										self::OPERATION_SAVE_MODE_LIST => 'Enregistrer et aller à la liste',
										self::OPERATION_SAVE_MODE_CONTINUE => 'Enregistrer et continuer l\'édition',
										self::OPERATION_SAVE_MODE_NEW => 'Enregistrer et éditer une nouvelle entrée'
									),

									'value' => $mode,
									'class' => 'list save-mode'
								)
							),

							'#submit' => new WdElement
							(
								WdElement::E_SUBMIT, array
								(
									WdElement::T_GROUP => 'save',
									WdElement::T_INNER_HTML => 'Enregistrer',
									'class' => 'save'
								)
							)
						) : array(),

						'id' => 'editor',
						'action' => '',
						'class' => 'group edit',
						'name' => (string) $this
					),

					call_user_func_array((PHP_MAJOR_VERSION > 5 || (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 2)) ? 'parent::' . __FUNCTION__ : array($this, 'parent::' . __FUNCTION__), $args)
				);

				#
				# alterators
				#

				// FIXME: permission won't get updated !!

				WdEvent::fire
				(
					'alter.block.edit', array
					(
						'target' => $this,
						'tags' => &$tags,
						'key' => $key,
						'entry' => $entry,
						'properties' => &$properties,
						'permission' => &$permission,
						'module' => $this
					)
				);

				#
				#
				#

				$form = new WdSectionedForm($tags);

				$form->save();

				return $form;
			}
			break;

			case 'config':
			{
				return $this->handle_block_config();
			}
			break;
		}

		return call_user_func_array((PHP_MAJOR_VERSION > 5 || (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 2)) ? 'parent::' . __FUNCTION__ : array($this, 'parent::' . __FUNCTION__), $args);
	}



	protected function handle_block_config()
	{
		global $core, $document;

		if (!$core->user->has_permission(self::PERMISSION_ADMINISTER, $this))
		{
			throw new WdHTTPException("You don't have permission to administer the %id module.", array('%id' => $this->id), 403);
		}

		#
		# extends document
		#

		$document->css->add('public/css/edit.css');

		$tags = wd_array_merge_recursive
		(
			array
			(
				WdForm::T_HIDDENS => array
				(
					WdOperation::DESTINATION => $this->id,
					WdOperation::NAME => self::OPERATION_CONFIG
				),

				WdForm::T_VALUES => array
				(
				),

				WdElement::T_GROUPS => array
				(
					'primary' => array
					(
						'title' => 'Général',
						'class' => 'form-section flat'
					),

					'save' => array
					(
						'weight' => 1000,
						'no-panels' => true
					)
				),

				WdElement::T_CHILDREN => array
				(
					new WdElement
					(
						WdElement::E_SUBMIT, array
						(
							WdElement::T_GROUP => 'save',
							WdElement::T_INNER_HTML => 'Enregistrer',
							'class' => 'save'
						)
					)
				),

				'class' => 'group config',
				'name' => (string) $this
			),

			$this->block_config($this->flat_id)

			//call_user_func_array((PHP_MAJOR_VERSION > 5 || (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 2)) ? 'parent::' . __FUNCTION__ : array($this, 'parent::' . __FUNCTION__), $args)
		);

		WdEvent::fire
		(
			'alter.block.config', array
			(
				'tags' => &$tags,
				'target' => $this,
				'module' => $this // DIRTY:COMPAT
			)
		);

		$form = new WdSectionedForm($tags);

		$registry = $core->registry;
		$local = $core->working_site->metas;
		$elements = $form->get_named_elements();
		$values = array();

		foreach ($elements as $name => $element)
		{
			$dotted_name = strtr($name, array('[' => '.', ']' => ''));

//			wd_log("element: $name");

			$value = null;

			if (substr($dotted_name, 0, 6) == 'local.')
			{
				$value = $local[substr($dotted_name, 6)];
			}
			else if (substr($dotted_name, 0, 7) == 'global.')
			{
				$value = $registry[substr($dotted_name, 7)];
			}
			else
			{
				// COMPAT

				$value = $registry[$dotted_name];
			}

			if ($value === null)
			{
//				wd_log("$dotted_name := <em>null</em>");

				continue;
			}

//			wd_log("$dotted_name := !value", array('!value' => $value));

			$values[$name] = $value;
		}

		$local = $core->working_site->metas;

		$config = array();

//		wd_log('values: \1', array($values));

		$form->set(WdForm::T_VALUES, $form->get(WdForm::T_VALUES) + $values);

		$form->save();

		return $form;
	}


	protected function block_config()
	{
		return array();
	}

	static public function route_block(WdOperation $operation)
	{
		global $core;

		$operation->name = self::OPERATION_GET_BLOCK;

		return $core->modules[$operation->params['module']]->handle_operation($operation);
	}

	/*
	 * The "lock" operaton is used to obtain an exclusive lock on a node. This is used when a user
	 * is editing a node.
	 */

	const OPERATION_LOCK = 'lock';

	protected function controls_for_operation_lock(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_MAINTAIN,
			self::CONTROL_OWNERSHIP => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_lock(WdOperation $operation)
	{
		return $this->lock_entry((int) $operation->key);
	}

	public function lock_entry($key, &$lock=null)
	{
		global $core;

		$user_id = $core->user_id;

		if (!$user_id)
		{
			throw new WdException('Guest users cannot lock entries');
		}

		if (!$key)
		{
			throw new WdException('There is no key baby');
		}

		#
		# is the node already locked by another user ?
		#

		$until = date('Y-m-d H:i:s', time() + 2 * 60);

		$base = 'admin.locks.' . $this->flat_id . '.' . $key;
		$lock_uid_key = $base . '.uid';
		$lock_until_key = $base . '.until';

		$registry = $core->registry;
		$lock = $registry[$base . '.'];

//		wd_log('all: \1, lock: \2', array($registry['admin.locks.'], $lock));

		if ($registry[$lock_uid_key])
		{
			$now = time();

			// TODO-20100903: too much code, cleanup needed !

			if ($now > strtotime($registry[$lock_uid_key]))
			{
				#
				# there _was_ a lock, but its time has expired, we can claim it.
				#

				$registry[$lock_uid_key] = $user_id;
				$registry[$lock_until_key] = $until;
			}
			else
			{
				if ($registry[$lock_uid_key] != $user_id)
				{
					return false;
				}

				$registry[$lock_until_key] = $until;
			}
		}
		else
		{
			$registry[$lock_uid_key] = $user_id;
			$registry[$lock_until_key] = $until;
		}

		return true;
	}

	/*
	 * The "unlock" operation is used to unlock a node previously locked using the "lock"
	 * operation.
	 */

	const OPERATION_UNLOCK = 'unlock';

	protected function controls_for_operation_unlock(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_MAINTAIN,
			self::CONTROL_OWNERSHIP => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_unlock(WdOperation $operation)
	{
		return $this->unlock_entry((int) $operation->key);
	}

	public function unlock_entry($key)
	{
		global $core;

		$base = "admin.locks.$this->flat_id.$key.";
		$lock_uid_key = $base . 'uid';
		$lock_until_key = $base . 'until';

		$registry = $core->registry;
		$lock_uid = $registry[$lock_uid_key];

		if (!$lock_uid)
		{
			return;
		}

		if ($lock_uid != $core->user_id)
		{
			return false;
		}

		$registry[$lock_uid_key] = null;
		$registry[$lock_until_key] = null;

		return true;
	}

	public function provide_view($name, $patron)
	{
		$query = new WdActiveRecordQuery($this->model);
		$query = $this->provide_view_alter_query($name, $query);

		$callback = __FUNCTION__ . '_' . $name;

		return $this->$callback($query, $patron);
	}

	protected function provide_view_alter_query($name, $query)
	{
		$callback = __FUNCTION__ . '_' . $name;

		if (!method_exists($this, $callback))
		{
			return $query;
		}

		return $this->$callback($query);
	}
}