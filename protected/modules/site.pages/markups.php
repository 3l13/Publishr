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

	static public function contents(WdHook $hook, WdPatron $patron, $template)
	{
		global $page;

		$pageid = $page->nid;
		$contentsid = $hook->args['id'];

		$contents = self::model('site.pages/contents')->loadRange
		(
			0, 1, 'WHERE pageid = ? AND contentsid = ?', array
			(
				$pageid,
				$contentsid
			)
		)
		->fetchAndClose();

		/*
		if (!$contents)
		{
			if (isset($hook->args['default']))
			{
				$class = $hook->args['editor'] . '_WdEditorElement';

				try
				{
					$rc = (string) call_user_func(array($class, 'render'), $hook->args['default']);
				}
				catch (Exception $e)
				{
					return (string) $e->getMessage();
				}

				return $rc;
			}

			return;
		}
		*/

		$class = isset($hook->args['editor']) ? $hook->args['editor'] . '_WdEditorElement' : null;

		if (!$contents && isset($hook->args['default']))
		{
			try
			{
				$contents = (string) call_user_func(array($class, 'render'), $hook->args['default']);
			}
			catch (Exception $e)
			{
				return $patron->error($e->getMessage());
			}
		}
		else if ($template)
		{
			$contents = call_user_func(array($class, 'render'), $contents->contents);
		}

		return $template ? $patron->publish($template, $contents) : (string) $contents;
	}

	static public function translations(WdHook $hook, WdPatron $patron, $template)
	{
		$page = $hook->args['select'] ? self::model()->load($hook->args['select']) : $patron->context['$page'];

		$tnid = $page->tnid;

		$entries = $tnid
			? self::model()->loadAll('WHERE (nid = ? OR tnid = ?) AND nid != ?  ORDER BY language', array($tnid, $tnid, $page->nid))
			: self::model()->loadall('WHERE tnid = ? ORDER BY language', array($page->nid));

		$entries = $entries->fetchAll();

		if (!$entries)
		{
			return;
		}

		return $patron->publish($template, $entries);
	}

	static public function menu(WdHook $hook, WdPatron $patron, $template)
	{
		$select = $hook->args['select'];

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
			$parentid = $hook->args['parent'];

			if (!$parentid && count(WdLocale::$languages) > 1)
			{
				$parentid = '/' . WdLocale::$language;
			}

			$parentid = $parentid ? self::resolveParent($parentid) : 0;

			if ($parentid === false)
			{
				return $patron->error('Unknown parent: %parent', array('%parent' => $parentid));
			}

			$entries = self::model()->loadAll
			(
				'WHERE is_online = 1 AND is_navigation_excluded = 0 AND pattern = "" AND parentid = ? ORDER BY weight, created', array
				(
					$parentid
				)
			)
			->fetchAll();
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
			$entry->active = $entry->is_active = isset($active_pages[$entry->nid]);
		}

		if ($template)
		{
			return $patron->publish($template, $entries);
		}

		$nest = $hook->args['nest'];

		return self::menu_builder($entries, $nest);
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

	static public function breadcrumb(WdHook $hook, WdPatron $patron, $template)
	{
		$current = $page = isset($hook->args['page']) ? $hook->args['page'] : $patron->context['$page'];

		$links = array();

		while ($page)
		{
			$url = $page->url;

			$title = $page->title;
			$title = wd_entities($title);

			if ($page == $current)
			{
				$link = '<span class="current">' . $title . '</span>';
			}
			else
			{
				$link = strpos('<', $url) === false ? '<a href="' . $url . '">' . $title . '</a>' : $title;
			}

			$links[] = $link;

			$page = $page->parent;

			if ($page && $page->is_index)
			{
				break;
			}
		}

		if (!$current->is_index)
		{
			$links[] = '<a href="/' . $current->language . '">' . t('@site.pages.breadcrumb.home') . '</a>';
		}

		$links = array_reverse($links);

		//return implode('<span class="separator"> › </span>', $links);

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

	static public function sitemap(WdHook $hook, WdPatron $patron, $template)
	{
		$parentid = $hook->args['parent'];

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

		$maxnest = $hook->args['nest'];

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
			$parent = self::module()->find($parentid);

			if (!$parent)
			{
				return null;
			}

			$parentid = $parent->nid;
		}

		return $parentid;
	}

	static public function tracker(WdHook $hook, WdPatron $patron, $template)
	{
		global $registry;

		$ua = $registry->get('site.analytics.ua');

		if (!$ua)
		{
			return;
		}

		return <<<EOT
<script type="text/javascript">

	var _gaq = _gaq || [];
	_gaq.push(['_setAccount', '$ua']);
	_gaq.push(['_trackPageview']);

	(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	})();

</script>
EOT;
	}
}