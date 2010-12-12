<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class site_pages_WdManager extends system_nodes_WdManager
{
	public function __construct($module, $tags)
	{
		parent::__construct($module, $tags);

		global $document;

		$document->css->add('public/manage.css');
		$document->js->add('public/manage.js');
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

		global $core;

		$core->session->wdmanager['options'][$name]['expanded'] = $options['expanded'] = $expanded;

		#
		#
		#

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

	protected function loadRange($offset, $limit, array $where, $order, array $params)
	{
		if ($this->mode == 'tree' && $this->tags['expanded'])
		{
			$where[] = '(parentid = 0 OR parentid IN (' . implode(',', $this->tags['expanded']) . '))';
		}

		$arq = $this->model->where(implode(' AND ', $where), $params);

		if ($this->mode == 'tree')
		{
			if (1)
			{
	//			wd_log_time('lets go baby');

				$entries = $arq->offset($offset)->order('weight, created')->all;

	//			wd_log_time('loaded');

				$tree = self::entriesTreefy($entries);

	//			wd_log_time('treefyied');

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

	//			wd_log_time('filtered');

				$entries = self::flattenTree2($filtered);

	//			wd_log_time('flattened');
			}
			else
			{
				$entries = $this->model->loadAllNested();

				$entries = site_pages_WdModel::levelNodesById($entries);
			}
		}
		else
		{
			$entries = $arq->limit($offset, $limit)->order(substr($order, 9))->all;
		}

		return $entries;
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

	/*
	 * La transformation en arbre est assez simple si l'on se sert du référencement
	 * des objets.
	 */

	static function entriesTreefy($entries)
	{
		#
		# we need to build an array of parents so that the key can be used as parentid
		#

		$parents = array();

		foreach ($entries as $entry)
		{
			$entry->children = array();

			$parents[$entry->nid] = $entry;
		}

		#
		#
		#

		$tree = array();

		foreach ($parents as $entry)
		{
			if (!$entry->parentid || empty($parents[$entry->parentid]))
			{
				$tree[] = $entry;

				continue;
			}

			$entry->parent = $parents[$entry->parentid];
			$entry->parent->children[] = $entry;
		}

		return $tree;
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

			$label = isset($col[self::COLUMN_LABEL]) ? t($col[self::COLUMN_LABEL], array(), array('scope' => 'manager.th')) : '&nbsp;';

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

			/*
			if ($i + 1 == $count)
			{
				$class .= ' last';
			}
			*/

			if ($this->expand_highlight && $entry->parentid == $this->expand_highlight)
			{
				$class .= ' volatile-highlight';
			}

			if (isset($view_ids[$entry->nid]))
			{
				$class .= ' view';
				/*

				$view_type = explode('/', $view_ids[$entry->nid]);

				wd_log('exploded: \1', array($view_type));

				if (count($view_type) == 2)
				{
					$class .= '-' . $view_type[1];
				}
				*/
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

			if ($this->mode == 'flat')
			{
				#
				# operations
				#

				$rc .= '<td class="operations">';
				$rc .= '<a href="#operations">&nbsp;</a>';
				$rc .= '</td>';
			}

			#
			# end row
			#

			$rc .= '</tr>';
		}

		return $rc;
	}

	protected function get_cell_title($entry, $tag)
	{
		$rc = '';

		if ($this->mode == 'tree')
		{
			$rc .= str_repeat('<div class="indentation">&nbsp;</div>', $entry->depth);
			$rc .= '<div class="handle">&nbsp;</div>';

			if (0)
			{
				$rc .= new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdElement::T_LABEL => 'w',
						WdElement::T_LABEL_POSITION => 'before',
						'name' => 'weights[' . $entry->nid . ']',
						'value' => $entry->weight,
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
						'name' => 'parents[' . $entry->nid . ']',
						'value' => $entry->parentid,
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
						'name' => 'parents[' . $entry->nid . ']',
						'value' => $entry->parentid
					)
				);
			}
		}

		$rc .= self::modify_code(wd_entities($entry->title), $entry->nid, $this);

		#
		# language
		#

		$language = $entry->language;

		if ($language)
		{
			if ($entry->parent)
			{
				if ($language != $entry->parent->language)
				{
					$rc .= ' <span class="language warn">:' . ($language ? $language : 'langue manquante') . '</span>';
				}
			}
			else
			{
				$rc .= ' <span class="language">:' . $language . '</span>';
			}
		}

		if (0)
		{
			$rc .= ' <small style="color: green">:' . $entry->nid . '</small>';
		}

		if ($this->mode == 'tree' && isset($entry->depth) && $entry->depth > 0 && $entry->has_child)
		{
			$expanded = in_array($entry->nid, $this->tags['expanded']);

			$rc .= ' <a class="ajaj treetoggle" href="?' . ($expanded ? 'collapse' : 'expand') . '=' . $entry->nid . '">' . ($expanded ? '-' : '+' . $entry->child_count) . '</a>';
		}

		#
		# modified
		#

		$now = time();
		$modified = strtotime($entry->modified);

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

	protected function get_cell_url($entry)
	{
		$rc = '';

		$pattern = $entry->url_pattern;

		if ($this->get(self::SEARCH) || $this->get(self::WHERE))
		{
			if (strpos($pattern, '<') !== false)
			{
				return;
			}

			// DIRTY-20100507

			if ($entry->location)
			{
				$location = $entry->location;

				$rc .= '<a class="location" title="Cette page est redirigée vers&nbsp;: ' . wd_entities($location->title) . ' (' . $location->url . ')">&nbsp;</a>';
				$rc .= '<span class="small"><a href="' . $entry->url . '" class="left">' . $entry->url . '</a></span>';

				return $rc;
			}

			$rc .= '<span class="small"><a href="' . $entry->url . '" class="out left">' . $entry->url . '</a></span>';

			return $rc;
		}

		$location = $entry->location;

		if ($location)
		{
			$rc .= '<a class="location" title="Cette page est redirigée vers&nbsp;: ' . wd_entities($location->title) . ' (' . $location->url . ')">&nbsp;</a>';
		}
		else if (strpos($pattern, '<') === false)
		{
			$url = $entry->url;

			$title = t('Aller à la page : !url', array('!url' => $url));

			$rc .= '<a href="' . $url . '" class="view" title="' . $title . '">' . '&nbsp;' . '</a>';
		}

		return $rc;
	}
}