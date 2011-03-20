<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
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

			self::$module = $core->modules[$name];
		}

		return self::$module;
	}

	static protected function model($name='site.pages')
	{
		return parent::model($name);
	}

	static public function content(array $args, WdPatron $patron, $template)
	{
		global $page;

		$render = $args['render'];

		if ($render == 'none')
		{
			return;
		}

		$pageid = $page->nid;
		$contentid = $args['id'];
		$contents = array_key_exists($contentid, $page->contents) ? $page->contents[$contentid] : null;

		$editor = null;

		if (!is_string($contents))
		{
			if (!$contents && !empty($args['inherit']))
			{
//				wd_log('Contents %id is not defined for page %title, but is inherited, searching for heritage...', array('%id' => $contentid, '%title' => $page->title));

				$node = $page->parent;

				while ($node)
				{
					$node_contents = $node->contents;

					if (empty($node_contents[$contentid]))
					{
						$node = $node->parent;

						continue;
					}

					$contents = $node_contents[$contentid];

					break;
				}

				#
				# maybe the home page define the contents, but because the home page is not the parent
				# of pages on single language sites, we have to check it now.
				#

				if (!$contents)
				{
					$node_contents = $page->home->contents;

//					wd_log('... try with home page %title', array('%title' => $page->title));

					if (isset($node_contents[$contentid]))
					{
						$contents = $node_contents[$contentid];
					}
				}

//				wd_log('... and found: \1', array($contents));
			}

			if ($contents instanceof site_pages_contents_WdActiveRecord)
			{
				$editor = $contents->editor;
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
		}

		if (!$contents)
		{
			return;
		}

		$rc = $template ? $patron->publish($template, $contents) : $contents->render();

		if (!$rc)
		{
			return;
		}

		if (preg_match('#\.html$#', $page->template) || !empty($args['no-wrap']))
		{
			$rc = '<div id="content-' . $contentid . '" class="editor-' . wd_normalize($editor) . '">' . $rc . '</div>';
		}

		return $rc;
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

		if (!$translations)
		{
			return;
		}

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
		global $core, $page;

		$select = $args['select'];
		$model = $core->models['organize.lists'];

		// TODO-20100323: Now that the organize.lists module brings custom menus, the markups needs
		// a complete overhaul. We need to find a commun ground between _lists_ and the navigation
		// menu.

		if ($select)
		{
			$menu = null;

			try
			{
				$menu = $model->where('(title = ? OR slug = ?) AND scope = "site.pages"', $select, $select)
				->visible->order('language DESC')->one;
			}
			catch (Exception $e) {}

			if (!$menu)
			{
				return;
			}

			$entries = $menu->nodes;

			if ($template)
			{
				return $patron->publish($template, $entries);
			}

			$rc = '<ul>';

			foreach ($entries as $entry)
			{
				$rc .= '<li class="' . $entry->css_class . '"><a href="' . $entry->url . '">' . $entry->label . '</a></li>';
			}

			$rc .= '</ul>';

			return $rc;
		}
		/*
		else
		{
			$parentid = $args['parent'];
			$parentid = $parentid ? self::resolveParent($parentid) : 0;

			if ($parentid === false)
			{
				return $patron->error('Unknown parent: %parent', array('%parent' => $parentid));
			}

			if (1)
			{
				$entries = $model
				->where('is_online = 1 AND is_navigation_excluded = 0 AND pattern = "" AND parentid = ?', $parentid)
				->order('weight, created')
				->all;
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

		$rc = self::menu_builder($entries, $nest);

		return $rc;
		*/
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

			$rc .= '<li class="' . $entry->css_class . '">';

			/*
			if (isset($entry->is_active))
			{
				$rc .= ' class="is_active"';
			}

			$rc .= '>';
			*/

			$rc .= '<a href="' . $entry->url . '">' . wd_entities($entry->label) . '</a>';

			$children = self::model()
			->where('is_online = 1 AND is_navigation_excluded = 0 AND pattern = "" AND parentid = ?', $entry->nid)
			->order('weight, created')
			->all;

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
		$classes = array();

		while ($node)
		{
			$url = $node->url;
			$label = $node->label;
			$label = wd_shorten($label, 48);
			$label = wd_entities($label);

			$links[] = $links ? '<a href="' . $url . '">' . $label . '</a>' : '<strong>' . $label . '</strong>';
			$classes[] = $node->css_class;

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
			$classes[] = $home->css_class;
		}

		$links = array_reverse($links);
		$classes = array_reverse($classes);

		if ($template)
		{
			return $patron->publish($template, $links);
		}

		$rc = '<ol id="breadcrumb">';

		foreach ($links as $i => $link)
		{
			$rc .= '<li class="' . $classes[$i] . '">';

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

		$children = self::model()->where('is_online = 1 AND parentid = ? AND pattern = ""', $parentid)->order('weight, created')->all;

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
		// TODO-20101216: The view should handle parsing template or not

		return $render = view_WdEditorElement::render($args['name'], $patron, $template);

		return $template ? $patron->publish($template, $render) : $render;
	}
}

class site_pages_languages_WdMarkup extends patron_WdMarkup
{
	public function __invoke(array $args, WdPatron $patron, $template)
	{
		global $core, $page;

		$source = isset($page->node) ? $page->node : $page;
		$translations = $source->translations;
		$translations_by_language = array();

		if ($translations)
		{
			$translations[$source->nid] = $source;
			$translations_by_language = array_flip($core->models['site.sites']->select('language')->where('status = 1')->order('weight, siteid')->all(PDO::FETCH_COLUMN));

			if ($source instanceof site_pages_WdActiveRecord)
			{
				foreach ($translations as $translation)
				{
					if (!$translation->is_accessible)
					{
						continue;
					}

					$translations_by_language[$translation->language] = $translation;
				}
			}
			else // nodes
			{
				foreach ($translations as $translation)
				{
					if (!$translation->is_online)
					{
						continue;
					}

					$translations_by_language[$translation->language] = $translation;
				}
			}

			foreach ($translations_by_language as $language => $translation)
			{
				if (is_object($translation))
				{
					continue;
				}

				unset($translations_by_language[$language]);
			}
		}

		if (!$translations_by_language)
		{
			$translations_by_language = array
			(
				($source->language ? $source->language : $page->language) => $source
			);
		}

		WdEvent::fire
		(
			'alter.page.languages:before', array
			(
				'target' => $page,
				'translations_by_languages' => &$translations_by_language
			)
		);

		if ($template)
		{
			return $patron->publish($template, $translations_by_language);
		}

		$page_language = $page->language;
		$languages = array();

		foreach ($translations_by_language as $language => $translation)
		{
			$languages[$language] = array
			(
				'class' => $language . ($language == $page_language ? ' active' : ''),
				'render' => $language == $page_language ? '<strong>' . $language . '</strong>' : '<a href="' . $translation->url . '">' . $language . '</a>',
				'node' => $translation
			);
		}

		WdEvent::fire
		(
			'alter.page.languages', array
			(
				'target' => $page,
				'languages' => &$languages
			)
		);

		$rc = '<ul>';

		foreach ($languages as $language)
		{
			$rc .= '<li class="' . $language['class'] . '">' . $language['render'] . '</li>';
		}

		$rc .= '</ul>';

		return $rc;
	}
}

class site_pages_navigation_WdMarkup extends patron_WdMarkup
{
	public function __invoke(array $args, WdPatron $patron, $template)
	{
		global $core, $page;

		$this->model = $core->models['site.pages'];

		$mode = $args['mode'];

		if ($mode == 'leaf')
		{
			$node = $page;

			while ($node)
			{
				if ($node->navigation_children)
				{
					break;
				}

				$node = $node->parent;
			}

			if (!$node)
			{
				return;
			}

			return $patron->publish($template, $node);
		}




















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

//			wd_log('from node: \1', array($node));

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
			}
		}

		$entries = $this->model->loadAllNested($page->siteid, $parentid, $depth);

		if (!$entries)
		{
			return false;
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

		$rc = $template ? $patron->publish($template, $entries) : self::navigation_builder($entries, $depth, $args['min-child']);

		WdEvent::fire
		(
			'alter.markup.navigation', array
			(
				'rc' => &$rc,
				'page' => $page,
				'entries' => $entries,
				'args' => $args
			)
		);

		return $rc;
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

			$class = $entry->css_class;

			if ($entry->navigation_children)
			{
				$class .= ' has-children';
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


	static public function navigation_leaf(array $args, WdPatron $patron, $template)
	{
		global $core, $page;

		$level = $args['level'];
		$depth = $args['depth'];

		$start_page = $page;

		while ($start_page && $start_page->depth > $level)
		{
			$start_page = $page->parent;
		}

		echo "$page->depth ($level), startpage: $start_page->title<br />";

		$records = $core->models['site.pages']->loadAllNested($page->siteid, $start_page->nid, $depth);

		foreach ($records as $record)
		{
			echo "$record->title<br />";
		}

		var_dump($records);

//		$records = self::navigation_filter($records);

//		var_dump($records);

		$rc = self::navigation_builder($records, $depth, false);

		echo $rc;

		var_dump($rc);
	}
}

class site_pages_sitemap_WdMarkup extends patron_WdMarkup
{
	public function __invoke(array $args, WdPatron $patron, $template)
	{
		global $core, $page;

		$this->model = $core->models['site.pages'];

		$entries = $this->model->loadAllNested($page->siteid);

		if (!$entries)
		{
			return;
		}

		$entries = self::filter($entries);

		return self::build($entries);
	}

	static protected function filter($entries)
	{
		$filtered = array();

		foreach ($entries as $entry)
		{
			if ($entry->pattern || !$entry->is_online)
			{
				continue;
			}

			$entry->is_active = !empty($entry->is_active);
			$entry->children = isset($entry->children) ? self::filter($entry->children) : array();

			$filtered[] = $entry;
		}

		return $filtered;
	}

	static protected function build($entries, $depth=false, $min_child=false, $level=1)
	{
		$rc = '';

		foreach ($entries as $entry)
		{
			if ($level == 1 && ($min_child !== false && (count($entry->children) < $min_child)))
			{
				continue;
			}

			$class = '';

			if ($entry->children)
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

			if (($depth === false || $level < $depth) && $entry->children)
			{
				$rc .= self::build($entry->children, $depth, $min_child, $level + 1);
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