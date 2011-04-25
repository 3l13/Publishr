<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Extends the WdModule class with the following features:
 *
 * - Special handling for the 'edit', 'new' and 'configure' blocks.
 * - Inter-users edit lock on records.
 */
class WdPModule extends WdModule
{
	const OPERATION_CONFIG = 'config';

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
				$permission = $core->user->has_permission(WdModule::PERMISSION_ACCESS, $this);

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
				$permission = $core->user->has_permission(WdModule::PERMISSION_CREATE, $this);
				$entry = null;
				$properties = array();
				$url = null;

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

					if (!($entry instanceof site_sites_WdActiveRecord) && !empty($entry->siteid) && $entry->siteid != $core->site_id)
					{
						header("Location: {$entry->site->url}/admin/{$entry->constructor}/{$entry->nid}/edit");

						exit;
					}

					$items = array();

					if ($key && $core->user->has_permission(self::PERMISSION_MANAGE, $this))
					{
						$items[] = '<a href="/admin/' . $this->id . '/' . $key . '/delete">' . t('label.delete') . '</a>';
					}

					if ($this instanceof system_nodes_WdModule && $entry->url[0] != '#')
					{
						$url = $entry->url;

						$items[] = '<a href="' . $url . '">' . t('label.display') . '</a>';
					}

					if ($items)
					{
						$items = '<li>' . implode('</li><li>', $items) . '</li>';
						$menu = '<div class="edit-actions"><ul class="items">' . $items . '</ul></div>';

						$document->addToBlock($menu, 'menu-options');
					}
				}




				WdI18n::push_scope(array($this->flat_id, $name));



				$nulls = array();

				#
				# all values missing from the schema are defined as null
				#

				$schema = $this->model->extended_schema;

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

				$mode = isset($core->session->wdpmodule[publishr_save_WdOperation::MODE][$this->id]) ? $core->session->wdpmodule[publishr_save_WdOperation::MODE][$this->id] : publishr_save_WdOperation::MODE_LIST;

				$save_mode_options = array
				(
					publishr_save_WdOperation::MODE_LIST => '.save_mode_list',
					publishr_save_WdOperation::MODE_CONTINUE => '.save_mode_continue',
					publishr_save_WdOperation::MODE_NEW => '.save_mode_new'
				);

				if ($url)
				{
					$save_mode_options[system_nodes__save_WdOperation::MODE_DISPLAY] = '.save_mode_display';
				}

				$tags = wd_array_merge_recursive
				(
					array
					(
						WdForm::T_VALUES => &$properties,
						WdForm::T_DISABLED => !$permission,
						WdForm::T_HIDDENS => array
						(
							WdOperation::DESTINATION => $this->id,
							WdOperation::NAME => 'save',
							WdOperation::KEY => $key
						),

						WdElement::T_GROUPS => array
						(
							'primary' => array
							(
								'title' => '.primary',
								'class' => 'form-section flat'
							),

							'admin' => array
							(
								'title' => '.admin',
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
							publishr_save_WdOperation::MODE => new WdElement
							(
								WdElement::E_RADIO_GROUP, array
								(
									WdElement::T_GROUP => 'save',
									WdElement::T_OPTIONS => $save_mode_options,

									'value' => $mode,
									'class' => 'list save-mode'
								)
							),

							'#submit' => new WdElement
							(
								WdElement::E_SUBMIT, array
								(
									WdElement::T_GROUP => 'save',
									WdElement::T_INNER_HTML => t('save', array(), array('scope' => array('button', 'label'))),
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

				$form = (string) $form;

				WdI18n::pop_scope();

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
		global $core;

		if (!$core->user->has_permission(WdModule::PERMISSION_ADMINISTER, $this))
		{
			throw new WdHTTPException("You don't have permission to administer the %id module.", array('%id' => $this->id), 403);
		}

		WdI18n::push_scope(array($this->flat_id, 'config'));

		$core->document->css->add('public/css/edit.css');

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
						'title' => '.primary',
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
							WdElement::T_INNER_HTML => t('save', array(), array('scope' => array('button', 'label'))),

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
		$local = $core->site->metas;
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

		$config = array();

		$form->set(WdForm::T_VALUES, $form->get(WdForm::T_VALUES) + $values);

		$form->save();

		$form = (string) $form;

		WdI18n::pop_scope();

		return $form;
	}

	protected function block_delete($key)
	{
		try
		{
			$record = $this->model[$key];
		}
		catch (Exception $e)
		{
			return '<div class="group">' . t('Unknown record id: %key', array('%key' => $key)) . '</div>';
		}

		$form = (string) new WdForm
		(
			array
			(
				WdForm::T_HIDDENS => array
				(
					WdOperation::DESTINATION => $this,
					WdOperation::NAME => self::OPERATION_DELETE,
					WdOperation::KEY => $key,

					'#location' => "/admin/{$this->id}"
				),

				WdElement::T_CHILDREN => array
				(
					new WdElement
					(
						WdElement::E_SUBMIT, array
						(
							WdElement::T_INNER_HTML => t('label.delete'),

							'class' => 'danger'
						)
					)
				)
			)
		);

		return <<<EOT
<div class="group">
<h3>Delete a record</h3>
<p>Are you sure you want to delete this record?</p>
$form
</div>
EOT;
	}

	protected function block_config()
	{
		return array();
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