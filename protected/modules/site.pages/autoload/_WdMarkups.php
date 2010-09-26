<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class site_pages_WdMarkups extends patron_markups_WdHooks
{
	static protected $module;

	static protected function module($name='site.pages')
	{
		if (!self::$module)
		{
			global $core;

			self::$module = $core->getModule($name);
		}

		return self::$module;
	}

	static protected function model($name='site.pages')
	{
		return parent::model($name);
	}

	static public function contents(array $args, WdPatron $patron, $template)
	{
		global $page;

		$render = $args['render'];

		if ($render == 'none')
		{
			return;
		}

		$pageid = $page->nid;
		$contentsid = $args['id'];
		$contents = array_key_exists($contentsid, $page->contents) ? $page->contents[$contentsid] : null;

		if (!$contents && !empty($args['inherit']))
		{
//			wd_log('Contents %id is not defined for page %title, but is inherited, searching for heritage...', array('%id' => $contentsid, '%title' => $page->title));

			$node = $page->parent;

			while ($node)
			{
				$node_contents = $node->contents;

				if (empty($node_contents[$contentsid]))
				{
					$node = $node->parent;

					continue;
				}

				$contents = $node_contents[$contentsid];

				break;
			}

			#
			# maybe the home page define the contents, but because the home page is not the parent
			# of pages on single language sites, we have to check it now.
			#

			if (!$contents/* DIRTY:MULTISITE && count(WdLocale::$languages) == 1*/)
			{
				$node_contents = $page->home->contents;

//				wd_log('... try with home page %title', array('%title' => $page->title));

				if (isset($node_contents[$contentsid]))
				{
					$contents = $node_contents[$contentsid];
				}
			}

//			wd_log('... and found: \1', array($contents));
		}

		$class = isset($args['editor']) ? $args['editor'] . '_WdEditorElement' : null;

		if ($contents === null && isset($args['default']))
		{
			try
			{
				$contents = (string) call_user_func(array($class, 'render'), $args['default']);
			}
			catch (Exception $e)
			{
				return $patron->error($e->getMessage());
			}
		}
		else if ($template && $contents)
		{
			$contents = $contents->render();
		}

		if ($template && ($contents === null || $contents === false))
		{
			return;
		}

		return $template ? $patron->publish($template, $contents) : (string) $contents;
	}

	/**
	 * Returns the translations available for a page.
	 *
	 * @param WdHook $hook
	 * @param WdPatron $patron
	 * @param unknown_type $template
	 */

	static public function translations(array $args, WdPatron $patron, $template)
	{
		$page = $args['select'];
		$page_language = $page->language;

		if (!$page_language)
		{
			return;
		}

		$translations = $page->translations;

		foreach ($translations as $i => $translation)
		{
			if ($translation->is_accessible)
			{
				continue;
			}

			unset($translations[$i]);
		}

		if (!$translations)
		{
			return;
		}

		return $patron->publish($template, $translations);
	}

	static public function menu(array $args, WdPatron $patron, $template)
	{
		$select = $args['select'];

		//$db_count = $stats['queries']['primary'];

		// TODO-20100323: Now that the organize.lists module brings custom menus, the markups needs
		// a complete overhaul. We need to find a commun ground between _lists_ and the navigation
		// menu.

		if ($select)
		{
			$menu = null;

			try
			{
				$menu = self::model('organize.lists')->loadRange
				(
					0, 2, 'WHERE title = ? OR slug = ? AND scope = "site.pages" AND (language = ? OR language = "") ORDER BY language DESC', array
					(
						$select, $select, WdLocale::$language
					)
				)
				->fetchAndClose();
			}
			catch (Exception $e) {}

			if (!$menu)
			{
				return;
			}

			$entries = $menu->nodes;
		}
		else
		{
			$parentid = $args['parent'];

			if (!$parentid && count(WdLocale::$languages) > 1)
			{
				$parentid = '/' . WdLocale::$language;
			}

			$parentid = $parentid ? self::resolveParent($parentid) : 0;

			if ($parentid === false)
			{
				return $patron->error('Unknown parent: %parent', array('%parent' => $parentid));
			}

			if (1)
			{
				$entries = self::model()->loadAll
				(
					'WHERE is_online = 1 AND is_navigation_excluded = 0 AND pattern = "" AND parentid = ? ORDER BY weight, created', array
					(
						$parentid
					)
				)
				->fetchAll();
			}
			else
			{
				wd_log_time('load nested start');

				$entries = self::model()->loadAllNested($siteid, $parentid, $args['nest']);

				wd_log_time('load nested done: \1', array($entries));

				return $entries;
			}
		}

		if (!$entries)
		{
			return;
		}

		global $page;

		$active_pages = array();

		$active = $page;

		while ($active)
		{
			$active_pages[$active->nid] = $active;

			$active = $active->parent;
		}

		// TODO-20100323: get rid of `active` and keep `is_active`

		foreach ($entries as $entry)
		{
			$entry->active = $entry->is_active = isset($active_pages[($entry instanceof organize_lists_nodes_WdActiveRecord) ? $entry->node->nid : $entry->nid]);
		}

		if ($template)
		{
			return $patron->publish($template, $entries);
		}

		$nest = $args['nest'];

		$rc =  self::menu_builder($entries, $nest);

		//wd_log('building menu took \1 db queries', array($stats['queries']['primary'] - $db_count));

		return $rc;
	}

	static public function menu_builder($entries, $nest=true, $level=1)
	{
		global $page;

		$active_pages = array();

		$active = $page;

		while ($active)
		{
			$active->is_active = true;
			$active = $active->parent;
		}

		$rc = null;

		foreach ($entries as $entry)
		{
			if ($entry->pattern || $entry->is_navigation_excluded)
			{
				continue;
			}

			$rc .= '<li';

			if (isset($entry->is_active))
			{
				$rc .= ' class="active"';
			}

			$rc .= '>';

			$rc .= '<a href="' . $entry->url . '">' . wd_entities($entry->label) . '</a>';

			$children = self::model()->loadAll
			(
				'WHERE is_online = 1 AND is_navigation_excluded = 0 AND pattern = "" AND parentid = ? ORDER BY weight, created', array
				(
					$entry->nid
				)
			)
			->fetchAll();

			if (($nest === true || $level < $nest) && $children)
			{
				$rc .= self::menu_builder($children, $nest, $level + 1);
			}

			$rc .= '</li>';
		}

		if ($rc)
		{
			$rc = '<ol class="menu lv' . $level . '">' . $rc . '</ol>';
		}

		return $rc;
	}

	static public function breadcrumb(array $args, WdPatron $patron, $template)
	{
		global $page;

		$node = $page;
		$links = array();

		while ($node)
		{
			$url = $node->url;
			$label = $node->label;
			$label = wd_shorten($label, 80);
			$label = wd_entities($label);

			$links[] = $links ? '<a href="' . $url . '">' . $label . '</a>' : '<strong>' . $label . '</strong>';

			if ($node->is_home)
			{
				break;
			}

			$node = $node->parent;
		}

		if (!$node)
		{
			#
			# $node is empty when the loop ended on a non _home_ page. We need to add the home page
			# to the links array.
			#

			$home = $page->home;


			$links[] = '<a href="' . $home->url . '">' . wd_entities($home->label) . '</a>';
		}

		$links = array_reverse($links);

		if ($template)
		{
			return $patron->publish($template, $links);
		}

		$rc = '<ol id="breadcrumb">';

		foreach ($links as $i => $link)
		{
			$rc .= '<li>';

			if ($i)
			{
				$rc .= '<span class="separator"> › </span>';
			}

			$rc .= $link;
			$rc .= '</li>';
		}

		$rc .= '</ol>';

		return $rc;
	}

	static public function sitemap(array $args, WdPatron $patron, $template)
	{
		$parentid = $args['parent'];

//		wd_log('sitemap parentid: \1', array($parentid));

		if (!$parentid && count(WdLocale::$languages) > 1)
		{
			$parentid = '/' . WdLocale::$language;
		}

//		wd_log('sitemap 2 parentid: \1', array($parentid));

		if ($parentid && is_string($parentid))
		{
			$parentid = $parentid ? self::resolveParent($parentid) : true;
		}

		if ($parentid === null)
		{
			$parentid = 0;
		}

		$maxnest = $args['nest'];

		return self::sitemap_callback($parentid, $maxnest);
	}

	static protected function sitemap_callback($parentid, $maxnest=false, $level=1)
	{
		$parent = null;

		if (is_object($parentid))
		{
			$parent = $parentid;
			$parentid = $parent->nid;
		}

		$children = self::model()->loadAll
		(
			'WHERE is_online = 1 AND parentid = ? AND pattern = "" ORDER BY weight, created', array
			(
				$parentid
			)
		)
		->fetchAll();

		if (!$children)
		{
			return;
		}

		$rc = '';
		$pad = str_repeat("\t", $level + 1);

		foreach ($children as $child)
		{
			if ($parent)
			{
				$child->parent = $parent;
			}

			$rc .= $pad . '<li><a href="' . $child->url . '">' . $child->label . '</a>' . PHP_EOL;

			if ($maxnest === false || $level < $maxnest)
			{
				$rc .= self::sitemap_callback($child, $maxnest, $level + 1);
			}

			$rc .= $pad . '</li>' . PHP_EOL;
		}

		$rc = str_repeat("\t", $level) . '<ul class="level' . $level . '">' . PHP_EOL . $rc . str_repeat("\t", $level) . '</ul>';

		return $rc;
	}

	static protected function resolveParent($parentid)
	{
//		wd_log('resolve parentid: \1', array($parentid));

		if (!is_numeric($parentid))
		{
			$parent = self::model()->loadByPath($parentid);

			if (!$parent)
			{
				return null;
			}

			$parentid = $parent->nid;
		}

		return $parentid;
	}

	static public function call_view(array $args, WdPatron $patron, $template)
	{
		$name = $args['name'];
		$render = view_WdEditorElement::render($name);

		if ($template)
		{
			return $patron->publish($template, $render);
		}

		$name = wd_normalize($name);

		return '<div id="' . $name . '">' . $render . '</div>';
	}
}