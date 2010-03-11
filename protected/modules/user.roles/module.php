<?php

class user_roles_WdModule extends WdPModule
{
	static public $levels = array
	(
		PERMISSION_NONE => 'none',
		PERMISSION_ACCESS => 'access',
		PERMISSION_CREATE => 'create',
		PERMISSION_MAINTAIN => 'maintain',
		PERMISSION_MANAGE => 'manage',
		PERMISSION_ADMINISTER => 'administer'
	);

	/*
	**

	MANAGEMENT

	**
	*/

	public function install()
	{
		$rc = parent::install();

		if (!$rc)
		{
			return $rc;
		}

		return $this->model()->save
		(
			array
			(
				Role::ROLE => t('Visitor')
			),

			0
		);
	}

	/*
	**

	OPERATIONS

	**
	*/

	const OPERATION_PERMISSIONS = 'permissions';

	protected function validate_operation_permissions($params)
	{
		global $user;

		if (!$user->hasPermission(PERMISSION_ADMINISTER, $this))
		{
			wd_log_error('You don\'t have permission to administer %module module', array($this->id));

			return false;
		}

		return true;
	}

	protected function operation_permissions(WdOperation $operation)
	{
		$params = &$operation->params;

		foreach ($params['roles'] as $rid => $perms)
		{
			$role = $this->model()->load($rid);

			if (!$role)
			{
				continue;
			}

			$p = array();

			foreach ($perms as $perm => $name)
			{
				if ($name == 'inherit')
				{
					continue;
				}

				if ($name == 'on')
				{
					global $core;

					if (isset($core->descriptors[$perm]))
					{
						#
						# the module defines his permission level
						#

						$p[$perm] = $core->descriptors[$perm][WdModule::T_PERMISSION];

						continue;
					}
					else
					{
						#
						# this is a special permission
						#

						$p[$perm] = true;

						continue;
					}
				}

				$p[$perm] = user_roles_WdActiveRecord::$permission_levels[$name];
			}

			//$role->perms = serialize($p);
			$role->perms = json_encode($p);

			$this->model()->save((array) $role, $role->rid);
		}

		return true;
	}


