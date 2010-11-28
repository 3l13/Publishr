<?php

class system_modules_WdModule extends WdPModule
{
	const OPERATION_INSTALL = 'install';

	/*
	**

	BLOCKS

	**
	*/

	const MANAGE_MODE = '#manage-mode';
	const MANAGE_MODE_INSTALLER = 'installer';

	protected function block_manage(array $options=array())
	{
		$is_installer_mode = isset($options[self::MANAGE_MODE])	&& $options[self::MANAGE_MODE] == self::MANAGE_MODE_INSTALLER;

		#
		#
		#

		if (!$is_installer_mode)
		{
			global $core;

			if (!$core->user->is_admin())
			{
				return;
			}
		}

		$form = $this->form_manage($options);

		if (!$is_installer_mode)
		{
			$form->setHidden(WdOperation::NAME, self::OPERATION_DEACTIVATE);
			$form->setHidden(WdOperation::DESTINATION, $this);
		}

		/*

		$rc = <<<EOT

<div class="group">
<div class="element-description">
<p>Epsum factorial non deposit quid pro quo hic escorol. Olypian quarrels et gorilla congolium sic ad nauseum. Souvlaki ignitus carborundum e pluribus unum. Defacto lingo est igpay atinlay. Marquee selectus non provisio incongruous feline nolo contendre. Gratuitous octopus niacin.</p>
</div>
</div>

EOT

		. $form;

		return $rc;
		*/

		return $form;
	}

	protected function form_manage(array $options=array())
	{
		$is_installer_mode = isset($options[self::MANAGE_MODE]) && $options[self::MANAGE_MODE] == self::MANAGE_MODE_INSTALLER;

		#
		#
		#

		global $document;

		$document->css->add('public/css/manage.css');
		$document->css->add('public/manage.css', 10);
		$document->js->add('public/module.js');

		#
		# read and sort packages and modules
		#

		global $core;


		$packages = array();
		$modules = array();

		foreach ($core->descriptors as $m_id => $descriptor)
		{
			if (isset($descriptor[WdModule::T_DISABLED]))
			{
				continue;
			}

			if (isset($descriptor[WdModule::T_CATEGORY]))
			{
				$category = $descriptor[WdModule::T_CATEGORY];
			}
			else
			{
				list($category) = explode('.', $m_id);
			}

			$category = t($category, array(), array('scope' => 'system.modules.categories', 'default' => $category));
			$title = t(isset($descriptor[WdModule::T_TITLE]) ? $descriptor[WdModule::T_TITLE] : $m_id);

			$packages[$category][$title] = array_merge
			(
				$descriptor, array
				(
					self::T_ID => $m_id
				)
			);
		}

		uksort($packages, 'wd_unaccent_compare_ci');

		$categories = $packages;

		$mandatories = $core->getModuleIdsByProperty(WdModule::T_REQUIRED);

		#
		#
		#

		$contents  = '<table class="manage" cellpadding="4" cellspacing="0">';
		$contents .= '<thead>';
		$contents .= '<tr>';
		$contents .= '<th colspan="2">&nbsp;</th>';
		$contents .= '<th>' . t('Author') . '</th>';
		$contents .= '<th>' . t('Description') . '</th>';

		if (!$is_installer_mode)
		{
			$contents .= '<th>' . t('Installed') . '</th>';
		}

		$contents .= '</tr>';
		$contents .= '</thead>';

		$contents .= '<tbody>';

		$span = $is_installer_mode ? 4 : 5;

		foreach ($packages as $p_name => $descriptors)
		{
			$sub = null;
			$i = 0;

			foreach ($descriptors as $title => $descriptor)
			{
				$m_id = $descriptor[WdModule::T_ID];
				$is_required = isset($mandatories[$m_id]);

				if (isset($descriptor[WdModule::T_DISABLED]))
				{
					continue;
				}

				$m_desc = $descriptor;

				#
				#
				#

				if ($i++ % 2)
				{
					$sub .= '<tr class="even">';
				}
				else
				{
					$sub .= '<tr>';
				}

				$sub .= '<td class="count">';

				#
				# selector
				#

				$sub .= new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						'name' => WdOperation::KEY . '[' . $m_id . ']',
						'disabled' => $is_required
					)
				);

				$sub .= '</td>';

				$sub .= '<td class="name">';
				$sub .= WdRoute::find_matching('/admin/' . $m_id) ? '<a href="/admin/' . $m_id . '">' . $title . '</a>' : $title;
				$sub .= '</td>';

				#
				# Author
				#

				$sub .= '<td>';
				$sub .= 'Olivier Laviale';
				$sub .= '</td>';

				#
				# Description
				#

				$sub .= '<td>';
				$sub .= isset($m_desc[WdModule::T_DESCRIPTION]) ? t($m_desc[WdModule::T_DESCRIPTION]) : '&nbsp;';
				$sub .= '</td>';

				if (!$is_installer_mode)
				{
					#
					# because disabled module cannot be loaded, we need to trick the system
					#

					/*
					$disabled = empty($m_desc[WdModule::T_DISABLED]) ? false : true;

					$m_desc[WdModule::T_DISABLED] = false;
					*/

					if ($core->hasModule($m_id))
					{
						$module = $core->getModule($m_id);

						$is_installed = false;

						try
						{
							$is_installed = $module->isInstalled();
						}
						catch (Exception $e)
						{
							wd_log_error('Exception with module %module: :message', array('%module' => (string) $module, ':message' => $e->getMessage()));
						}

						if ($is_installed)
						{
							$sub .= '<td class="installed">' . t('Installed') . '</td>';
						}
						else if ($is_installed === false)
						{
							$sub .= '<td>';
							/*
							$sub .= t('Not installed');
							$sub .= ' ';
							*/
							$sub .= '<a class="install" href="';
							$sub .= '/admin/' . $this . '/' . $module . '/install';

							$sub .= '">' . t('Install module') . '</a>';

							$sub .= '</td>';
						}
						else // null
						{
							$sub .= '<td class="not-applicable">';
							$sub .= 'Not applicable';
							$sub .= '</td>';
						}
					}
					else
					{
						$sub .= '<td class="not-applicable">';
						$sub .= 'Module is disabled';
						$sub .= '</td>';
					}

					/*
					#
					# now we can restore the disabled status
					#

					if ($disabled)
					{
						$m_desc[WdModule::T_DISABLED] = true;
					}
					*/
				}

				$sub .= '</tr>';
			}

