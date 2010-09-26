<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

/*

The purpose of this class is to extend WdModule with user's privilege checking (mainly on operations)

The module also adds the following operations :

OPERATION_EDIT
OPERATION_SAVE

*/

class WdPModule extends WdModule
{
	const OPERATION_DOWNLOAD = 'download'; // FIXME-20081223: this should be obsolete, and defined in the resources.files module

	const OPERATION_SAVE_MODE = '#operation-save-mode';
	const OPERATION_SAVE_MODE_CONTINUE = 'continue';
	const OPERATION_SAVE_MODE_LIST = 'list';
	const OPERATION_SAVE_MODE_NEW = 'new';

	const OPERATION_CONFIG = 'config';

	const OPERATION_QUERY_OPERATION = 'queryOperation';
	const OPERATION_GET_BLOCK = 'getBlock';

	protected function getOperationsAccessControls()
	{
		$rc = array
		(
			self::OPERATION_CONFIG => array
			(
				self::CONTROL_PERMISSION => PERMISSION_ADMINISTER,
				self::CONTROL_FORM => true,
				self::CONTROL_VALIDATOR => false // FIXME: false ??
			),

			self::OPERATION_QUERY_OPERATION => array
			(
				self::CONTROL_AUTHENTICATION => true,
				self::CONTROL_VALIDATOR => false
			),

			self::OPERATION_GET_BLOCK => array
			(
				self::CONTROL_AUTHENTICATION => true,
				self::CONTROL_VALIDATOR => true
			)
		)

		+ parent::getOperationsAccessControls();

		return $rc;
	}

	protected function operation_save(WdOperation $operation)
	{
		$rc = parent::operation_save($operation);

		if (!$rc)
		{
			return $rc;
		}

		$params = &$operation->params;

		#
		# choose possible redirection, depending on save mode
		#

		if (isset($params[self::OPERATION_SAVE_MODE]))
		{
			global $app;

			$mode = $params[self::OPERATION_SAVE_MODE];

			$app->session->wdpmodule['save_mode'][$this->id] = $mode;

			#
			# list (default): we are done with the editing and we want to see all of our lovely entries.
			#

			$route = '/' . $this->id;

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

			$operation->location = WdRoute::encode($route);
		}

		return $rc;
	}

	protected function operation_delete(WdOperation $operation)
	{
		#
		# check key
		#

		$key = $operation->params[WdOperation::KEY];

		// TODO: move to validator

		if (empty($key))
		{
			throw new WdException('Key is missing for the delete operation.');
		}

		// TODO: use CONTROL_OWNERSHIP

		#
		# check user's permission
		#

		global $app;

		$permission = $app->user->has_permission(PERMISSION_MAINTAIN, $this);

		if (!$permission)
		{
			throw new WdException('You don\'t have permission to delete entries from %module.', array('%module' => $destination));
		}

		if ($permission == PERMISSION_MAINTAIN)
		{
			$entry = $this->load($key);

			if (!$entry)
			{
				throw new WdException('The entry %key does not exists in %module.', array('%key' => $key, '%module' => $this->id));
			}

			#
			# only an user with administer privilege may delete an entry
			# without ownership
			#

			if (empty($entry->uid))
			{
				throw new WdException('You don\'t have permission to delete entries from %module.', array('%module' => $this->id));
			}

			if ($entry->uid != $app->user->uid)
			{
				throw new WdException('You don\'t have the ownership of the entry %key in %module.', array('%key' => $key, '%module' => $this->id));
			}
		}

		if (!$this->model()->delete($key))
		{
			wd_log_error('Unable to delete the entry %key from %module.', array('%key' => $key, '%module' => $this->id));

			return;
		}

		wd_log_done('The entry %key has been delete from %module.', array('%key' => $key, '%module' => $this->id));

		return $key;
	}

