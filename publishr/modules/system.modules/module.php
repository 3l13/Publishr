<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class system_modules_WdModule extends WdPModule
{
	const MANAGE_MODE = '#manage-mode';
	const MANAGE_MODE_INSTALLER = 'installer';

	const OPERATION_ACTIVATE = 'activate';
	const OPERATION_DEACTIVATE = 'deactivate';

	protected function block_manage(array $options=array())
	{
		global $core;

		$is_installer_mode = isset($options[self::MANAGE_MODE])	&& $options[self::MANAGE_MODE] == self::MANAGE_MODE_INSTALLER;

		if (!$core->user->has_permission(self::PERMISSION_ADMINISTER, $this))
		{
			throw new WdHTTPException("You don't have permission to administer modules.", array(), 403);
		}

		$form = $this->form_manage($options);

		if (!$is_installer_mode)
		{
			$form->hiddens[WdOperation::NAME] = self::OPERATION_DEACTIVATE;
			$form->hiddens[WdOperation::DESTINATION] = $this;
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
		global $core, $document;

		$document->css->add('public/css/manage.css');
		$document->css->add('public/manage.css', 10);

		$is_installer_mode = isset($options[self::MANAGE_MODE]) && $options[self::MANAGE_MODE] == self::MANAGE_MODE_INSTALLER;

		#
		# read and sort packages and modules
		#

		$packages = array();
		$modules = array();

		foreach ($core->modules->descriptors as $id => $descriptor)
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
				list($category) = explode('.', $id);
			}

			$category = t($category, array(), array('scope' => 'module_category.title', 'default' => ucfirst($category)));
			$title = t(strtr($id, '.', '_'), array(), array('scope' => 'module.title', 'default' => isset($descriptor[WdModule::T_TITLE]) ? $descriptor[WdModule::T_TITLE] : $id));

			$packages[$category][$title] = array_merge
			(
				$descriptor, array
				(
					self::T_ID => $id
				)
			);
		}

		uksort($packages, 'wd_unaccent_compare_ci');

		$categories = $packages;
		$mandatories = $core->modules->ids_by_property(WdModule::T_REQUIRED);

		$rows = '';

		$span = $is_installer_mode ? 4 : 5;
		$context = $core->site->path;

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
				$sub .= WdRoute::find('/admin/' . $m_id) ? '<a href="' . $context . '/admin/' . $m_id . '">' . $title . '</a>' : $title;
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

					if (isset($core->modules[$m_id]))
					{
						$module = $core->modules[$m_id];

						$is_installed = false;

						try
						{
							$is_installed = $module->is_installed();
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
							$sub .= $context . '/admin/' . $this . '/' . $module . '/install';

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
				$rows .= <<<EOT
<tr class="module">
	<td colspan="$span">$p_name</td>
</tr>

$sub
EOT;
			}
		}

		$label_author = t('Author');
		$label_description = t('Description');
		$label_button = t('Désactiver les modules sélectionnés');

		$th_installed = null;

		if (!$is_installer_mode)
		{
			$th_installed = '<th><div>' . t('Installed') . '</div></th>';
		}

		$contents  = <<<EOT
<table class="manage" cellpadding="4" cellspacing="0">
	<thead>
		<tr>
		<th colspan="2"><div>&nbsp;</div></th>
		<th><div>$label_author</div></th>
		<th><div>$label_description</div></th>
		$th_installed
		</tr>
	</thead>

	<tfoot>
		<tr>
		<td colspan="5"><button type="submit" class="danger">$label_button</button></td>
		</tr>
	</tfoot>

	<tbody>
		$rows
	</tbody>
</table>
EOT;

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

		if (empty($core->modules[$module_id]))
		{
			return '<div class="group"><p>' . t('The module %module_id does not exists.', array('%module_id' => $module_id)) . '</p></div>';
		}

		$module = $core->modules[$module_id];

		if ($module->is_installed())
		{
			return '<div class="group"><p>' . t('The module %module is already installed', array('%module' => $module_id)) . '</p></div>';
		}

		if (!$module->install())
		{
			return '<div class="group"><p>' . t('Unable to install the module %module', array('%module' => $module_id)) . '</p></div>';
		}

		return '<div class="group"><p>' . t('The module %module has been installed. <a href="' . $core->site->path . '/admin/' . $this . '">Retourner à la liste.</a>', array('%module' => $module_id)) . '</p></div>';
	}

	protected function block_inactives()
	{
		global $core, $document;

		$document->css->add('public/css/manage.css');
		$document->css->add('public/manage.css', 10);

		#
		# read and sort packages and modules
		#

		$categories = array();
		$modules = array();

		foreach ($core->modules->descriptors as $id => $descriptor)
		{
			$name = isset($descriptor[WdModule::T_TITLE]) ? $descriptor[WdModule::T_TITLE] : $id;

			if (isset($descriptor[WdModule::T_CATEGORY]))
			{
				$category = $descriptor[WdModule::T_CATEGORY];
			}
			else
			{
				list($category) = explode('.', $id);
			}

			$category = t($category, array(), array('scope' => 'module_category.title', 'default' => ucfirst($category)));
			$title = t(isset($descriptor[WdModule::T_TITLE]) ? $descriptor[WdModule::T_TITLE] : $id);

			$categories[$category][$title] = array_merge
			(
				$descriptor, array
				(
					self::T_ID => $id
				)
			);
		}

		uksort($categories, 'wd_unaccent_compare_ci');

		$mandatories = $core->modules->ids_by_property(WdModule::T_REQUIRED);

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

				$checkbox = new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						'name' => WdOperation::KEY . '[' . $m_id . ']'
					)
				);

				$author = 'Olivier Laviale';
				$description = isset($m_desc[WdModule::T_DESCRIPTION]) ? t($m_desc[WdModule::T_DESCRIPTION]) : '&nbsp;';

				$category_rows .= <<<EOT
<tr>
	<td class="count">$checkbox</td>
	<td class="name">$title</td>
	<td>$author</td>
	<td>$description</td>
	<td>&nbsp;</td>
</tr>
EOT;
			}

			if ($category_rows)
			{
				$rows .= <<<EOT
<tr class="module">
	<td colspan="5">$category</td>
</tr>

$category_rows
EOT;
			}
		}

		$disabled_table = null;

		$rc = '';

		if ($rows)
		{
			$label_author = t('Author');
			$label_description = t('Description');
			$label_installed = t('Installed');
			$label_button = t('Activer les modules sélectionnés');

			$rc = <<<EOT
<table class="manage resume" cellpadding="4" cellspacing="0">
	<thead>
		<tr>
		<th colspan="2"><div>&nbsp;</div></th>
		<th><div>$label_author</div></th>
		<th><div>$label_description</div></th>
		<th><div>$label_installed</div></th>
		</tr>
	</thead>

	<tbody>$rows</tbody>

	<tfoot>
		<tr>
		<td colspan="5"><button type="submit" class="danger">$label_button</button></td>
		</tr>
	</tfoot>

</table>
EOT;
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
}