<?php

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
		if (!$this->parentid && ($this->weight == 0 || in_array($this->pattern, WdLocale::$languages)))
		{
			return true;
		}

		return false;
	}

	protected function __get_location()
	{
		if (!$this->locationid)
		{
			return null;
		}

		return $this->model()->load($this->locationid);
	}

	protected function __get_url()
	{
		// FIXME-20100226: this is the right rule, but the module fails to save weights correctly

		/*
		if (!$this->parentid && $this->weight == 0)
		{
			return '/';
		}
		*/

		$urlPattern = $this->urlPattern;
		$url = null;

		if (strpos($urlPattern, '<') !== false)
		{
			global $page;

			if (isset($page))
			{
				$url = $this->patternToURL($urlPattern, (object) $page->url_vars);

				wd_log('URL %pattern rescued using current page', array('%pattern' => $urlPattern));
			}
			else
			{
				WdDebug::trigger
				(
					'The url for this page has a pattern: %pattern !page', array
					(
						'%pattern' => $urlPattern, '!page' => $this
					)
				);
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

		return ($parent ? $parent->urlPattern : '') . '/' . $this->pattern;
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

	protected function patternToURL($pattern, $entry)
	{
		$parsed = WdRoute::parse($this->urlPattern);

		$url = '';

		foreach ($parsed[0] as $i => $value)
		{
			if (!($i % 2))
			{
				$url .= $value;

				continue;
			}

			$url .= urlencode($entry->$value[0]);
		}

		return $url;
	}

	static protected $parseCache = array();

	public function entryURL($entry)
	{
		$nid = $this->nid;

		if (!isset(self::$parseCache[$nid]))
		{
			self::$parseCache[$nid] = WdRoute::parse($this->urlPattern);
		}

		$parsed = self::$parseCache[$nid];

		$url = '';

		foreach ($parsed[0] as $i => $value)
		{
			if (!($i % 2))
			{
				$url .= $value;

				continue;
			}

			$url .= urlencode($entry->$value[0]);
		}

		return $url;
	}
}