	protected function block_edit($properties, $permission)
	{
		return array
		(
			WdElement::T_CHILDREN => array
			(
				Role::ROLE => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Title',
						WdElement::T_MANDATORY => true
					)
				)
			)
		);
	}

	public function block_manage()
	{
		global $core;
		global $user;
		global $document;

		$document->addStyleSheet('public/css/manage.css', 170);
		$document->addStyleSheet('public/manage.css');
		$document->addJavascript('public/module.js');

		//
		//
		//

//		wd_log('packages: \1', $core->packages);

		$packages = array();
		$modules = array();

		foreach ($core->descriptors as $m_id => $descriptor)
		{
			if (!$core->hasModule($m_id))
			{
				continue;
			}

			$name = isset($descriptor[WdModule::T_TITLE]) ? $descriptor[WdModule::T_TITLE] : $m_id;

			if (isset($descriptor[WdModule::T_PERMISSION]))
			{
				if ($descriptor[WdModule::T_PERMISSION] == PERMISSION_NONE)
				{
					continue;
				}

				$name .= ' <em>(';
				$name .= self::$levels[$descriptor[WdModule::T_PERMISSION]];
				$name .= ')</em>';
			}

			list($package) = explode('.', $m_id);

			$packages[$package][t($name)] = array_merge
			(
				$descriptor, array
				(
					self::T_ID => $m_id
				)
			);
		}

		ksort($packages);

		$packages = array_merge
		(
			array
			(
				t('General') => array
				(
					t('All') => array(self::T_ID => 'all')
				)
			),

			$packages
		);

		#
		# load roles
		#

		$roles = $this->model()->loadAll()->fetchAll();

		//
		// create resume
		//

		$rc = '';

		$rc .= '<form name="roles" action=""';
		$rc .= ' method="post" enctype="multipart/form-data">';
		$rc .= '<input type="hidden" name="' . WdOperation::DESTINATION . '" value="' . $this . '" />';

		// table

		$rc .= '<table class="manage group" cellpadding="4" cellspacing="0">';

		//
		// table header
		//

		$span = 1;

		$rc .= '<thead>';
		$rc .= '<tr>';
		$rc .= '<th>' . t('Modules') . '</th>';

		foreach ($roles as $role)
		{
			$span++;

			$rc .= '<th>';

			if ($role->rid == 0)
			{
				$rc .= $role->title;
			}
			else
			{
				$rc .= new WdElement
				(
					'a', array
					(
						WdElement::T_INNER_HTML => $role->role,
						'href' => WdRoute::encode('/' . $this . '/' . $role->rid . '/edit'),
						'title' => t('Edit entry')
					)
				);
			}

			$rc .= '</th>';
		}

		$rc .= '</tr>';
		$rc .= '</thead>';

		$rc .= '<tbody>';

		//
		//
		//

		$user_has_access = $user->hasPermission('administer', $this);

		foreach ($packages as $p_name => $modules)
		{
			$rc .= '<tr class="module">';
			$rc .= '<td colspan="' . $span . '">';
			$rc .= $p_name;
			$rc .= '</td>';
			$rc .= '</tr>';

			$n = 0;

			//
			// admins
			//

			foreach ($modules as $m_name => $m_desc)
			{
				$m_id = $m_desc[self::T_ID];

				if ($n++ % 2)
				{
					$rc .= '<tr class="admin even">';
				}
				else
				{
					$rc .= '<tr class="admin">';
				}

				$rc .= '<td>';
				$rc .= $m_name;
				$rc .= '</td>';

				foreach ($roles as $role)
				{
					$rc .= '<td>';

					if (isset($m_desc[WdModule::T_PERMISSION]))
					{
						$level = $m_desc[WdModule::T_PERMISSION];

						$rc .= new WdElement
						(
							WdElement::E_CHECKBOX, array
							(
								'name' => 'roles[' . $role->rid . '][' . $m_id . ']',
								'checked' => isset($role->levels[$m_id]) && ($role->levels[$m_id] = $level)
							)
						);
					}
					else
					{
						if ($user_has_access)
						{
							$rc .= '<select';
							$rc .= ' name="roles[' . $role->rid . '][' . $m_id . ']"';
							$rc .= '>';

							if ($m_id != 'all')
							{
								$rc .= '<option value="inherit">&nbsp;</option>';
							}

							foreach (self::$levels as $level => $lname)
							{
								$rc .= new WdElement
								(
									'option', array
									(
										WdElement::T_INNER_HTML => $lname ? $lname : '&nbsp;',
										'value' => $lname,
										'selected' => isset($role->levels[$m_id]) && ($role->levels[$m_id] == $level)
									)
								);
							}

							$rc .= '</select>';
						}
						else
						{
							$level = isset($role->levels[$m_id]) ? $role->levels[$m_id] : null;

							if ($level)
							{
								$rc .= self::$levels[$level];
							}
							else
							{
								$rc .= '&nbsp;';
							}
						}
					}

					$rc .= '</td>';
				}

				$rc .= '</tr>';

				#
				# permissions
				#
				# e.g. "modify own profile"
				#

				if (empty($m_desc[WdModule::T_PERMISSIONS]))
				{
					continue;
				}

				$perms = $m_desc[WdModule::T_PERMISSIONS];

				foreach ($perms as $pname)
				{
					if ($n++ % 2)
					{
						$rc .= '<tr class="perm even">';
					}
					else
					{
						$rc .= '<tr class="perm">';
					}

					$rc .= '<td>';
					$rc .= $pname;
					$rc .= '</td>';

					foreach ($roles as $role)
					{
						$rc .= '<td>';

						$rc .= new WdElement
						(
							WdElement::E_CHECKBOX, array
							(
								'name' => $user_has_access ? 'roles[' . $role->rid . '][' . $pname . ']' : NULL,
								'checked' => $role->hasPermission($pname)
							)
						);

						$rc .= '</td>';
					}

					$rc .= '</tr>';
				}
			}
		}

		//
		// footer
		//

		$rc .= '<tr class="footer">';

		// jobs

		$rc .= '<td>';
		$rc .= '<div class="jobs">';

		$rc .= '<a class="operation-delete" href="#" rel="op-delete">';
		$rc .= t('Delete the selected entries');
		$rc .= '</a>';

		$rc .= '</div>';
		$rc .= '</td>';

		// selection

		foreach ($roles as $role)
		{
			$rc .= '<td>';

			if ($role->rid == 1)
			{
				$rc .= '&nbsp;';
			}
			else
			{
				$rc .= new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						'checked' => WdOperation::KEY . '[' . $role->rid . ']'
					)
				);
			}

			$rc .= '</td>';
		}

		$rc .= '</tr>';
		$rc .= '</tbody>';

		//

		$rc .= '</table>';

		//
		// submit
		//

		if ($user_has_access)
		{
			$rc .= '<br />';

			$rc .= new WdElement
			(
				'button', array
				(
					'class' => 'save',
					'type' => 'submit',
					'value' => self::OPERATION_PERMISSIONS,
					WdElement::T_INNER_HTML => t('Save permissions')
				)
			);

			$rc .= new WdElement
			(
				'button', array
				(
					'class' => 'delete',
					'type' => 'submit',
					'title' => t('Delete the selected entries'),
					'value' => self::OPERATION_DELETE,
					WdElement::T_INNER_HTML => self::OPERATION_DELETE
				)
			);
		}

		$rc .= '</form>';

		return array
		(
			'element' => $rc
		);
	}
}