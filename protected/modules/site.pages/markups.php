<?php

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
		$contentsid = $hook->params['id'];

		$contents = self::model('site.pages/contents')->loadRange
		(
			0, 1, 'WHERE pageid = ? AND contentsid = ?', array
			(
				$pageid,
				$contentsid
			)
		)
		->fetchAndClose();

		if (!$contents)
		{
			if (isset($hook->params['default']))
			{
				//echo t('default: \1', array($hook->params['default']));

				$class = $hook->params['editor'] . '_WdEditorElement';

				try
				{
					$rc = (string) call_user_func(array($class, 'render'), $hook->params['default']);
				}
				catch (Exception $e)
				{
					return (string) $e;
				}

				return $rc;
			}

			return;
		}

		return $template ? $patron->publish($template, $contents) : (string) $contents;
	}

	static public function translations(WdHook $hook, WdPatron $patron, $template)
	{
		$page = $hook->params['select'] ? self::model()->load($hook->params['select']) : $patron->context['$page'];

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
		$select = $hook->params['select'];

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
					0, 1, 'WHERE title = ? OR slug = ? AND scope = "site.pages"', array
					(
						$select, $select
					)
				)
				->fetchAndClose();
			}
			catch (Exception $e) {}

			if (!$menu)
			{
				$patron->error('Uknown menu: %menu', array('%menu' => $select));

				return;
			}

			$entries = $menu->nodes;
		}
		else
		{
			$parentid = $hook->params['parent'];

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

		$nest = $hook->params['nest'];

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
		$current = $page = isset($hook->params['page']) ? $hook->params['page'] : $patron->context['$page'];

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
		// TODO-20100125: language support

		//$parentid = self::resolveParent($hook->params['parent']);

		$parentid = $hook->params['parent'];

		if (!$parentid && count(WdLocale::$languages) > 1)
		{
			$parentid = '/' . WdLocale::$language;
		}

		$parentid = $parentid ? self::resolveParent($parentid) : true;

		/*
		if (!$parentid)
		{
			return $patron->error('Unknown parent: %parent', array('%parent' => $parentid));
		}
		*/

		$maxnest = $hook->params['nest'];

		/* AND is_navigation_excluded = 0 */

		$entries = self::model()->loadAll('WHERE is_online = 1 AND pattern NOT LIKE "%<%" ORDER BY weight, created')->fetchAll();

		$tree = site_pages_WdManager::entriesTreefy($entries);

		$rc = self::sitemap_callback($tree, 1, $maxnest, $parentid);

		return $rc/* . wd_dump($tree)*/;
	}

	static protected function sitemap_callback($entries, $level, $maxnest, $startid)
	{
		$rc = null;

		foreach ($entries as $entry)
		{
			if ($startid === true)
			{
				$rc .= str_repeat("\t", $level + 1) . '<li><a href="' . $entry->url . '">' . $entry->title . '</a>' . PHP_EOL;
			}

			if (($maxnest === false || $level < $maxnest) && !empty($entry->children))
			{
				$rc .= self::sitemap_callback($entry->children, $startid === true ? $level + 1 : $level, $maxnest, $entry->nid == $startid ? true : $startid);
			}

			if ($startid === true)
			{
				$rc .= '</li>' . PHP_EOL;
			}
		}

		if ($rc && $startid === true)
		{
			$rc = str_repeat("\t", $level) . '<ul class="level' . $level . '">' . PHP_EOL . $rc . str_repeat("\t", $level) . '</ul>';
		}

		return $rc;
	}

	static protected function resolveParent($parentid)
	{
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
}