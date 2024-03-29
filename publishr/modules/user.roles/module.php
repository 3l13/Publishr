<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class user_roles_WdModule extends WdPModule
{
	const OPERATION_PERMISSIONS = 'permissions';

	static public $levels = array
	(
		WdModule::PERMISSION_NONE => 'none',
		WdModule::PERMISSION_ACCESS => 'access',
		WdModule::PERMISSION_CREATE => 'create',
		WdModule::PERMISSION_MAINTAIN => 'maintain',
		WdModule::PERMISSION_MANAGE => 'manage',
		WdModule::PERMISSION_ADMINISTER => 'administer'
	);

	public function install()
	{
		$rc = parent::install();

		if (!$rc)
		{
			return $rc;
		}

		$this->model->save
		(
			array
			(
				Role::ROLE => t('Visitor')
			)
		);

		$this->model->save
		(
			array
			(
				Role::ROLE => t('User')
			)
		);

		return $rc;
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
						WdForm::T_LABEL => '.title',
						WdElement::T_REQUIRED => true
					)
				)
			)
		);
	}

	protected function block_manage()
	{
		global $core, $document;

		$document->css->add('public/css/manage.css', -170);
		$document->css->add('public/manage.css');
		$document->js->add('public/module.js');

		$packages = array();
		$modules = array();

		foreach ($core->modules->descriptors as $m_id => $descriptor)
		{
			if (empty($core->modules[$m_id]))
			{
				continue;
			}

			$name = isset($descriptor[WdModule::T_TITLE]) ? $descriptor[WdModule::T_TITLE] : $m_id;

			if (isset($descriptor[WdModule::T_PERMISSION]))
			{
				if ($descriptor[WdModule::T_PERMISSION] != self::PERMISSION_NONE)
				{
					$name .= ' <em>(';
					$name .= self::$levels[$descriptor[WdModule::T_PERMISSION]];
					$name .= ')</em>';
				}
				else if (empty($descriptor[WdModule::T_PERMISSIONS]))
				{
					continue;
				}
			}

			if (isset($descriptor[WdModule::T_CATEGORY]))
			{
				$package = $descriptor[WdModule::T_CATEGORY];
			}
			else
			{
				list($package) = explode('.', $m_id);
			}

			$package = t($package, array(), array('scope' => array('module_category', 'title'), 'default' => $package));

			$packages[$package][t($name)] = array_merge
			(
				$descriptor, array
				(
					self::T_ID => $m_id
				)
			);
		}

		uksort($packages, 'wd_unaccent_compare_ci');

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

		$roles = $this->model->all;

		//
		// create manager
		//

		$rc = '';

		$rc .= '<form name="roles" action="" method="post" enctype="multipart/form-data">';
		$rc .= '<input type="hidden" name="' . WdOperation::DESTINATION . '" value="' . $this . '" />';

		// table

		$rc .= '<table class="manage group" cellpadding="4" cellspacing="0">';

		//
		// table header
		//

		$span = 1;
		$context = $core->site->path;

		$rc .= '<thead>';
		$rc .= '<tr>';
		$rc .= '<th>&nbsp;</th>';

		foreach ($roles as $role)
		{
			$span++;

			$rc .= '<th><div>';

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
						'href' => $context . '/admin/' . $this . '/' . $role->rid . '/edit',
						'title' => t('Edit entry')
					)
				);
			}

			$rc .= '</div></th>';
		}

		$rc .= '</tr>';
		$rc .= '</thead>';

		if (1)
		{
			$actions_rows = '';

			foreach ($roles as $role)
			{
				$actions_rows .= '<td>';

				if ($role->rid == 1 || $role->rid == 2)
				{
					$actions_rows .= '&nbsp;';
				}
				else
				{
					$actions_rows .= '<a class="button danger small" href="' . $context . '/admin/user.roles/' . $role->rid . '/delete">Supprimer</a>';
				}

				$actions_rows .= '</td>';
			}

			$rc .= <<<EOT
<tfoot>
	<tr class="footer">
		<td>
		<div class="jobs">
			<a class="operation-delete" href="#" rel="op-delete">Delete the selected entries</a>
		</div>
		</td>

		$actions_rows

	</tr>
</tfoot>
EOT;
		}

		$rc .= '<tbody>';

		//
		//
		//


		$role_options = array();

		foreach (self::$levels as $i => $level)
		{
			$role_options[$i] = t('permission.' . $level, array(), array('default' => $level));
		}


		$user_has_access = $core->user->has_permission(self::PERMISSION_ADMINISTER, $this);

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

			uksort($modules, 'wd_unaccent_compare_ci');

			foreach ($modules as $m_name => $m_desc)
			{
				$m_id = $m_desc[self::T_ID];
				$flat_id = strtr($m_id, '.', '_');


				$rc .= '<tr class="admin">';

				$rc .= '<td>';
				$rc .= WdRoute::find('/admin/' . $m_id) ? '<a href="' . $context . '/admin/' . $m_id . '">' . $m_name . '</a>' : $m_name;
				$rc .= '</td>';

				foreach ($roles as $role)
				{
					$rc .= '<td>';

					if (isset($m_desc[WdModule::T_PERMISSION]))
					{
						if ($m_desc[WdModule::T_PERMISSION] != self::PERMISSION_NONE)
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
							$rc .= '&nbsp;';
						}
					}
					else
					{
						if ($user_has_access)
						{
							$options = $role_options;

							if ($m_id != 'all')
							{
								$options = array('inherit' => '') + $options;
							}

							$rc .= new WdElement
							(
								'select', array
								(
									WdElement::T_OPTIONS => $options,

									'name' => 'roles[' . $role->rid . '][' . $m_id . ']',
									'value' => isset($role->levels[$m_id]) ? $role->levels[$m_id] : null
								)
							);
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
				# Permissions
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
					$columns = '';

					foreach ($roles as $role)
					{
						$columns .= '<td>' . new WdElement
						(
							WdElement::E_CHECKBOX, array
							(
								'name' => $user_has_access ? 'roles[' . $role->rid . '][' . $pname . ']' : NULL,
								'checked' => $role->has_permission($pname)
							)
						)
						. '</td>';
					}

					$label = t($pname, array(), array('scope' => array($flat_id, 'permission')));

					$rc .= <<<EOT
<tr class="perm">
	<td><span title="$pname">$label</span></td>
	$columns
</tr>
EOT;
				}
			}
		}

		$rc .= '</tbody>';
		$rc .= '</table>';

		//
		// submit
		//

		if ($user_has_access)
		{
			$rc .= '<div class="group">';

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

			$rc .= '</div>';
		}

		$rc .= '</form>';

		return $rc;
	}
}