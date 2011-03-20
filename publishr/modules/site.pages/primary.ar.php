<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
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

	public $parentid;
	public $locationid;
	public $pattern;
	public $weight;
	public $template;
	public $label;
	public $is_navigation_excluded;

	public $url_part;
	public $url_variables = array();
	public $node;

	public function __construct()
	{
		if (empty($this->language))
		{
			unset($this->language);
		}

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

	protected function __get_language()
	{
		return $this->siteid ? $this->site->language : null;
	}

	/**
	 * Returns the previous online sibling for the page.
	 *
	 * @return site_pages_WdActiveRecord|false The previous sibling, or false if there is none.
	 *
	 * @see system_nodes_WdActiveRecord::__get_previous()
	 */
	protected function __get_previous()
	{
		return $this->model()
		->where('is_online = 1 AND nid != ? AND parentid = ? AND siteid = ? AND weight <= ?', $this->nid, $this->parentid, $this->siteid, $this->weight)
		->order('weight desc, created desc')->one();
	}

	/**
	 * Returns the next online sibling for the page.
	 *
	 * @return site_pages_WdActiveRecord|false The next sibling, or false if there is none.
	 *
	 * @see system_nodes_WdActiveRecord::__get_next()
	 */
	protected function __get_next()
	{
		return $this->model()
		->where('is_online = 1 AND nid != ? AND parentid = ? AND siteid = ? AND weight >= ?', $this->nid, $this->parentid, $this->siteid, $this->weight)
		->order('weight, created')->one();
	}

	/**
	 * Returns the URL of the page.
	 *
	 * @return string
	 */
	protected function __get_url()
	{
		if ($this->location)
		{
			return $this->location->url;
		}

		if ($this->is_home)
		{
			return $this->url_pattern;
		}

		$url = null;
		$pattern = $this->url_pattern;

		if (WdRoute::is_pattern($pattern))
		{
			global $page;

			if ($this->url_variables)
			{
				$url = WdRoute::format($pattern, $this->url_variables);

//				wd_log('URL %pattern rescued using URL variables', array('%pattern' => $pattern));
			}
			else if (isset($page) && $page->url_variables)
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


		if (0)
		{
			$site = $this->site;
			$once = 1;

			$pattern = '^(' . ($site->subdomain ? preg_quote($site->subdomain, '#') : '[^\.]+') . ')'
			. '\.' . ($site->domain ? preg_quote($site->domain, '#') : '([^\.]+)')
			. '\.' . ($site->tld ? preg_quote($site->tld, '#') : '([^\.]+)$');

			if (!preg_match('#' . $pattern . '#', $_SERVER['HTTP_HOST'], $matches))
			{
				$url = $site->url . $url;
			}

//			var_dump($_SERVER, $core->site, $pattern, $matches);
		}

		return $url;
	}

	/**
	 * Returns the absulte URL of the pages.
	 *
	 * @return string The absolute URL of the page.
	 *
	 * @see site_pages_view_WdHooks::__get_absolute_url()
	 */
	protected function __get_absolute_url()
	{
		global $core;

		$site = $core->site;
		$path = $site->path;

		return substr($site->url, 0, -strlen($path)) . $this->url;
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
		global $core;

		if ($this->is_home)
		{
			$site = $this->site;

			if (!$site && $core->working_site)
			{
				$site = $core->working_site;
			}

			return $site ? $site->path . '/' : '/';
			//return $this->site->url;
		}

		// COMPAT

		if (!$this->siteid)
		{
			throw new WdException("Page %title (%nid) has no associated site", array('%title' => $this->title, '%nid' => $this->nid));
		}

		//

		$parent = $this->parent;

		$rc = ($parent ? $parent->url_pattern : $this->site->path . '/') . ($this->pattern ? $this->pattern : $this->slug);

		if ($this->has_child)
		{
			$rc .= '/';
		}
		else
		{
			$template = $this->template;

			$pos = strrpos($template, '.');
		 	$extension = substr($template, $pos);

		 	$rc .= $extension;
		}

//		wd_log('page: \1, has_child: \2 (\4), url_pattern: \3', array($this->title, $this->has_child, $rc, $this->__get_has_child()));

		return $rc;
	}

	/**
	 * Checks if the page record is the home page.
	 *
	 * A page is considered a home page when the page has no parent and its weight value is zero.
	 *
	 * @return bool true if the page record is the home page, false otherwise.
	 */
	protected function __get_is_home()
	{
		return (!$this->parentid && $this->weight == 0);
	}

	/**
	 * Checks if the page record is the active page.
	 *
	 * The global variable `page` must be defined in order to identify the page record.
	 *
	 * @return bool true if the page record is the active page, false otherwise.
	 */
	protected function __get_is_active()
	{
		global $page;

		return $page->nid == $this->nid;
	}

	/**
	 * Returns the location target for the page record.
	 *
	 * @return site_pages_WdActiveRecord|null The location target, or null if there is none.
	 */
	protected function __get_location()
	{
		return $this->locationid ? $this->model()->find($this->locationid) : null;
	}

	static private $home_by_siteid;

	/**
	 * Returns the home page for the page record.
	 *
	 * @return site_pages_WdActiveRecord
	 */
	protected function __get_home()
	{
		$siteid = $this->siteid;

		if (empty(self::$home_by_siteid[$siteid]))
		{
			$model = $this->model();
			$homeid = $model->select('nid')->where('parentid = 0 AND siteid = ?', $siteid)->order('weight')->rc;

			self::$home_by_siteid[$siteid] = $model[$homeid];
		}

		return self::$home_by_siteid[$siteid];
	}

	/**
	 * Return the parent page for this page, or null if the page has no parent.
	 */

	protected function __get_parent()
	{
		return $this->parentid ? $this->model()->find($this->parentid) : null;
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
		return $this->model()->where('is_online = 1 AND parentid = ?', $this->nid)->order('weight, created')->all;
	}

	/**
	 * Returns the page's children that are part of the navigation.
	 */

	protected function __get_navigation_children()
	{
		return $this->model()->where('is_online = 1 AND is_navigation_excluded = 0 AND pattern = "" AND parentid = ?', $this->nid)->order('weight, created')->all;
	}

	static private $childrens_ids_by_parentid;

	protected function __get_has_child()
	{
		if (!self::$childrens_ids_by_parentid)
		{
			self::$childrens_ids_by_parentid = $this->model()->select('parentid, GROUP_CONCAT(nid)')->group('parentid')->pairs;
		}

		return isset(self::$childrens_ids_by_parentid[$this->nid]);
	}

	/**
	 * Returns the number of child for this page.
	 */

	protected function __get_child_count()
	{
		if (!$this->has_child)
		{
			return 0;
		}

		return substr_count(self::$childrens_ids_by_parentid[$this->nid], ',') + 1;
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
		global $core;

		if ($core->user->is_guest() && $this->site->status != 1)
		{
			return false;
		}

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
	 * Keys of the array are the contentid, values are the contents objects.
	 */

	protected function __get_contents()
	{
		global $core;

		$entries = $core->models['site.pages/contents']->where(array('pageid' => $this->nid));
		$contents = array();

		foreach ($entries as $entry)
		{
			$contents[$entry->contentid] = $entry;
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

	protected function __get_css_class()
	{
		global $core, $page;

		$class = "page-id-{$this->nid} page-slug-{$this->slug}";

		if ($this->home->nid == $this->nid)
		{
			$class .= ' home';
		}

		//TODO-20101213: add a "breadcrumb" class to recognize the actual active page from the pages of the breadcrumb

		if (!empty($this->is_active) || (isset($page) && $page->nid == $this->nid))
		{
			$class .= ' active';
		}

		if (isset($this->node))
		{
			$node = $this->node;

			$class .= " node-id-{$node->nid} node-constructor-" . wd_normalize($node->constructor);
		}

		$class .= ' template-' . preg_replace('#|.(html|php)#', '', $this->template);

		return $class;
	}

	/**
	 * Return the description for the page.
	 */

	// TODO-20101115: these should be methods added by the "firstposition' module

	protected function __get_description()
	{
		return $this->metas['description'];
	}

	protected function __get_document_title()
	{
		return $this->metas['document_title'] ? $this->metas['document_title'] : $this->title;
	}
}