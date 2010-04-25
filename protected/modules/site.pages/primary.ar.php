<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class site_pages_WdActiveRecord extends system_nodes_WdActiveRecord
{
	const PARENTID = 'parentid';
	const LOCATIONID = 'locationid';
	const PATTERN = 'pattern';
	const WEIGHT = 'weight';
	const LAYOUT = 'layout';
	const LABEL = 'label';
	const IS_NAVIGATION_EXCLUDED = 'is_navigation_excluded';

	public function __construct()
	{
		if (empty($this->label))
		{
			unset($this->label);
		}

		parent::__construct();
	}

	protected function __get_is_index()
	{
		if (!$this->parentid && ($this->weight == 0 || in_array($this->slug, WdLocale::$languages)))
		{
			return true;
		}

		return false;
	}

	protected function __get_location()
	{
		if (!$this->locationid)
		{
			return;
		}

		return $this->model()->load($this->locationid);
	}

	protected function __get_url()
	{
		if ($this->location)
		{
			return $this->location->url;
		}

		if ($this->is_index && count(WdLocale::$languages) == 1)
		{
			return '/';
		}

		$url = null;
		$urlPattern = $this->urlPattern;

		if (strpos($urlPattern, '<') !== false)
		{
			global $page;

			if (isset($this->urlVariables))
			{
				$url = $this->entryURL((object) $this->urlVariables);

				//wd_log('URL %pattern rescued using URL variables', array('%pattern' => $urlPattern));
			}
			else if (isset($page) && isset($page->urlVariables))
			{
				$url = $this->entryURL((object) $page->urlVariables);

				wd_log('URL %pattern rescued using current page variables', array('%pattern' => $urlPattern));
			}
			else
			{
				/*
				WdDebug::trigger
				(
					'The url for this page has a pattern that cannot be resolved: %pattern !page', array
					(
						'%pattern' => $urlPattern, '!page' => $this
					)
				);
				*/
				
				$url = '#url-pattern-could-not-be-resolved';
			}
		}
		else
		{
			$url = $urlPattern;
		}

		return $url;
	}

	protected function __get_urlPattern()
	{
		$parent = $this->parent;

		$rc = ($parent ? $parent->urlPattern : '/') . ($this->pattern ? $this->pattern : $this->slug);

		if (!$this->hasChild)
		{
			$pos = strrpos($this->layout, '.');
		 	$extension = substr($this->layout, $pos);

		 	$rc .= $extension;
		}
		else
		{
			$rc .= '/';
		}

		return $rc;
	}

	protected function __get_parent()
	{
		return $this->parentid ? $this->model()->load($this->parentid) : null;
	}

	protected function __get_children()
	{
		return self::model()->loadAll
		(
			'WHERE is_online = 1 AND parentid = ? ORDER BY weight, created', array
			(
				$this->nid
			)
		)
		->fetchAll();
	}

	protected function __get_hasChild()
	{
		$rc = $this->model()->select
		(
			'nid', 'WHERE parentid = ? LIMIT 1', array
			(
				$this->nid
			)
		)
		->fetchColumnAndClose();

		return !empty($rc);
	}

	protected function __get_childCount()
	{
		return $this->model()->select
		(
			'count(nid)', 'WHERE parentid = ?', array
			(
				$this->nid
			)
		)
		->fetchColumnAndClose();
	}

	protected function __get_previous()
	{
		return $this->model()->loadRange
		(
			0, 1, 'WHERE is_online = 1 AND nid != ? AND parentid = ? AND weight <= ? ORDER BY weight DESC, created DESC', array
			(
				$this->nid,
				$this->parentid,
				$this->weight
			)
		)
		->fetch();
	}

	protected function __get_next()
	{
		return $this->model()->loadRange
		(
			0, 1, 'WHERE is_online = 1 AND nid != ? AND parentid = ? AND weight >= ? ORDER BY weight, created', array
			(
				$this->nid,
				$this->parentid,
				$this->weight
			)
		)
		->fetch();
	}

	protected function __get_label()
	{
		return $this->title;
	}

	protected function __get_depth()
	{
		return $this->parentid ? $this->parent->depth + 1 : 0;
	}

	public function entryURL($entry)
	{
		$url = '';
		
		$parsed = WdRoute::parse($this->urlPattern);
		
		foreach ($parsed[0] as $i => $value)
		{
			$url .= ($i % 2) ? urlencode($entry->$value[0]) : $value;
		}

		return $url;
	}
}