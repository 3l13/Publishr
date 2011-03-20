<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class site_search_WdModule extends WdPModule
{
	protected function block_config()
	{
		global $core;

		$core->document->css->add('public/config.css');
		$core->document->js->add('public/config.js');

		$options = array();

		foreach ($core->modules->descriptors as $module_id => $descriptor)
		{
			if (empty($core->modules[$module_id]))
			{
				continue;
			}

			if (!WdModule::is_extending($module_id, 'contents') && !WdModule::is_extending($module_id, 'site.pages'))
			{
				continue;
			}

			$options[$module_id] = t($descriptor[WdModule::T_TITLE]);
		}

		$options['google'] = '<em>Google</em>';

		asort($options);

		$scope = explode(',', $core->working_site->metas[$this->flat_id . '.scope']);
		$scope = array_combine($scope, array_fill(0, count($scope), true));

		$sorted_options = array();

		foreach ($scope as $module_id => $dummy)
		{
			if (empty($options[$module_id]))
			{
				continue;
			}

			$sorted_options[$module_id] = $options[$module_id];
		}

		$sorted_options += $options;

		$el = '<ul class="sortable combo self-handle">';

		foreach ($sorted_options as $module_id => $label)
		{
			$el .= '<li>';
			$el .= new WdElement
			(
				'input', array
				(
					WdElement::T_LABEL => $label,

					'name' => "local[$this->flat_id.scope][$module_id]",
					'type' => 'checkbox',
					'checked' => !empty($scope[$module_id])
				)
			);

			$el .= '</li>';
		}

		$el .= '</ul>';

		#
		# description
		#

		$pageid = $core->working_site->metas['views.targets.site_search/search'];

		if ($pageid)
		{
			$page = $core->models['site.pages'][$pageid];
			$description_link = '<a href="/admin/site.pages/' . $page->nid . '/edit">' . wd_entities($page->title) . '</a>';
		}
		else
		{
			$description_link = '<q><a href="/admin/site.pages">Pages</a></q>';
		}

		return array
		(
			WdElement::T_GROUPS => array
			(
				'primary' => array
				(
					'description' => t($pageid ? 'description' : 'description_nopage', array(':link' => $description_link))
				)
			),

			WdElement::T_CHILDREN => array
			(
				"local[$this->flat_id.scope]" => new WdElement
				(
					'div', array
					(
						WdForm::T_LABEL => '.scope',
						WdElement::T_INNER_HTML => $el,
						WdElement::T_DESCRIPTION => '.scope'
					)
				),

				"local[$this->flat_id.limits.home]" => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'limits_home',
						WdElement::T_DEFAULT => 5
					)
				),

				"local[$this->flat_id.limits.list]" => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'limits_list',
						WdElement::T_DEFAULT => 10
					)
				)
			)
		);
	}
}