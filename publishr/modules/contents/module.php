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
 * The "contents" module extends the "system.nodes" module by offrering a subtitle, a body
 * (with a customizable editor), an optional excerpt, a date and a new visibility option (home).
 */
class contents_WdModule extends system_nodes_WdModule
{
	const OPERATION_HOME_INCLUDE = 'home_include';
	const OPERATION_HOME_EXCLUDE = 'home_exclude';

	protected function block_manage()
	{
		return new contents_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', /*'category',*/ 'uid', 'is_home_excluded', 'is_online', 'date', 'modified'
				)
			)
		);
	}

	protected function block_config()
	{
		return array
		(
			WdElement::T_GROUPS => array
			(
				'limits' => array
				(
					'title' => '.limits',
					'class' => 'form-section flat'
				)
			),

			WdElement::T_CHILDREN => array
			(
				"local[$this->flat_id.default_editor]" => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => '.default_editor'
					)
				),

				"local[$this->flat_id.use_multi_editor]" => new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						WdElement::T_LABEL => '.use_multi_editor'
					)
				),

				"local[$this->flat_id.limits.home]" => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => '.limits_home',
						WdElement::T_DEFAULT => 3,
						WdElement::T_GROUP => 'limits'
					)
				),

				"local[$this->flat_id.limits.list]" => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => '.limits_list',
						WdElement::T_DEFAULT => 10,
						WdElement::T_GROUP => 'limits'
					)
				)
			)
		);
	}

	protected function block_edit(array $properties, $permission)
	{
		global $core;

		$default_editor = $core->site->metas->get($this->flat_id . '.default_editor', 'moo');
		$use_multi_editor = $core->site->metas->get($this->flat_id . '.use_multi_editor');

		if ($use_multi_editor)
		{

		}
		else
		{

		}

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission),

			array
			(
				WdElement::T_GROUPS => array
				(
					'contents' => array
					(
						'title' => '.contents',
						'class' => 'form-section flat'
					),

					'date' => array
					(
					)
				),

				WdElement::T_CHILDREN => array
				(
					contents_WdActiveRecord::SUBTITLE => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => '.subtitle'
						)
					),

					contents_WdActiveRecord::BODY => new WdMultiEditorElement
					(
						$properties['editor'] ? $properties['editor'] : $default_editor, array
						(
							WdElement::T_LABEL_MISSING => 'Contents', // TODO-20110205: scope => 'element', 'missing', 'label'
							WdElement::T_GROUP => 'contents',
							WdElement::T_REQUIRED => true,

							'rows' => 16
						)
					),

					contents_WdActiveRecord::EXCERPT => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => '.excerpt',
							WdElement::T_GROUP => 'contents',
							WdElement::T_DESCRIPTION => ".excerpt",

							'rows' => 3
						)
					),

					contents_WdActiveRecord::DATE => new WdDateElement
					(
						array
						(
							WdForm::T_LABEL => 'Date',
							WdElement::T_REQUIRED => true,
							WdElement::T_DEFAULT => date('Y-m-d')
						)
					),

					'is_home_excluded' => new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							WdElement::T_LABEL => ".is_home_excluded",
							WdElement::T_GROUP => 'online',
							WdElement::T_DESCRIPTION => ".is_home_excluded"
						)
					)
				)
			)
		);
	}

	protected function provide_view_view(WdActiveRecordQuery $query, WdPatron $patron)
	{
		global $page;

		$record = $query->one;
		$url_variables = $page->url_variables;

		if (!$record && empty($url_variables['nid']) && isset($url_variables['slug']))
		{
			$slug = $page->url_variables['slug'];
			$tries = $this->model->select('nid, slug')->where('constructor = ?', $this->id)->visible->order('date DESC')->pairs;
			$key = null;
			$max = 0;

			foreach ($tries as $nid => $compare)
			{
				similar_text($slug, $compare, $p);

				if ($p > $max)
				{
					$key = $nid;

					if ($p > 90)
					{
						break;
					}

					$max = $p;
				}
			}

			if ($key)
			{
				$record = $this->model[$key];

				wd_log('The content node %title was rescued !', array('%title' => $record->title));
			}
		}

		$query->one = $record;

		return parent::provide_view_view($query, $patron);
	}

	protected function provide_view_home(WdActiveRecordQuery $query, WdPatron $patron)
	{
		global $page;

		$limit = $page->site->metas->get("$this->flat_id.limits.home", 5);

		if ($limit)
		{
			$query->limit($limit);
		}

		return $query->all;
	}

	protected function provide_view_alter_query($name, $query)
	{
		global $page;

		$url_variables = $page->url_variables;

		if (isset($url_variables['year']))
		{
			$query->where('YEAR(date) = ?', $url_variables['year']);
		}

		if (isset($url_variables['month']))
		{
			$query->where('MONTH(date) = ?', $url_variables['month']);
		}

		if (isset($url_variables['day']))
		{
			$query->where('DAY(date) = ?', $url_variables['day']);
		}

		if (isset($url_variables['categoryslug']))
		{
			$query->where('nid IN (SELECT nid FROM {prefix}taxonomy_terms
			INNER JOIN {prefix}taxonomy_terms_nodes USING(vtid) WHERE termslug = ?)', $url_variables['categoryslug']);
		}

		return parent::provide_view_alter_query($name, $query);
	}

	protected function provide_view_alter_query_home(WdActiveRecordQuery $query)
	{
		return $query->where('is_home_excluded = 0')->order('date DESC');
	}

	protected function provide_view_alter_query_list(WdActiveRecordQuery $query)
	{
		return $query->order('date DESC');
	}
}