<?php

/* ***** BEGIN LICENSE BLOCK *****
 *
 * This file is part of WdPublisher:
 *
 *     * http://www.weirdog.com
 *     * http://www.wdpublisher.com
 *
 * Software License Agreement (New BSD License)
 *
* Copyright (c) 2007-2010, Olivier Laviale
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *     * Redistributions in binary form must reproduce the above copyright notice,
 *       this list of conditions and the following disclaimer in the documentation
 *       and/or other materials provided with the distribution.
 *
 *     * Neither the name of Olivier Laviale nor the names of its
 *       contributors may be used to endorse or promote products derived from this
 *       software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * ***** END LICENSE BLOCK ***** */

/*

The purpose of this class is to extend WdModule with user's privilege checking (mainly on operations)

The module also adds the following operations :

OPERATION_EDIT
OPERATION_SAVE

*/

class WdPModule extends WdModule
{
	const OPERATION_EDIT = 'edit'; // FIXME-20081223: this should be obsolete
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
				self::CONTROL_AUTHENTICATED => true,
				self::CONTROL_VALIDATOR => false
			),

			self::OPERATION_GET_BLOCK => array
			(
				self::CONTROL_AUTHENTICATED => true,
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
			$mode = $params[self::OPERATION_SAVE_MODE];

			$_SESSION[$this->id . '.' . self::OPERATION_SAVE_MODE] = $mode;

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

		global $user;

		$permission = $user->hasPermission(PERMISSION_MAINTAIN, $this);

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

			if ($entry->uid != $user->uid)
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

	// TODO: split this into AUTHENTICATED, PERMISSION and OWNERSHIP

	protected function validate_unit_user(WdOperation $operation)
	{
		global $user;

		$permission = $user->hasPermission(PERMISSION_CREATE, $this);

		if (!$permission)
		{
			wd_log_error('You don\'t have permission to save entries in %module.', array('%module' => $this->id));

			return false;
		}

		#
		#
		#

		$key = isset($params[WdOperation::KEY]) ? $params[WdOperation::KEY] : null;

		if ($key && ($permission < PERMISSION_ADMINISTER))
		{
			// FIXME-20090117: we can now use schema to look for 'uid', and only load this property instead of the whole object

			$entry = $this->model()->load($key);

			if (!$entry)
			{
				wd_log_error('The entry %key does not exists in %module.', array('%key' => $key, '%module' => $this->id));

				return false;
			}

			#
			# only user with administer privileges may modify entries with no ownership
			#

			if (empty($entry->uid))
			{
				wd_log_error('You don\'t have permission to save entries in %module.', array('%module' => $this->id));

				return false;
			}

			if ($user->uid != $entry->uid)
			{
				wd_log_error
				(
					'You don\'t have the ownership of the entry %id in %module.', array
					(
						'%id' => $key,
						'%module' => $this->id
					)
				);

				return false;
			}
		}

		return true;
	}

	/*
	**

	BLOCKS

	**
	*/

	public function getBlock($name)
	{
		$args = func_get_args();

		switch ($name)
		{
			case 'manage':
			{
				global $user;

				$permission = $user->hasPermission(PERMISSION_ACCESS, $this);

				if (!$permission)
				{
					#
					# The user don't have the permission to acces this block, we redirect him to
					# the dashboard.
					#

					header('Location: ' . WDPUBLISHER_URL);

					exit;
				}
			}
			break;

			case 'edit':
			{
				/*
				 * TODO: implement control for blocks in the same fashions as for operations
				 *
				global $user;

				$permission = $user->hasPermission(PERMISSION_ACCESS, $this);

				if (!$permission)
				{
					#
					# The user don't have the permission to acces this block, we redirect him to
					# the dashboard.
					#

					header('Location: ' . WDPUBLISHER_URL);

					exit;
				}
				*/







				global $document;

				$document->addStyleSheet('public/css/edit.css');
				$document->addJavascript('public/js/edit.js');

				global $user;

				$key = null;
				$permission = $user->hasPermission(PERMISSION_CREATE, $this);
				$properties = array();

				if (isset($args[1]))
				{
					$key = $args[1];

					$properties = $this->model()->load($key);

					#
					# check user ownership
					#

					if (isset($properties->uid))
					{
						// TODO-20091110: changed from hasPermission to hasOwnership, maybe I should rename the $permission
						// variable to a $ownership one ??

						$permission = $user->hasOwnership($this, $properties);
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

				$properties = array_merge($nulls, (array) $properties, $_POST);

				#
				# convert arguments [$name, $id, ...] to [$name, $properties, $permission, ...]
				#

				array_shift($args);
				array_shift($args);

				array_unshift($args, $name, $properties, $permission);

				#
				# get save mode used for this module
				#

				$mode_key = $this->id . '.' . self::OPERATION_SAVE_MODE;
				$mode = isset($_SESSION[$mode_key]) ? $_SESSION[$mode_key] : self::OPERATION_SAVE_MODE_LIST;

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

					call_user_func_array(array($this, 'parent::' . __FUNCTION__), $args)
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
				global $user, $document;

				if (!$user->hasPermission(PERMISSION_ADMINISTER, $this))
				{
					return '<p class="group">Qu\'est-ce que vous faites là ?</p>';
				}

				#
				# extends document
				#

				$document->addStylesheet('public/css/edit.css');

				array_shift($args);
				array_unshift($args, 'config', wd_camelCase($this->id, '.'));

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
								//'title' => 'Enregistrer',
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

						'class' => 'group edit config',
						'name' => (string) $this
					),

					call_user_func_array(array($this, 'parent::' . __FUNCTION__), $args)
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

		return call_user_func_array(array($this, 'parent::' . __FUNCTION__), $args);
	}

	protected function block_config($base)
	{
		return array();
	}
}