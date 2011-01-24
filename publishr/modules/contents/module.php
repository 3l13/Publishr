<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_WdModule extends system_nodes_WdModule
{
	/**
	 * The 'home_include' operation is used to include a node is the home list.
	 */

	const OPERATION_HOME_INCLUDE = 'home_include';

	protected function controls_for_operation_home_include(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_MAINTAIN,
			self::CONTROL_RECORD => true,
			self::CONTROL_OWNERSHIP => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_home_include(WdOperation $operation)
	{
		$record = $operation->record;

		$record->is_home_excluded = false;
		$record->save();

		wd_log_done('!title is now included on the home page', array('!title' => $record->title));

		return true;
	}

	/**
	 * The `home_exclude` operation is used to exclude a node from the home list.
	 */

	const OPERATION_HOME_EXCLUDE = 'home_exclude';

	protected function controls_for_operation_home_exclude(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_MAINTAIN,
			self::CONTROL_RECORD => true,
			self::CONTROL_OWNERSHIP => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_home_exclude(WdOperation $operation)
	{
		$record = $operation->record;

		$record->is_home_excluded = true;
		$record->save();

		wd_log_done('!title is now excluded from the home page', array('!title' => $record->title));

		return true;
	}

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
					'title' => 'Limites',
					'class' => 'form-section flat'
				)
			),

			WdElement::T_CHILDREN => array
			(
				"local[$this->flat_id.default_editor]" => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => "Éditeur par défaut"
					)
				),

				"local[$this->flat_id.use_multi_editor]" => new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						WdElement::T_LABEL => "Permettre à l'utilisateur de changer d'éditeur"
					)
				),

				"local[$this->flat_id.limits.home]" => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => "Limite du nombre d'entrées sur la page d'accueil",
						WdElement::T_DEFAULT => 3,
						WdElement::T_GROUP => 'limits'
					)
				),

				"local[$this->flat_id.limits.list]" => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => "Limite du nombre d'entrées sur la page de liste",
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

		$default_editor = $core->working_site->metas->get($this->flat_id . '.default_editor', 'moo');
		$use_multi_editor = $core->working_site->metas->get($this->flat_id . '.use_multi_editor');

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
						'title' => 'Contenu',
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
							WdForm::T_LABEL => 'Sous-titre'
						)
					),

					contents_WdActiveRecord::BODY => new WdMultiEditorElement
					(
						$properties['editor'] ? $properties['editor'] : $default_editor, array
						(
							WdElement::T_LABEL_MISSING => 'Contents',
							WdElement::T_GROUP => 'contents',
							WdElement::T_REQUIRED => true,

							'rows' => 16
						)
					),

					contents_WdActiveRecord::EXCERPT => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => 'Accroche',
							WdElement::T_GROUP => 'contents',
							WdElement::T_DESCRIPTION => "L'arroche présente	en quelques mots
							le contenu. Vous pouvez saisir votre propre accroche ou laisser le
							système la créer pour vous à partir des 50 premiers mots du contenu.",

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
							WdElement::T_LABEL => "Ne pas afficher en page d'accueil",
							WdElement::T_GROUP => 'online',
							WdElement::T_DESCRIPTION => "L'entrée n'apparait pas en page d'accueil
							lorsque la case est cochée. Que la case soit cochée ou non, l'entrée
							apparait en page de liste."
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

		if (!$record && isset($page->url_variables['slug']))
		{
			$slug = $page->url_variables['slug'];
			$tries = $this->model->select('nid, slug')->order('date DESC')->pairs;
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

	protected function provide_view_alter_query_home($query)
	{
		$query->where('is_home_excluded = 0');
		$query->order('date DESC');

		return $query;
	}

	protected function provide_view_alter_query_list(WdActiveRecordQuery $query)
	{
		$query->order('date DESC');

		return $query;
	}
}