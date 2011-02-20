<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class system_nodes_WdManager extends WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct
		(
			$module, $tags + array
			(
				self::T_KEY => Node::NID,
				self::T_ORDER_BY => array(Node::MODIFIED, 'desc')
			)
		);

		global $document;

		$document->css->add('public/manage.css');
		$document->js->add('public/manage.js');
	}

	protected function columns()
	{
		return array
		(
			'url' => array
			(
				self::COLUMN_LABEL => null,
				self::COLUMN_CLASS => 'url',
			),

			Node::TITLE => array
			(

			),

			Node::UID => array
			(

			),

			Node::CONSTRUCTOR => array
			(
				self::COLUMN_HOOK => array(__CLASS__, 'select_callback')
			),

			Node::CREATED => array
			(
				self::COLUMN_CLASS => 'date',
				self::COLUMN_HOOK => array($this, 'get_cell_datetime')
			),

			Node::MODIFIED => array
			(
				self::COLUMN_CLASS => 'date',
				self::COLUMN_HOOK => array($this, 'get_cell_datetime')
			),

			Node::IS_ONLINE => array
			(
				self::COLUMN_LABEL => null,
				self::COLUMN_CLASS => 'is_online'
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
						self::COLUMN_LABEL => 'Translations'
					);
				}
			}

			$columns = $expanded;
		}

		return parent::parseColumns($columns);
	}

	protected function alter_query(WdActiveRecordQuery $query)
	{
		return parent::alter_query($query)->where('constructor = ?', (string) $this->module);
	}

	protected function get_cell_url($entry)
	{
		$url = $entry->url;

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

	protected function get_cell_title($record, $tag)
	{
		global $core;
		static $languages;
		static $languages_count;

		if ($languages === null)
		{
			$languages = $core->models['site.sites']->count('language');
			$languages_count = count($languages);
		}

		$title = $record->$tag;
		$label = $title ? wd_entities(wd_shorten($title, 52, .75, $shortened)) : t('<em>no title</em>');

		if ($shortened)
		{
			$label = str_replace('…', '<span class="light">…</span>', $label);
		}

		$rc = $this->get_cell_url($record);

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
				'title' => $shortened ? t('manager.edit_named', array(':title' => $title ? $title : 'unnamed')) : t('manager.edit'),
				'href' => '/admin/' . $record->constructor . '/' . $record->nid . '/edit'
			)
		);

		$metas = '';

		$language = $record->language;

		if ($languages_count > 1 && $language != $core->working_site->language)
		{
			$metas .= ', <span class="language">' . ($language ? $language : 'neutre') . '</span>';
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

	private $last_uid;

	protected function get_cell_uid($entry, $tag)
	{
		$uid = $entry->uid;

		if ($this->last_uid === $uid)
		{
			return '<span class="lighter">―</span>';
		}

		$this->last_uid = $uid;

		$label = $this->get_cell_user($entry, $tag);

		return parent::select_code($tag, $entry->$tag, $label, $this);
	}

	protected function get_cell_is_online($entry, $tag)
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

	protected function get_cell_translations(WdActiveRecord $record)
	{
		$translations = $record->translations_keys;

		if (!$translations)
		{
			return;
		}

		$rc = '';

		foreach ($translations as $nid => $language)
		{
			$rc .= ', <a href="/admin/' . $this->module . '/' . $nid . '/edit">' . $language . '</a>';
		}

		return '<span class="translations">' . substr($rc, 2) . '</span>';
	}
}