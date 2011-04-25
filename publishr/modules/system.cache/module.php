<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class system_cache_WdModule extends WdPModule
{
	protected function block_manage()
	{
		global $core, $document;

		$document->css->add('public/css/manage.css');
		$document->css->add('public/manage.css');
		$document->js->add('public/manage.js');

		$caches = array
		(
			'core.assets' => array
			(
				'title' => 'CSS et Javascript',
				'description' => "Jeux compilés de sources CSS et Javascript.",
				'group' => 'system',
				'state' => $core->config['cache assets'],
				'size_limit' => false,
				'time_limit' => false
			),

			'core.catalogs' => array
			(
				'title' => 'Traductions',
				'description' => "Traductions par langue pour l'ensemble du framework.",
				'group' => 'system',
				'state' => $core->config['cache catalogs'],
				'size_limit' => false,
				'time_limit' => false
			),

			'core.configs' => array
			(
				'title' => 'Configurations',
				'description' => "Configurations des différents composants du framework.",
				'group' => 'system',
				'state' => $core->config['cache configs'],
				'size_limit' => false,
				'time_limit' => false
			),

			'core.modules' => array
			(
				'title' => 'Modules',
				'description' => "Index des modules disponibles pour le framework.",
				'group' => 'system',
				'state' => $core->config['cache modules'],
				'size_limit' => false,
				'time_limit' => false
			)
		);

		WdEvent::fire
		(
			'alter.block.manage', array
			(
				'target' => $this,
				'caches' => &$caches
			)
		);

		$groups = array();

		asort($caches);

		foreach ($caches as $cache_id => $cache)
		{
			$group = $cache['group'];
			$group = t($group, array(), array('scope' => array('system', 'modules', 'categories'), 'default' => ucfirst($group)));

			$groups[$group][$cache_id] = $cache;
		}

//		uksort($groups, 'wd_unaccent_compare_ci');

		$rows = '';

		foreach ($groups as $group_title => $group)
		{
			$rows .= <<<EOT
<tr class="group-title">
	<td>&nbsp;</td>
	<td>$group_title</td>
	<td colspan="5">&nbsp;</td>
</tr>
EOT;

			foreach ($group as $cache_id => $definition)
			{
				$checked = $definition['state'];

				$checkbox = new WdElement
				(
					'label', array
					(
						WdElement::T_CHILDREN => array
						(
							new WdElement
							(
								WdElement::E_CHECKBOX, array
								(
									'checked' => $checked,
									'disabled' => $definition['state'] === null,
									'name' => $cache_id
								)
							)
						),

						'title' => "Cliquer pour activer ou désactiver le cache",
						'class' => 'checkbox-wrapper circle' . ($checked ? ' checked': '')
					)
				);

				$title = wd_entities($definition['title']);
				$description = $definition['description'];

				$size_limit = null;

				if ($definition['size_limit'])
				{
					list($value, $unit) = $definition['size_limit'];

					$size_limit = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdElement::T_LABEL => $unit,

							'name' => 'size_limit',
							'size' => 4,
							'value' => $value
						)
					);
				}

				$time_limit = null;

				if ($definition['time_limit'])
				{
					list($value, $unit) = $definition['time_limit'];

					$time_limit = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdElement::T_LABEL => $unit,

							'name' => 'time_limit',
							'size' => 4,
							'value' => $value
						)
					);
				}

				$rows .= <<<EOT
<tr>
	<td class="state">$checkbox</td>
	<td class="title">$title<div class="element-description">$description</div></td>
	<td class="limit">$size_limit &nbsp; $time_limit</td>
	<td class="usage empty">&nbsp;</td>
	<td class="erase"><button type="button" class="warn" name="clear">Vider</button></td>
</tr>
EOT;
			}
		}

		$rc = <<<EOT
<table class="wdform manage" cellpadding="0" cellspacing="0" border="0" width="100%">
	<thead>
		<tr>
			<th colspan="2"><div>&nbsp;</div></th>
			<th><div>Limites <span class="small">(Taille et durée)</span></div></th>
			<th class="right"><div>Utilisation</div></th>
			<th><div>&nbsp;</div></th>
		</tr>
	</thead>

	<tbody>$rows</tbody>
</table>
EOT;

		return $rc;
	}
}