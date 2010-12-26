<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class site_pages_WdModel extends system_nodes_WdModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		if ($key && isset($properties[Page::PARENTID]) && $key == $properties[Page::PARENTID])
		{
			throw new WdException('A page connot be its own parent');
		}

		return parent::save($properties, $key, $options);
	}

	/**
	 * Load a page object given an URL path.
	 *
	 * @param string $url
	 * @return site_pages_WdActiveRecord
	 */

	public function loadByPath($url)
	{
		global $core;

		$pos = strrpos($url, '.');
		$extension = null;

		if ($pos && $pos > strrpos($url, '/'))
		{
			$extension = substr($url, $pos);
		 	$url = substr($url, 0, $pos);
		}

		#
		# matching site
		#

		$site = $core->site;

		if (!$site)
		{
			WdDebug::trigger('No matching site for uri: %uri', array('%uri' => $url));

			return false;
		}

		$siteid = $site->siteid;
		$site_path = $site->path;

//		wd_log('url: \1, site url: \2', array($url, $site_path));

		if ($site_path)
		{
			$url = substr($url, strlen($site_path));
		}

		#
		#
		#

		if ($url{strlen($url) - 1} == '/')
		{
			$url = substr($url, 0, -1);
		}

		if (!$url)
		{
			#
			# The home page is requested, we load the first parentless online page of the site.
			#

			$page = $this
			->where('siteid = ? AND parentid = 0 AND is_online = 1', $siteid)
			->order('weight, created')
			->one();

			if ($page && !$this->retrieve($page->nid))
			{
				$this->store($page->nid, $page);
			}

			return $page;
		}

		$parts = explode('/', $url);

		array_shift($parts);

		$parts_n = count($parts);

		$vars = array();

		#
		# We load from all the pages just what we need to find a matching path, and create a tree
		# with it.
		#

		$tries = $this->select('nid, parentid, slug, pattern')->where(array('siteid' => $siteid))->all(PDO::FETCH_OBJ);
		$tries = self::nestNodes($tries);

		$try = null;
		$pages_by_ids = array();

		for ($i = 0 ; $i < $parts_n ; $i++)
		{
			if ($try)
			{
				$tries = $try->children;
			}

			$part = $url_part = $parts[$i];

			#
			# first we search for a matching slug
			#

			foreach ($tries as $try)
			{
				if ($try->pattern || $part != $try->slug)
				{
					$try = null;

					continue;
				}

				#
				# found matching slug !
				#

				break;
			}

			#
			# if we didn't found a matching slug, let's try patterns
			#

			if (!$try)
			{
				foreach ($tries as $try)
				{
					$pattern = $try->pattern;

					if (!$pattern)
					{
						$try = null;

						continue;
					}

					$parsed = WdRoute::parse($pattern);
					$stripped = preg_replace('#<[^>]+>#', '', $pattern);

					$nparts = substr_count($stripped, '/') + 1;
					$url_part = implode('/', array_slice($parts, $i, $nparts));
					$match = WdRoute::match($url_part, $pattern);

					if ($match === false)
					{
						$try = null;

						continue;
					}

					#
					# found matching pattern !
					#

					#
					# we skip parts ate by the pattern
					#

					$i += $nparts - 1;

					#
					# even if the pattern matched, $match is not guaranteed to be an array,
					# 'feed.xml' is a valid pattern.
					#

					if (is_array($match))
					{
						$vars = $match + $vars;
					}

					break;
				}
			}

			#
			# well, if `try` is null at this point its that the path could not be matched
			#

			if (!$try)
			{
				return false;
			}

			#
			# otherwise, we continue
			#

			$pages_by_ids[$try->nid] = array
			(
				'url_part' => $url_part,
				'url_variables' => $vars
			);
		}

		#
		# append the extension (if any) to the leaf (the last point of the branch)
		#

		$pages_by_ids[$try->nid]['url_part'] .= $extension;

		#
		# All page objects have been loaded, we need to set up some additionnal properties, link
		# each page to its parent and propagate the online status.
		#

		$parent = null;
		$pages = $this->find(array_keys($pages_by_ids));

		foreach ($pages as $page)
		{
			$page->url_part = $pages_by_ids[$page->nid]['url_part'];
			$page->url_variables = $pages_by_ids[$page->nid]['url_variables'];

			if ($parent)
			{
				$page->parent = $parent;

				if (!$parent->is_online)
				{
					$page->is_online = false;
				}
			}

			$parent = $page;
		}

		return $page;
	}

	/**
	 * Load the nested nodes of a tree.
	 *
	 * Because the children
	 *
	 * @param unknown_type $parentid
	 * @param unknown_type $max_depth
	 */

	public function loadAllNested($siteid, $parentid=null, $max_depth=false)
	{
		$ids = $this->select('nid, parentid')->where('siteid = ?', $siteid)->order('weight, created')->all(PDO::FETCH_OBJ);

		$tree = self::nestNodes($ids, $by_id);

		if ($parentid)
		{
			if (empty($by_id[$parentid]))
			{
				return null;
			}

			$tree = $by_id[$parentid]->children;
		}

		if (!$tree)
		{
			return;
		}

		/*
		if ($parentid)
		{
			echo "<h1>node $parentid, max_depth: $max_depth</h1>";
			var_dump($by_id[$parentid]);
		}
		*/

		self::setNodesDepth($tree, $max_depth);

		/*
		if ($parentid)
		{
			if (empty($by_id[$parentid]))
			{
				return null;
			}

			if (empty($by_id[$parentid]->children))
			{
				echo "no children for $parentid<br />";
				var_dump($by_id[$parentid]);

				return null;
			}

			$tree = $by_id[$parentid]->children;
		}
		*/

		$nodes = self::levelNodesById($tree);
		$records = $this->find(array_keys($nodes));

		return self::nestNodes($records);
	}

	/**
	 * Nest an array of nodes, using their `parentid` property.
	 *
	 * Children are stored in the `children` property of their parents.
	 *
	 * Parent is stored in the `parent` property of its children.
	 *
	 * @param array $entries The array of nodes.
	 * @param array $parents The array of nodes, where the key is the entry's `nid`.
	 */

	static public function nestNodes($entries, &$entries_by_ids=null)
	{
		#
		# In order to easily access entries, they are store by their Id in an array.
		#

		$entries_by_ids = array();

		foreach ($entries as $entry)
		{
			$entry->children = array();

			$entries_by_ids[$entry->nid] = $entry;
		}

		#
		#
		#

		$tree = array();

		foreach ($entries_by_ids as $entry)
		{
			if (!$entry->parentid || empty($entries_by_ids[$entry->parentid]))
			{
				$tree[] = $entry;

				continue;
			}

			$entry->parent = $entries_by_ids[$entry->parentid];
			$entry->parent->children[] = $entry;
		}

		return $tree;
	}

	/**
	 * Walk the nodes and sets their depth level.
	 *
	 * @param $nodes The nodes to walk through.
	 * @param $max_depth The maximum depth level of the nodes. Nodes beyond the max_depth are removed.
	 * Default to false (no maximum depth level).
	 * @param $depth The depth level to start from. Default to 0.
	 */

	static public function setNodesDepth($nodes, $max_depth=false, $depth=0)
	{
		foreach ($nodes as $node)
		{
			$node->depth = $depth;

			if ($node->children)
			{
				if ($max_depth !== false && $max_depth == $depth)
				{
					if ($max_depth === 1)
					{
						echo "<h1>max_depth ($max_depth) reached for</h1>";
						var_dump($node);
					}

					#
					# The `children` property is unset rather then emptied, making the loading
					# of children possible by accessing the `children` property.
					#

					unset($node->children);
				}
				else
				{
					self::setNodesDepth($node->children, $max_depth, $depth + 1);
				}
			}
		}
	}

	/**
	 * Creates an array from all the nested nodes, where keys are node's Id.
	 *
	 * @param $nodes
	 */

	static public function levelNodesById($nodes)
	{
		$by_id = array();

		foreach ($nodes as $node)
		{
			$by_id[$node->nid] = $node;

			if (isset($node->children))
			{
				$by_id += self::levelNodesById($node->children);
			}
		}

		return $by_id;
	}
}