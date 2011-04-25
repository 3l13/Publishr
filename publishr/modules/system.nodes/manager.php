<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class system_nodes_WdManager extends WdManager
{
	public function __construct($module, array $tags=array())
	{
		global $document;

		parent::__construct
		(
			$module, $tags + array
			(
				self::T_KEY => Node::NID,
				self::T_ORDER_BY => array(Node::MODIFIED, 'desc')
			)
		);

		$document->css->add('public/manage.css');
		$document->js->add('public/manage.js');
	}

	protected function columns()
	{
		return array
		(
			'url' => array
			(
				'label' => null,
				'class' => 'url',
			),

			Node::TITLE => array
			(

			),

			Node::UID => array
			(

			),

			Node::CONSTRUCTOR => array
			(
				self::COLUMN_HOOK => array($this, 'render_filter_cell')
			),

			Node::CREATED => array
			(
				'class' => 'date',
				self::COLUMN_HOOK => array($this, 'render_cell_datetime'),
				'default_order' => -1
			),

			Node::MODIFIED => array
			(
				'class' => 'date',
				self::COLUMN_HOOK => array($this, 'render_cell_datetime'),
				'default_order' => -1
			),

			Node::IS_ONLINE => array
			(
				'label' => null,
				'class' => 'is_online',
				'orderable' => false
			)
		);
	}

	protected function jobs()
	{
		return parent::jobs() + array
		(
			'online' => t('online.operation.short_title'),
			'offline' => t('offline.operation.short_title')
		);
	}

	protected function parseColumns($columns)
	{
		$translations = $this->model->where('constructor = ? AND tnid != 0', (string) $this->module)->count();

		if ($translations)
		{
			$expanded = array();

			foreach ($columns as $identifier => $column)
			{
				$expanded[$identifier] = $column;

				if ($identifier == 'title')
				{
					$expanded['translations'] = array
					(
						'label' => 'Translations'
					);
				}
			}

			$columns = $expanded;
		}

		return parent::parseColumns($columns);
	}

	/**
	 * Alters the query with the 'is_online' and 'uid' filters.
	 *
	 * @see WdManager::alter_query()
	 */
	protected function alter_query(WdActiveRecordQuery $query, array $filters)
	{
		if (isset($filters['is_online']))
		{
			$query->where('is_online = ?', $filters['is_online']);
		}

		if (isset($filters['uid']))
		{
			$query->where('uid = ?', $filters['uid']);
		}

		return parent::alter_query($query, $filters)->where('constructor = ?', (string) $this->module);
	}

	protected function alter_records(array $records)
	{
		$records = parent::alter_records($records);

		$this->resolve_translations($records);

		return $records;
	}

	protected $translations_by_records;

	protected function resolve_translations(array $records)
	{
		global $core;

		$translations = array();
		$translations_by_records = array();

		$site = $core->site;
		$sites = $core->models['site.sites'];
		$site_translations = $site->translations;

		if (!$site_translations)
		{
			return;
		}

		$site_translations_ids = array();

		foreach ($site_translations as $site_translation)
		{
			$site_translations_ids[] = $site_translation->siteid;
		}

//		var_dump($site_translations_ids, $site_translations);

		if ($site->nativeid)
		{
			foreach ($records as $record)
			{
				$tnid = $record->tnid;

				if (!$tnid)
				{
					continue;
				}

				$translations[$tnid] = true;
				$translations_by_records[$record->nid][$tnid] = true;
			}
		}
		else
		{
			$native_ids = array();

			foreach ($records as $record)
			{
				$native_ids[] = $record->nid;
			}

			if (!$native_ids)
			{
				return;
			}

			$translations_raw = $core->models['system.nodes']->select('siteid, tnid, language, nid')->where(array('tnid' => $native_ids, 'siteid' => $site_translations_ids))->order('FIELD(siteid, ' . implode(',', $site_translations_ids) . ')')->all;

			if (!$translations_raw)
			{
				return;
			}

			foreach ($translations_raw as $translation)
			{
				$translations_by_records[$translation['tnid']][$translation['nid']] = array
				(
					'site' => $sites[$translation['siteid']],
					'siteid' => $translation['siteid'],
					'language' => $translation['language']
				);
			}

//			var_dump($translations_by_records);

			$this->translations_by_records = $translations_by_records;

			return;
		}

		if (!$translations)
		{
			return;
		}

		$translations = array_keys($translations);
		$ids = implode(',', $translations);

		$infos = $core->models['system.nodes']->select('siteid, language')->where('nid IN(' . $ids . ')')->order('FIELD(nid, ' . $ids . ')')->all;

		//var_dump($translations_by_records, $translations, $infos);

		$translations = array_combine($translations, $infos);

		foreach ($translations_by_records as $nid => $nt)
		{
			foreach ($nt as $tnid => $dummy)
			{
				$translation = $translations[$tnid];
				$translation['site'] = $sites[$translation['siteid']];

				$translations_by_records[$nid][$tnid] = $translation;
			}
		}

		$this->translations_by_records = $translations_by_records;
	}

	protected function extend_column_is_online(array $column, $id)
	{
		return array
		(
			'filters' => array
			(
				'options' => array
				(
					'=1' => 'En ligne',
					'=0' => 'Hors ligne'
				)
			),

			'sortable' => false
		)

		+ parent::extend_column($column, $id);
	}

	/**
	 * Extends the "uid" column by providing users filters.
	 *
	 * @see WdManager::extend_column()
	 *
	 * @param array $column
	 * @param string $id
	 */
	protected function extend_column_uid(array $column, $id)
	{
		global $core;

		$users_keys = $this->module->model->select('DISTINCT uid')->own->similar_site->all(PDO::FETCH_COLUMN);

		if (!$users_keys || count($users_keys) == 1)
		{
			return array
			(
				'sortable' => false
			)

			+ parent::extend_column($column, $id);
		}

		$users = $core->models['user.users']->select('CONCAT("=", uid), IF((firstname != "" AND lastname != ""), CONCAT_WS(" ", firstname, lastname), username) name')->where(array('uid' => $users_keys))->order('name')->pairs;

		return array
		(
			'filters' => array
			(
				'options' => $users
			)
		)

		+ parent::extend_column($column, $id);
	}

	protected function extend_column_translations(array $column, $id)
	{
		return array
		(
			'sortable' => false
		)

		+ parent::extend_column($column, $id);
	}

	protected function render_cell_url($record)
	{
		$url = $record->url;

		if (!$url || $url{0} == '#')
		{
			return;
		}

		return new WdElement
		(
			'a', array
			(
				WdElement::T_INNER_HTML => t('Display'),

				'href' => $url,
				'title' => t('View this entry on the website'),
				'class' => 'view'
			)
		);
	}

	protected function render_cell_title($record, $property)
	{
		global $core;
		static $languages;
		static $languages_count;

		if ($languages === null)
		{
			$languages = $core->models['site.sites']->count('language');
			$languages_count = count($languages);
		}

		$title = $record->$property;
		$label = $title ? wd_entities(wd_shorten($title, 52, .75, $shortened)) : $this->t->__invoke('<em>no title</em>');

		if ($shortened)
		{
			$label = str_replace('…', '<span class="light">…</span>', $label);
		}

		$rc = $this->render_cell_url($record);

		if ($rc)
		{
			$rc .= ' ';
		}

		$rc .= new WdElement
		(
			'a', array
			(
				WdElement::T_INNER_HTML => $label,

				'class' => 'edit',
				'title' => $shortened ? $this->t->__invoke('manager.edit_named', array(':title' => $title ? $title : 'unnamed')) : $this->t->__invoke('manager.edit'),
				'href' => $core->site->path . '/admin/' . $record->constructor . '/' . $record->nid . '/edit'
			)
		);

		$metas = '';

		$language = $record->language;

		if ($languages_count > 1 && $language != $core->site->language)
		{
			$metas .= ', <span class="language">' . ($language ? $language : 'multilingue') . '</span>';
		}

		if (!$record->siteid)
		{
			$metas .= ', multisite';
		}

		if ($metas)
		{
			$rc .= '<span class="metas small light">:' . substr($metas, 2) . '</span>';
		}

		return $rc;
	}

	private $last_rendered_uid;

	protected function render_cell_uid($record, $property)
	{
		$uid = $record->uid;

		if ($this->last_rendered_uid === $uid)
		{
			return self::REPEAT_PLACEHOLDER;
		}

		$this->last_rendered_uid = $uid;

		$label = $this->render_cell_user($record, $property);

		return parent::render_filter_cell($record, $property, $label);
	}

	protected function render_cell_is_online($entry, $tag)
	{
		return new WdElement
		(
			'label', array
			(
				WdElement::T_CHILDREN => array
				(
					new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							'value' => $entry->nid,
							'checked' => ($entry->$tag != 0),
							'class' => 'is_online'
						)
					)
				),

				'class' => 'checkbox-wrapper circle',
				'title' => '.is_online'
			)
		);
	}

	protected function render_cell_translations(WdActiveRecord $record)
	{
		global $core;

		if (empty($this->translations_by_records[$record->nid]))
		{
			return;
		}

		$translations = $this->translations_by_records[$record->nid];

		$rc = '';

		foreach ($translations as $tnid => $translation)
		{
			$rc .= ', <a href="' . $translation['site']->url . '/admin/' . $this->module . '/' . $tnid . '/edit">' . $translation['language'] . '</a>';
		}

		return '<span class="translations">' . substr($rc, 2) . '</span>';
	}
}