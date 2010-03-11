<?php

class system_modules_WdModule extends WdPModule
{
	const OPERATION_PACKAGES = 'packages';
	const OPERATION_INSTALL = 'install';

	protected function getOperationsAccessControls()
	{
		return array
		(
			self::OPERATION_PACKAGES => array
			(
				self::CONTROL_PERMISSION => PERMISSION_ADMINISTER,
				self::CONTROL_VALIDATOR => false
			)
		)

		+ parent::getOperationsAccessControls();
	}

	protected function operation_packages(WdOperation $operation)
	{
		global $core;

		$disableds = array();

		#
		# reset all modules to 'off'
		#

		foreach ($core->descriptors as $id => $dummy)
		{
			$disableds[$id] = true;
		}

		#
		# update with post
		#

		foreach ($operation->params[WdOperation::KEY] as $id => $state)
		{
			unset($disableds[$id]);
		}

		#
		# force mandatory module to 'on'
		#

		$mandatories = $core->getModuleIdsByProperty(WdModule::T_MANDATORY);

		foreach ($mandatories as $id => $value)
		{
			if (!$value)
			{
				continue;
			}

			unset($disableds[$id]);
		}

		global $registry;

		$registry->set('wdcore.disabledModules', json_encode(array_keys($disableds)));

		return true;
	}

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
			global $user;

			if (!$user->isAdmin())
			{
				return;
			}
		}

		$form = $this->form_manage($options);

		if (!$is_installer_mode)
		{
			$form->setHidden(WdOperation::NAME, self::OPERATION_PACKAGES);
			$form->setHidden(WdOperation::DESTINATION, $this);

			/*
			$contents  = '<br />';
			$contents .= '<button class="save" type="submit"';
			$contents .= ' value="' . self::OPERATION_PACKAGES . '"';
			$contents .= ' title="' . t('Save permissions') . '">';
			$contents .= self::OPERATION_PACKAGES;
			$contents .= '</button>';
			$contents .= '<div style="clear:both"></div>';

			$form->addChild($contents);
			*/
		}

		return $form;
	}

	protected function form_manage(array $options=array())
	{
		$is_installer_mode = isset($options[self::MANAGE_MODE]) && $options[self::MANAGE_MODE] == self::MANAGE_MODE_INSTALLER;

		#
		#
		#

		global $document;

		$document->addStyleSheet('public/css/manage.css');
		$document->addStyleSheet('public/manage.css', -10);
		//$document->addStyleSheet('public/module.css', -10);
		$document->addJavaScript('public/module.js', 0);

		#
		# read and sort packages and modules
		#

		global $core;

		$packages = array();

		//wd_log('core: \1', $core);

		foreach ($core->descriptors as $m_id => $descriptor)
		{
			/*
			if (!$core->hasModule($m_id))
			{
				continue;
			}
			*/

			list($package) = explode('.', $m_id);

			$packages[$package][$m_id] = array_merge
			(
				$descriptor, array
				(
					self::T_ID => $m_id
				)
			);
		}

		ksort($packages);

		$mandatories = $core->getModuleIdsByProperty(WdModule::T_MANDATORY);

		#
		#
		#

		$contents  = '<table class="manage resume" cellpadding="4" cellspacing="0">';
		$contents .= '<thead>';
		$contents .= '<tr>';
		$contents .= '<th colspan="2">Modules</th>';
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

			foreach ($descriptors as $m_id => $descriptor)
			{
				$is_mandatory = isset($mandatories[$m_id]);

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

				/*
				$sub .= new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						'name' => ($is_installer_mode ? 'packages' : WdOperation::KEY) . '[' . $m_id . ']',
						'checked' => $core->hasModule($m_id),
						'disabled' => $is_mandatory
					)
				);
				*/

				$sub .= new WdElement
				(
					'label', array
					(
						WdElement::T_CHILDREN => array
						(
							new WdElement
							(
								WdElement::E_CHECKBOX, array
								(
									'name' => ($is_installer_mode ? 'packages' : WdOperation::KEY) . '[' . $m_id . ']',
									'checked' => $core->hasModule($m_id),
									'disabled' => $is_mandatory
								)
							)
						),

						'class' => 'checkbox-wrapper circle'
					)
				);

				$sub .= '</td>';

				$sub .= '<td class="name">';
				$sub .= t(isset($m_desc[self::T_TITLE]) ? $m_desc[self::T_TITLE] : $m_id);
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
							$sub .= WdRoute::encode('/' . $this . '/' . $module . '/install');

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

		#
		# jobs
		#

		$contents .= '<tr class="footer">';
		$contents .= '<td colspan="' . $span . '"><div class="jobs"><button type="submit" class="save">Enregistrer</button></div></td>';
		$contents .= '</tr>';

		$contents .= '</tbody>';
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
		global $user;

		if (!$user->hasPermission(PERMISSION_ADMINISTER, $this))
		{
			return '<div class="group"><p>' . t('You don\'t have enought privileges to install packages.') . '</p></div>';
		}

		global $core;

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

		return '<div class="group"><p>' . t('The module %module has been installed. <a href="' . WdRoute::encode('/' . $this) . '">Retourner à la liste.</a>', array('%module' => $module_id)) . '</p></div>';
	}
}