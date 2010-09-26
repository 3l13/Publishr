<?php

class site_pages_languages_WdMarkup extends patron_WdMarkup
{
	public function __invoke(array $args, WdPatron $patron, $template)
	{
		global $page;

		$page_language = $page->language;

		if (!$page_language)
		{
			return;
		}

		$languages = array_combine(WdLocale::$languages, array_pad(array(), count(WdLocale::$languages), null));
		$translations = $page->translations;

		foreach ($translations as $i => $translation)
		{
			if (!$translation->is_accessible)
			{
				continue;
			}

			$languages[$translation->language] = $translation;
		}

		$languages[$page->language] = $page;

		foreach ($languages as $language => $node)
		{
			if ($node)
			{
				continue;
			}

			unset($languages[$language]);
		}

		if ($template)
		{
			return $patron->publish($template, $languages);
		}

		$rc = '<ol>';

		foreach ($languages as $language => $node)
		{
			$rc .= '<li class="' . $language . ($language == $page->language ? ' active' : '') . '">';

			if ($language == $page->language)
			{
				$rc .= '<strong>' . strtoupper($language) . '</strong>';
			}
			else
			{
				$rc .= '<a href="' . $node->url . '">' . strtoupper($node->language) . '</a>';
			}

			$rc .= '</li>';
		}

		$rc .= '</ol>';

		return $rc;
	}
}

class site_pages_navigation_WdMarkup extends patron_WdMarkup
{
	protected $constructor = 'site.pages';

	public function __invoke(array $args, WdPatron $patron, $template)
	{
		global $page;

//		$db_count = $stats['queries']['primary'];

		$depth = $args['depth'];

		if ($args['from-level'])
		{
			$node = $page;
			$from_level = $args['from-level'];

			#
			# The current page level is smaller than the page level requested, the navigation is
			# canceled.
			#

			if ($node->depth < $from_level)
			{
				return;
			}

			while ($node->depth > $from_level)
			{
				$node = $node->parent;
			}

			$parentid = $node->nid;
		}
		else
		{
			$parentid = $args['parent'];

			if (is_object($parentid))
			{
				$parentid = $parentid->nid;
			}
			else
			{
				if ($parentid && !is_numeric($parentid))
				{
					$parent = $this->model->loadByPath($parentid);

					$parentid = $parent->nid;
				}

				/* DITRY: MULTISITE
				if (!$parentid && count(WdLocale::$languages) > 1)
				{
					$parentid = $this->model->select
					(
						'nid', 'WHERE slug = ? AND parentid = 0 LIMIT 1', array
						(
							WdLocale::$language
						)
					)
					->fetchColumnAndClose();
				}
				*/
			}
		}

		$entries = $this->model->loadAllNested($page->siteid, $parentid, $depth);

		if (!$entries)
		{
			return '<!-- empty navigation -->';
		}

		#
		# set active pages
		#

		$node = $page;

		while ($node)
		{
			$node->is_active = true;
			$node = $node->parent;
		}

//		wd_log_time('navigation start');

		$entries = self::navigation_filter($entries);

		return $template ? $patron->publish($template, $entries) : self::navigation_builder($entries, $depth, $args['min-child']);
	}

	static protected function navigation_filter($entries)
	{
		$filtered = array();

		foreach ($entries as $entry)
		{
			if ($entry->pattern || !$entry->is_online || $entry->is_navigation_excluded)
			{
				continue;
			}

			$entry->is_active = !empty($entry->is_active);
			$entry->navigation_children = isset($entry->children) ? self::navigation_filter($entry->children) : array();

			$filtered[] = $entry;
		}

		return $filtered;
	}

	static protected function navigation_builder($entries, $depth, $min_child, $level=1)
	{
		$rc = '';

		foreach ($entries as $entry)
		{
			if ($level == 1 && ($min_child !== false && (count($entry->navigation_children) < $min_child)))
			{
				continue;
			}

			$class = '';

			if ($entry->navigation_children)
			{
				$class .= 'has-children';
			}

			if (!empty($entry->is_active))
			{
				if ($class)
				{
					$class .= ' ';
				}

				$class .= 'active';
			}

			$rc .=  $class ? '<li class="' . $class . '">' : '<li>';
			$rc .= '<a href="' . $entry->url . '">' . $entry->label . '</a>';

			if ($level < $depth && $entry->navigation_children)
			{
				$rc .= self::navigation_builder($entry->navigation_children, $depth, $min_child, $level + 1);
			}

			$rc .= '</li>';
		}

		if (!$rc)
		{
			return;
		}

		return '<ol class="lv' . $level . '">' . $rc . '</ol>';
	}
}