			if ($sub)
			{
				$contents .= '<tr class="module">';
				$contents .= '<td colspan="' . $span . '">';
				$contents .= ucfirst($p_name);
				$contents .= '</td>';
				$contents .= '</tr>';
				$contents .= $sub;
			}
		}

		$contents .= '</tbody>';


		#
		# jobs
		#

		$contents .= '<tfoot>';
		$contents .= '<tr>';
		$contents .= '<td colspan="5"><button type="submit" class="danger">Désactiver les modules sélectionnés</button></td>';
		$contents .= '</tr>';
		$contents .= '</tfoot>';

		$contents .= '</table>';

		return new WdForm
		(
			array
			(
				WdElement::T_CHILDREN => array
				(
					$contents
				),

				'class' => 'management'
			),

			'div'
		);
	}

	protected function block_install($module_id)
	{
		global $core;

		if (!$core->user->has_permission(self::PERMISSION_ADMINISTER, $this))
		{
			return '<div class="group"><p>' . t('You don\'t have enought privileges to install packages.') . '</p></div>';
		}

		if (empty($core->descriptors[$module_id]))
		{
			return '<div class="group"><p>' . t('The module %module_id does not exists.', array('%module_id' => $module_id)) . '</p></div>';
		}

		$module = $core->loadModule($module_id, $core->descriptors[$module_id]);

		if ($module->isInstalled())
		{
			return '<div class="group"><p>' . t('The module %module is already installed', array('%module' => $module_id)) . '</p></div>';
		}

		if (!$module->install())
		{
			return '<div class="group"><p>' . t('Unable to install the module %module', array('%module' => $module_id)) . '</p></div>';
		}

		return '<div class="group"><p>' . t('The module %module has been installed. <a href="/admin/' . $this . '">Retourner à la liste.</a>', array('%module' => $module_id)) . '</p></div>';
	}

	protected function block_inactives()
	{
		global $document;

		$document->css->add('public/css/manage.css');
		$document->css->add('public/manage.css', 10);
		$document->js->add('public/module.js');

		#
		# read and sort packages and modules
		#

		global $core;


		$packages = array();
		$modules = array();

		foreach ($core->descriptors as $m_id => $descriptor)
		{
			$name = isset($descriptor[WdModule::T_TITLE]) ? $descriptor[WdModule::T_TITLE] : $m_id;

			if (isset($descriptor[WdModule::T_CATEGORY]))
			{
				$package = $descriptor[WdModule::T_CATEGORY];
			}
			else
			{
				list($package) = explode('.', $m_id);
			}

			$package = t($package, array(), array('scope' => 'system.modules.categories', 'default' => $package));

			$packages[$package][t($name)] = array_merge
			(
				$descriptor, array
				(
					self::T_ID => $m_id
				)
			);
		}

		uksort($packages, 'wd_unaccent_compare_ci');

		$categories = $packages;

		$mandatories = $core->getModuleIdsByProperty(WdModule::T_REQUIRED);

		#
		# disabled modules
		#

		$rows = '';

		foreach ($categories as $category => $descriptors)
		{
			$category_rows = null;

			foreach ($descriptors as $title => $descriptor)
			{
				$m_id = $descriptor[WdModule::T_ID];

				if (isset($mandatories[$m_id]) || empty($descriptor[WdModule::T_DISABLED]))
				{
					continue;
				}

				$category_rows .= '<tr>';

				#
				# activate
				#

				$category_rows .= '<td class="count">';

				/*
				$category_rows .= new WdElement
				(
					'label', array
					(
						WdElement::T_CHILDREN => array
						(
							new WdElement
							(
								WdElement::E_CHECKBOX, array
								(
									'name' => WdOperation::KEY . '[' . $m_id . ']'
								)
							)
						),

						'class' => 'checkbox-wrapper circle'
					)
				);
				*/

				$category_rows .= new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						'name' => WdOperation::KEY . '[' . $m_id . ']'
					)
				);

				$category_rows .= '</td>';

				$category_rows .= '<td class="name">';
				$category_rows .= $title;
				$category_rows .= '</td>';

				#
				# Author
				#

				$category_rows .= '<td>';
				$category_rows .= 'Olivier Laviale';
				$category_rows .= '</td>';

				#
				# Description
				#

				$category_rows .= '<td>';
				$category_rows .= isset($m_desc[WdModule::T_DESCRIPTION]) ? t($m_desc[WdModule::T_DESCRIPTION]) : '&nbsp;';
				$category_rows .= '</td>';

				#
				# installed
				#

				$is_installed = false;

				/*
				try
				{
					$module = $core->hasModule($m_id) ? $core->getModule($m_id) : $core->loadModule($m_id);

					$is_installed = $module->isInstalled();
				}
				catch (Exception $e)
				{
					wd_log_error('Exception with module %module: :message', array('%module' => (string) $module, ':message' => $e->getMessage()));
				}

				$category_rows .= '<td class="installed">' . t($is_installed ? 'Installed' : 'Not installed') . '</td>';
				*/

				$category_rows .= '<td>&nbsp;</td>';

				#
				#
				#

				$category_rows .= '</tr>';
			}

			if ($category_rows)
			{
				$rows .= '<tr class="module">';
				$rows .= '<td colspan="5">';
				$rows .= ucfirst($category);
				$rows .= '</td>';
				$rows .= '</tr>';
				$rows .= $category_rows;
			}
		}

		$disabled_table = null;

		$rc = '';

		if ($rows)
		{
			$rc .= '<table class="manage resume" cellpadding="4" cellspacing="0">';
			$rc .= '<thead>';
			$rc .= '<tr>';
			$rc .= '<th colspan="2">&nbsp;</th>';
			$rc .= '<th>' . t('Author') . '</th>';
			$rc .= '<th>' . t('Description') . '</th>';
			$rc .= '<th>' . t('Installed') . '</th>';
			$rc .= '</tr>';
			$rc .= '</thead>';

			$rc .= '<tbody>' . $rows . '</tbody>';

			$rc .= '<tfoot><tr><td colspan="5"><button type="submit" class="danger">Activer les
			modules sélectionnés</button></td></tr></tfoot>';

			$rc .= '</table>';
		}

		return new WdForm
		(
			array
			(
				WdForm::T_HIDDENS => array
				(
					WdOperation::NAME => self::OPERATION_ACTIVATE,
					WdOperation::DESTINATION => $this
				),

				WdElement::T_CHILDREN => array
				(
					$rc
				)
			)
		);
	}

	const OPERATION_ACTIVATE = 'activate';

	protected function get_operation_activate_controls(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_ADMINISTER,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_activate(WdOperation $operation)
	{
		global $registry;

		$enabled = json_decode($registry['wdcore.enabled_modules'], true);
		$enabled = $enabled ? array_flip($enabled) : array();

		foreach ((array) $operation->key as $key => $dummy)
		{
			$enabled[$key] = true;
		}

		$registry['wdcore.enabled_modules'] = json_encode(array_keys($enabled));

		$operation->location = '/admin/' . $this->id;

		return true;
	}

	const OPERATION_DEACTIVATE = 'deactivate';

	protected function get_operation_deactivate_controls(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_ADMINISTER,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_deactivate(WdOperation $operation)
	{
		global $registry;

		$enabled = json_decode($registry['wdcore.enabled_modules'], true);
		$enabled = $enabled ? array_flip($enabled) : array();

		foreach ((array) $operation->key as $key => $dummy)
		{
			unset($enabled[$key]);
		}

		$registry['wdcore.enabled_modules'] = json_encode(array_keys($enabled));

		$operation->location = '/admin/' . $this->id;

		return true;
	}
}