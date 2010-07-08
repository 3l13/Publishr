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
	const TEMPLATE = 'template';
	const LABEL = 'label';
	const IS_NAVIGATION_EXCLUDED = 'is_navigation_excluded';

	public function __construct()
	{
		if (empty($this->label))
		{
			unset($this->label);
		}

		if (empty($this->template))
		{
			unset($this->template);
		}

		parent::__construct();
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

	/**
	 * Returns the URL of the page.
	 *
	 * If the page is an home page (its `is_home` is true), the URL is created according to the
	 * language of the page e.g. '/fr/' or '/' if the page has no language defined.
	 *
	 * @see /wdpublisher/protected/modules/system.nodes/system_nodes_WdActiveRecord::__get_url()
	 */

	protected function __get_url()
	{
		if ($this->location)
		{
			return $this->location->url;
		}

		if ($this->is_home)
		{
			return '/' . ($this->language ? $this->language . '/' : '');
		}

		$url = null;
		$pattern = $this->url_pattern;

		if (strpos($pattern, '<') !== false)
		{
			global $page;

			if (isset($this->url_variables))
			{
				$url = WdRoute::format($pattern, $this->url_variables);

//				wd_log('URL %pattern rescued using URL variables', array('%pattern' => $pattern));
			}
			else if (isset($page) && isset($page->url_variables))
			{
				$url = WdRoute::format($pattern, $page->url_variables);

//				wd_log("URL pattern %pattern was resolved using current page's variables", array('%pattern' => $pattern));
			}
			else
			{
				/*
				WdDebug::trigger
				(
					'The url for this page has a pattern that cannot be resolved: %pattern !page', array
					(
						'%pattern' => $pattern, '!page' => $this
					)
				);
				*/

				$url = '#url-pattern-could-not-be-resolved';
			}
		}
		else
		{
			$url = $pattern;
		}

		return $url;
	}

	/**
	 * Return the absulte URL for this pages.
	 *
	 * @see /wdpublisher/protected/modules/system.nodes/system_nodes_WdActiveRecord::__get_absolute_url()
	 */

	protected function __get_absolute_url()
	{
		return 'http://' . $_SERVER['HTTP_HOST'] . $this->url;
	}

	public function translation($language=null)
	{
		$translation = parent::translation($language);

		if ($translation->nid != $this->nid && isset($this->url_variables))
		{
			$translation->url_variables = $this->url_variables;
		}

		return $translation;
	}

	protected function __get_translations()
	{
		$translations = parent::__get_translations();

		if (!$translations || empty($this->url_variables))
		{
			return $translations;
		}

		foreach ($translations as $translation)
		{
			$translation->url_variables = $this->url_variables;
		}

		return $translations;
	}

	// TODO-20100706: Shouldn't url_pattern be null if there was no pattern in the path ? We
	// wouldn't have to check for '<' to know if the URL has a pattern, on the other hand we would
	// have to do two pass each time we try to get the URL.

	protected function __get_url_pattern()
	{
		$parent = $this->parent;

		$rc = ($parent ? $parent->url_pattern : '/') . ($this->pattern ? $this->pattern : $this->slug);

		if (!$this->has_child)
		{
			$template = $this->template;

			$pos = strrpos($template, '.');
		 	$extension = substr($template, $pos);

		 	$rc .= $extension;
		}
		else
		{
			$rc .= '/';
		}

//		wd_log('page: \1, has_child: \2 (\4), url_pattern: \3', array($this->title, $this->has_child, $rc, $this->__get_has_child()));

		return $rc;
	}

	/**
	 * Returns wheter or not the page is an home page.
	 *
	 * A page is consiredred on home page when the following conditions are matched :
	 *
	 * 1. The page has no parent
	 * 2. The weight of the page is 0 or the slug of the page matches one of the languages defined.
	 *
	 */

	protected function __get_is_home()
	{
		if (!$this->parentid && ($this->weight == 0 || in_array($this->slug, WdLocale::$languages)))
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns the page object to which this page is relocated.
	 */

	protected function __get_location()
	{
		return $this->locationid ? $this->model()->load($this->locationid) : null;
	}

	/**
	 * Cache for home pages by language.
	 *
	 * @var array
	 */

	static private $home_by_language;

	/**
	 * Returns the home page for this page.
	 */

	protected function __get_home()
	{
		$language = $this->language;

		if (empty(self::$home_by_language[$language]))
		{
			self::$home_by_language[$language] = $this->model()->select
			(
				'nid', 'WHERE parentid = 0 AND language = ? ORDER BY weight', array
				(
					$this->language
				)
			)
			->fetchColumnAndClose();
		}

		return $this->model()->load(self::$home_by_language[$language]);
	}

	/**
	 * Return the parent page for this page, or null if the page has no parent.
	 */

	protected function __get_parent()
	{
		return $this->parentid ? $this->model()->load($this->parentid) : null;
	}

	/**
	 * Return the online children page for this page.
	 *
	 * TODO-20100629: The `children` virtual property should return *all* the children for the page,
	 * we should create a `online_children` virtual property that returns only _online_ children,
	 * or maybe a `accessible_children` virtual property ?
	 */

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

	protected function __get_has_child()
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

	/**
	 * Cache for the `child_count` virtual property.
	 * @var array
	 */

	static private $child_count_by_nid;

	/**
	 * Returns the number of child for this page.
	 */

	protected function __get_child_count()
	{
		if (!self::$child_count_by_nid)
		{
			self::$child_count_by_nid = $this->model()->select
			(
				array('parentid', 'count(nid)'), 'GROUP BY parentid'
			)
			->fetchPairs();
		}

		return isset(self::$child_count_by_nid[$this->nid]) ? self::$child_count_by_nid[$this->nid] : 0;

		/*
		wd_log('child: \1', array($child_count_by_id));


		return $this->model()->select
		(
			'count(nid)', 'WHERE parentid = ?', array
			(
				$this->nid
			)
		)
		->fetchColumnAndClose();
		*/
	}

	/**
	 * Returns the label for the page.
	 *
	 * This function is only called if no label is defined, in which case the title of the page is
	 * returned instead.
	 */

	protected function __get_label()
	{
		return $this->title;
	}

	/**
	 * Returns the depth level of this page in the navigation tree.
	 */

	protected function __get_depth()
	{
		return $this->parent ? $this->parent->depth + 1 : 0;
	}

	/**
	 * Returns if the page is accessible or not in the navigation tree.
	 */

	protected function __get_is_accessible()
	{
		return ($this->parent && !$this->parent->is_accessible) ? false : $this->is_online;
	}

	protected function __get_template()
	{
		if (isset($this->layout))
		{
			return $this->layout;
		}

		if ($this->is_home)
		{
			return 'home.html';
		}
		else if ($this->parent && !$this->parent->is_home)
		{
			return $this->parent->template;
		}

		return 'page.html';
	}

	/**
	 * Returns the contents of the page as an array.
	 *
	 * Keys of the array are the contentsid, values are the contents objects.
	 */

	protected function __get_contents()
	{
		$entries = $this->model('site.pages/contents')->loadAll
		(
			'WHERE pageid = ?', array
			(
				$this->nid
			)
		);

		$contents = array();

		foreach ($entries as $entry)
		{
			$contents[$entry->contentsid] = $entry;
		}

		return $contents;
	}

	/**
	 * Returns the body of this page.
	 *
	 * The body is the page's contents object with the 'body' identifier.
	 */

	protected function __get_body()
	{
		$contents = $this->contents;

		return isset($contents['body']) ? $contents['body'] : null;
	}
}