<?php

class site_pages_navigation_markup extends patron_WdMarkup
{
	protected $constructor = 'site.pages';

	public function __invoke(array $args, WdPatron $patron, $template)
	{
//		$db_count = $stats['queries']['primary'];

		$depth = $args['depth'];
		$parentid = $args['parent'];

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

		$entries = $this->model->loadAllNested($parentid, $depth);

		#
		# set active pages
		#

		global $page;

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