	protected function operation_config(WdOperation $operation)
	{
		global $registry;

		foreach ($operation->params as $key => $value)
		{
			if ($key{0} == '#')
			{
				continue;
			}

			$registry->set($key, $value);
		}

		wd_log_done('@operation.config.done');

		$operation->location = WdRoute::encode('/' . $this->id);

		return true;
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
		$operation->response->assets = $document->getAssets();

		return (string) $block;
	}

	protected function operation_queryOperation(WdOperation $operation)
	{
		$name = $operation->params['operation'];
		$callback = 'operation_query_' . $name;

		if (!method_exists($this, $callback))
		{
			wd_log_error('The operation %operation is not queriable for the %module module', array('%operation' => $name, '%module', $this->id));

			return;
		}

		$operation->terminus = true;

		return $this->$callback($operation);
	}

	protected function operation_query_delete(WdOperation $operation)
	{
		$entries = $operation->params['entries'];
		$count = count($entries);

		return array
		(
			'title' => t('@operation.delete.title'),
			'message' => t($count == 1 ? '@operation.delete.confirm' : '@operation.delete.confirmN', array(':count' => count($entries))),
			'confirm' => array(t('@operation.delete.dont'), t('@operation.delete.do')),
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
		global $app;

		$args = func_get_args();

		switch ($name)
		{
			case 'manage':
			{
				$permission = $app->user->has_permission(PERMISSION_ACCESS, $this);

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
				$permission = $app->user->has_permission(PERMISSION_CREATE, $this);
				$entry = null;
				$properties = array();

				if (isset($args[1]))
				{
					$key = $args[1];

					$entry = $this->model()->load($key);

					#
					# check user ownership
					#

					if (isset($entry->uid))
					{
						// TODO-20091110: changed from hasPermission to hasOwnership, maybe I should rename the $permission
						// variable to a $ownership one ??

						$permission = $app->user->has_ownership($this, $entry);
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
		<!--li><a class="danger" href="/do/$this->id/$key/delete">Supprimer</a></li-->
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

				$schema = $this->model()->getExtendedSchema();

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

				global $app;

				$mode = isset($app->session->wdpmodule['save_mode'][$this->id]) ? $app->session->wdpmodule['save_mode'][$this->id] : self::OPERATION_SAVE_MODE_LIST;

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
								'title' => 'Enregistrer',
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
				global $app, $document;

				if (!$app->user->has_permission(PERMISSION_ADMINISTER, $this))
				{
					throw new WdHTTPException("You don't have permission to administer the %id module.", array('%id' => $this->id), 403);
				}

				#
				# extends document
				#

				$document->css->add('public/css/edit.css');

				array_shift($args);
				array_unshift($args, 'config', strtr($this->id, '.', '_'));

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

					call_user_func_array((PHP_MAJOR_VERSION > 5 || (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 2)) ? 'parent::' . __FUNCTION__ : array($this, 'parent::' . __FUNCTION__), $args)
				);

				#
				# alterators
				#

				WdEvent::fire
				(
					'alter.block.config', array
					(
						'tags' => &$tags,
						'module' => $this
					)
				);

				#
				# load config
				#

				global $registry;

				$config = array();

				foreach (array_keys($tags[WdElement::T_CHILDREN]) as $name)
				{
					if (is_numeric($name))
					{
						continue;
					}

					$config_name = strtr
					(
						$name, array
						(
							'[' => '.',
							']' => ''
						)
					);

					$value = $registry->get($config_name);

					if ($value === null)
					{
						$value = $registry->get($config_name . '.');

						if (!count($value))
						{
							$value = null;
						}

						//wd_log('single: \1 :: \2', array($config_name, $value));
					}

					$config[$name] = $value;

					//wd_log('name: \1:: \2', array($config_name, $value));
				}

				$tags[WdForm::T_VALUES] += $config;

				#
				# create form
				#

				$form = new WdSectionedForm($tags);

				$form->save();

				return $form;
			}
			break;
		}

		return call_user_func_array((PHP_MAJOR_VERSION > 5 || (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 2)) ? 'parent::' . __FUNCTION__ : array($this, 'parent::' . __FUNCTION__), $args);
	}

	protected function block_config($base)
	{
		return array();
	}
}