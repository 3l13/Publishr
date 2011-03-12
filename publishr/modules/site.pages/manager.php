<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class site_pages_WdManager extends system_nodes_WdManager
{
	public function __construct($module, $tags)
	{
		global $core;

		parent::__construct($module, $tags);

		$core->document->css->add('public/manage.css');
		$core->document->js->add('public/manage.js');
	}

	protected function columns()
	{
		return parent::columns() + array
		(
			'url' => array
			(
				self::COLUMN_LABEL => null,
				self::COLUMN_CLASS => 'url'
			),

			'infos' => array
			(
				self::COLUMN_LABEL => null,
				self::COLUMN_CLASS => 'infos'
			)
		);
	}

	protected function jobs()
	{
		return parent::jobs() + array
		(
			'copy' => 'Copier'
		);
	}

	protected $mode = 'tree';
	protected $expand_highlight;

	protected function parseOptions($name)
	{
		global $core;

		$options = parent::parseOptions($name);

		$expanded = empty($options['expanded']) ? array() : $options['expanded'];

		#
		# changes
		#

		if (isset($_GET['expand']) || isset($_GET['collapse']))
		{
			$expanded = array_flip($options['expanded']);

			if (isset($_GET['expand']))
			{
				$this->expand_highlight = $_GET['expand'];

				$expanded[$_GET['expand']] = true;
			}

			if (isset($_GET['collapse']))
			{
				unset($expanded[$_GET['collapse']]);
			}

			$expanded = array_keys($expanded);
		}

		#
		# force depth 0 ids
		#

		$ids = $this->model->select('nid')->where('parentid = 0')->all(PDO::FETCH_COLUMN);
		$expanded = array_merge($expanded, $ids);

		$core->session->wdmanager['options'][$name]['expanded'] = $options['expanded'] = $expanded;

		if ($options['where'] || $options['search'])
		{
			$this->mode = 'flat';
		}
		else
		{
			$options['by'] = null;
			$options['order'] = null;
		}

		return $options;
	}

	protected function load_range(WdActiveRecordQuery $query)
	{
		global $core;

		if ($this->mode != 'tree')
		{
			return parent::load_range($query);
		}

		if ($this->tags['expanded'])
		{
			$query->where('parentid = 0 OR parentid IN (' . implode(',', $this->tags['expanded']) . ')');
		}

		$keys = $query->select('nid')->order('weight, created')->limit(null, null)->all(PDO::FETCH_COLUMN);

		if (!$keys)
		{
			return array();
		}

		$records = $this->model->find($keys);

		$tree = $this->model->nestNodes($records);

		$entries_by_ids = array();

		foreach ($tree as $entry)
		{
			$entries_by_ids[$entry->nid] = $entry;
		}

		$filtered = array();

		foreach ($tree as $entry)
		{
			if ($entry->parentid && empty($entries_by_ids[$entry->parentid]))
			{
				continue;
			}

			$filtered[] = $entry;
		}

		$records = self::flattenTree2($filtered);

		return $records;
	}

	static protected function flattenTree2($pages, $level=0)
	{
		$flatten = array();

		if (!is_array($pages))
		{
			throw new WdException('should be an array: \1', array($pages));
		}

		foreach ($pages as $page)
		{
			$page->level = $level;

			$flatten[] = $page;

			if (isset($page->children) && $page->children)
			{
				$flatten = array_merge($flatten, self::flattenTree2($page->children, $level + 1));
			}
		}

		return $flatten;
	}

	protected function getJobs()
	{
		$rc = '<div class="update" style="float: left"><button name="update">Enregistrer les modifications</button>&nbsp;</div>' . parent::getJobs();

		return $rc;
	}

	protected function getLimiter()
	{
		if ($this->mode == 'tree')
		{
			$rc  = '<div class="limiter"><span class="wdranger">';
			$rc .= '<select style="visibility: hidden"><option>&nbsp;</option></select>'; // to have the same height as the jobs div
			$rc .= 'De 1 à ' . count($this->entries) . ' sur ' . $this->count;
			$rc .= '</span></div>';

			return $rc;
		}

		return parent::getLimiter();
	}

	protected function getHeader()
	{
		if ($this->mode == 'flat')
		{
			return parent::getHeader();
		}

		$constructor_flat_id = $this->module->flat_id;

		$rc  = '<thead>';
		$rc .= '<tr>';

		foreach ($this->columns as $by => $col)
		{
			$class = isset($col[self::COLUMN_CLASS]) ? $col[self::COLUMN_CLASS] : null;

			//
			// start markup
			//

			if ($class)
			{
				$rc .= '<th class="' . $class . '">';
			}
			else
			{
				$rc .= '<th>';
			}

			$label = isset($col[self::COLUMN_LABEL]) ? $col[self::COLUMN_LABEL] : null;

			if ($label)
			{
				$label = t($by, array(), array('scope' => array($constructor_flat_id, 'manager', 'label'), 'default' => $label));
			}
			else
			{
				$label .= '&nbsp;';
			}

			$rc .= $label;
			$rc .= '</th>';
		}

		$rc .= '</tr>';
		$rc .= '</thead>';

		return $rc;
	}

	protected function getContents()
	{
		global $core;

		$view_ids = $this->module->model('contents')
		->select('pageid, content')
		->where('contentid = "body" AND editor = "view"')
		->pairs;

		$user = $core->user;
		$count = count($this->entries);

		$rc = '';

		foreach ($this->entries as $i => $entry)
		{
			$class = 'entry draggable';

			$ownership = $user->has_ownership($this->module, $entry);

			if ($ownership === false)
			{
				$class .= ' no-ownership';
			}

			if ($this->expand_highlight && $entry->parentid == $this->expand_highlight)
			{
				$class .= ' volatile-highlight';
			}

			if (isset($view_ids[$entry->nid]))
			{
				$class .= ' view';
			}

			if ($entry->pattern)
			{
				$class .= ' pattern';
			}

			if ($entry->locationid)
			{
				$class .= ' location';
			}

			#
			# create rows, with a special 'even' class for even rows
			#

			$rc .= '<tr class="' . $class . '" id="nid:' . $entry->nid . '">';

			#
			# create user defined columns
			#

			foreach ($this->columns as $tag => $opt)
			{
				$rc .= $this->get_cell($entry, $tag, $opt) . PHP_EOL;
			}

			$rc .= '</tr>';
		}

		return $rc;
	}

	protected function get_cell_title(system_nodes_WdActiveRecord $record, $property)
	{
		$rc = '';

		if ($this->mode == 'tree')
		{
			$rc .= str_repeat('<div class="indentation">&nbsp;</div>', $record->depth);
			$rc .= '<div class="handle">&nbsp;</div>';

			if (0)
			{
				$rc .= new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdElement::T_LABEL => 'w',
						WdElement::T_LABEL_POSITION => 'before',
						'name' => 'weights[' . $record->nid . ']',
						'value' => $record->weight,
						'size' => 3,
						'style' => 'border: none; background: transparent; color: green'
					)
				);

				$rc .= '&nbsp;';

				$rc .= new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdElement::T_LABEL => 'p',
						WdElement::T_LABEL_POSITION => 'before',
						'name' => 'parents[' . $record->nid . ']',
						'value' => $record->parentid,
						'size' => 3,
						'style' => 'border: none; background: transparent; color: green'
					)
				);
			}
			else
			{
				/*
				$rc .= new WdElement
				(
					WdElement::E_HIDDEN, array
					(
						'name' => 'weights[' . $entry->nid . ']',
						'value' => $entry->weight
					)
				);

				$rc .= '&nbsp;';
				*/

				$rc .= new WdElement
				(
					WdElement::E_HIDDEN, array
					(
						'name' => 'parents[' . $record->nid . ']',
						'value' => $record->parentid
					)
				);
			}
		}

		$rc .= self::modify_code(wd_entities($record->title), $record->nid, $this);

		if (0)
		{
			$rc .= ' <small style="color: green">:' . $record->nid . '</small>';
		}

		if ($this->mode == 'tree' && isset($record->depth) && $record->depth > 0 && $record->has_child)
		{
			$expanded = in_array($record->nid, $this->tags['expanded']);

			$rc .= ' <a class="ajaj treetoggle" href="?' . ($expanded ? 'collapse' : 'expand') . '=' . $record->nid . '">' . ($expanded ? '-' : '+' . $record->child_count) . '</a>';
		}

		#
		# modified
		#

		$now = time();
		$modified = strtotime($record->modified);

		if ($now - $modified < 60 * 60 * 2)
		{
			$rc .= ' <sup style="vertical-align: text-top; color: red;">Récemment modifié</sup>';
		}

		return $rc;
	}

	protected function get_cell_infos($entry)
	{
		$rc = '<label class="checkbox-wrapper navigation" title="Inclure ou exclure la page du menu de navigation principal">';

		$rc .= new WdElement
		(
			WdElement::E_CHECKBOX, array
			(
				'class' => 'navigation',
				'checked' => !empty($entry->is_navigation_excluded),
				'value' => $entry->nid
			)
		);

		$rc .= '</label>';

		#
		#
		#

		return $rc;
	}

	protected function get_cell_url($record)
	{
		global $core;

		$rc = '';

		$pattern = $record->url_pattern;

		if ($this->get(self::SEARCH) || $this->get(self::WHERE))
		{
			if (WdRoute::is_pattern($pattern))
			{
				return;
			}

			$url = $record->url;

			// DIRTY-20100507

			if ($record->location)
			{
				$location = $record->location;

				$rc .= '<a class="location" title="' . t('This page is redirected to: !title (!url)', array('!title' => $location->title, '!url' => $location->url)) . '">&nbsp;</a>';
				$rc .= '<span class="small"><a href="' . $url . '" class="left">' . $url . '</a></span>';

				return $rc;
			}

			$rc .= '<span class="small"><a href="' . $url . '" class="out left">' . $url . '</a></span>';

			return $rc;
		}

		$location = $record->location;

		if ($location)
		{
			$rc .= '<a class="location" title="' . t('This page is redirected to: !title (!url)', array('!title' => $location->title, '!url' => $location->url)) . '">&nbsp;</a>';
		}
		else if (!WdRoute::is_pattern($pattern))
		{
			$url = ($core->site_id == $record->siteid) ? $record->url : $record->absolute_url;

			$title = t('Go to the page: !url', array('!url' => $url));

			$rc .= '<a href="' . $url . '" class="view" title="' . $title . '">' . '&nbsp;' . '</a>';
		}

		return $rc;
	}
}