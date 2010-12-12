<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
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
			'online' => t('@operation.online.title'),
			'offline' => t('@operation.offline.title')
		);
	}

	protected function parseColumns($columns)
	{
		//TODO-20101121: should go multisite
		if (count(WdI18n::$languages) > 1)
		{
			$expanded = array();

			foreach ($columns as $identifier => $column)
			{
				$expanded[$identifier] = $column;

				if ($identifier == 'title')
				{
					$expanded['translations'] = array
					(
						self::COLUMN_LABEL => null
					);
				}
			}

			$columns = $expanded;
		}

		return parent::parseColumns($columns);
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
				WdElement::T_INNER_HTML => 'Afficher l\'entrée',

				'href' => $url,
				'title' => t('View this entry on the website'),
				'class' => 'view'
			)
		);
	}

	protected function get_cell_title($entry, $tag)
	{
		$title = $entry->$tag;
		$label = $title ? wd_entities(wd_shorten($title, 52, .75, $shortened)) : t('<em>no title</em>');

		if ($shortened)
		{
			$label = str_replace('…', '<span class="light">…</span>', $label);
		}

		$rc = $this->get_cell_url($entry);

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
				'href' => '/admin/' . $entry->constructor . '/' . $entry->nid . '/edit'
			)
		);

		if ($entry->language)
		{
			$rc .= '<span class="language">:' . $entry->language . '</span>';

			/*
			$translations = $this->get_cell_i18n($entry);

			if ($translations)
			{
				$rc .= ' <span class="translations">[' . $translations . ']</span>';
			}
			*/
		}

		return $rc;
	}

	protected function get_cell_uid($entry, $tag)
	{
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
				'title' => "Inclure ou excluse l'entrée du site"
			)
		);
	}

	protected function get_cell_i18n($entry)
	{
		$entries = null;

		if ($entry->tnid)
		{
			$native = $entry->native;

			if ($native)
			{
				$entries = array($native->nid => $native->language);
			}
		}
		else if ($entry->language)
		{
			$entries = $this->model->select('nid, language')->where('tnid = ?', $entry->nid)->order('language')->pairs;
		}

		if (!$entries)
		{
			return;
		}

		$rc = array();

		foreach ($entries as $nid => $language)
		{
			$rc[] = '<a href="/admin/' . $this->module . '/' . $nid . '/edit">' . $language . '</a>';
		}

		return implode(', ', $rc);
	}

	protected function get_cell_translations($entry)
	{
		$translations = $this->get_cell_i18n($entry);

		if (!$translations)
		{
			return;
		}

		return '<span class="translations">[' . $translations . ']</span>';
	}
}