<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_articles_WdMarkups extends patron_markups_WdHooks
{
	static protected function model($name='contents.articles')
	{
		return parent::model($name);
	}

	static public function articles(array $args, WdPatron $patron, $template)
	{
		#
		# extract attributes
		#

		extract($args, EXTR_PREFIX_ALL, 'attr');

		#
		#
		#

		// TODO-20090121: ajouter l'atribut group="username" grouporder="asc"
		// on pourra peut être se débarasser de mouth, categories, user...

		$options = $args;

		#
		# build query
		#

		$where = array();
		$params = array();

		#
		# section
		#

		if ($attr_category)
		{
			$where[] = '{taxonomy:category} = ?';
			$params[] = $attr_category;
		}

		if ($attr_tag)
		{
			//$where[] = '? IN ({taxonomy:tags})';
			//$params[] = $attr_tag;
			$where[] = '{taxonomy:tags} LIKE ?';
			$params[] = '%' . $attr_tag . '%';
		}

		if ($attr_author)
		{
			$where[] = '(SELECT username FROM {prefix}user_users WHERE uid = node.uid) = ?';
			$params[] = $attr_author;
		}

		if ($attr_date)
		{
			$names = array('YEAR', 'MONTH', 'DAY');

			if (preg_match('#(\d{4})?-(\d{2})?#', $attr_date, $match))
			{
//				echo l('date: \1, match: \2', $attr_date, $match);

				array_shift($match);

				foreach ($match as $key => $value)
				{
					$where[] = $names[$key] . '(`date`) = ?';
					$params[] = $value;
				}
			}
		}

		$where[] = 'is_online = 1';

		#
		# build query
		#

		if ($where)
		{
			$query = 'WHERE ' . implode(' AND ', $where);
		}
		else
		{
			$query = '';
		}





		/*
		if ($attr_author)
		{
			$query = 'INNER JOIN {prefix}user_users USING(uid) ' . $query;
		}
		*/




		$count = self::model()->count(null, null, $query, $params);

		$options['count'] = $count;
		$options['pages'] = $attr_limit ? ceil($count / $attr_limit) : 1;

		/*
		 * FIXME-20100702: this is disabled because the markup might be used multiple time on
		 * the same page. (e.g. list, recent...)
		 *
		if ($attr_limit && $attr_page === null && isset($_GET['page']))
		{
			$attr_page = $_GET['page'];
		}
		*/

		#
		# load entries
		#

		if ($attr_order == 'random')
		{
			$query .= ' ORDER BY RAND()';
		}
		else if ($attr_by)
		{
			$query .= " ORDER BY `$attr_by` " . $attr_order;
		}

		if ($attr_limit)
		{
			$entries = self::model()->loadRange($attr_page * $attr_limit, $attr_limit, $query, $params);
		}
		else
		{
			$entries = self::model()->loadAll($query, $params);
		}

		//$publisher->error('<code>' . $entries->queryString . '</code>');

		$entries = $entries->fetchAll();

		WdEvent::fire
		(
			'publisher.nodes_loaded', array
			(
				'nodes' => $entries
			)
		);

		#
		# save options, they'll be used to handle pages
		#

		//$patron->set('self.range', $options);
		$patron->context['self']['range'] = array
		(
			'count' => $count,
			'limit' => $attr_limit,
			'page' => $attr_page
		);

		return $patron->publish($template, $entries);
	}

	static public function articles_read(array $args, WdPatron $patron, $template)
	{
		$limit = $args['limit'];
		$scope = 'contents.articles';

		$hits = self::model('feedback.hits')->query
		(
			'SELECT hit.*, (hits / (TO_DAYS(CURRENT_DATE) - TO_DAYS(first))) AS perday
			FROM {self} as hit
			INNER JOIN {prefix}system_nodes USING(nid)
			WHERE is_online = 1 AND constructor = ?
			ORDER BY hits DESC LIMIT ' . $limit, array
			(
				$scope
			)
		)
		->fetchAll(PDO::FETCH_OBJ);

		$nids = array();

		foreach ($hits as $hit)
		{
			$nids[$hit->nid] = $hit;
		}

		$entries = self::model($scope)->loadAll
		(
			'WHERE nid IN (' . implode(',', array_keys($nids)) . ')'
		)
		->fetchAll();

		foreach ($entries as $entry)
		{
			$nids[$entry->nid]->node = $entry;
		}

		return $patron->publish($template, array_values($nids));
	}

	static public function articles_authors(array $args, WdPatron $patron, $template)
	{
		extract($args, EXTR_PREFIX_ALL, 'attr');

		$query = 'where `section` ';
		$params = array();

		if ($attr_section === null)
		{
			$query .= ' is null';
		}
		else
		{
			$query .= ' = ?';
			$params = $attr_section;
		}

		$query .= 'and is_online = 1 group by `uid` order by `username`';

		// FIXME-20091208: because users may have needed informations, they should be loaded using
		// the load() method of their model.

		$users = self::model()->select
		(
			array('uid', 'username'), $query, $params
		)
		->fetchAll();

		return $patron->publish($template, $users);
	}

	static public function article(array $args, WdPatron $patron, $template)
	{
		global $core;

		$select = $args['select'];

		$where = array();
		$params = array();

		foreach ($select as $key => $value)
		{
			switch ($key)
			{
				case 'month':
				{
					$where[] = 'MONTH(date) = ?';
					$params[] = $value;
				}
				break;

				case 'year':
				{
					$where[] = 'YEAR(date) = ?';
					$params[] = $value;
				}
				break;

				case 'slug':
				{
					$where[] = 'slug = ?';
					$params[] = $value;
				}
				break;

				case 'categoryslug':
				{
					$where[] = 'nid IN (SELECT nid FROM {prefix}taxonomy_terms
					INNER JOIN {prefix}taxonomy_terms_nodes USING(vtid) WHERE termslug = ?)';
					$params[] = $value;
				}
				break;
			}
		}

		$entry = self::model()->where(implode(' AND ', $where), $params)->order('created desc')->limit(1)->one();

		if (!$entry)
		{
			if (isset($select['slug']))
			{
				$slug = $select['slug'];

				$tries = self::model()->select
				(
					array('nid', 'slug'), 'ORDER BY `date` DESC'
				)
				->fetchPairs();

				$key = null;
				$max = 0;

				foreach ($tries as $nid => $compare)
				{
					#
					# compare string
					#

					similar_text($slug, $compare, $p);

					#
					# log result
					#

					//printf('"%s" ?= [id:%02d] "%s" == %.2f%%<br />', $title, $nid, $compare, $p);

					if ($p > $max)
					{
						#
						# we have found a better match, we save its id
						#

						$key = $nid;

						if ($p > 90)
						{
							#
							# huge match, we can break the loop
							#

							break;
						}

						$max = $p;
					}
				}

				if ($key)
				{
					wd_log('Article %title has been rescued !', array('%title' => $slug));

					$entry = self::model()->load($key);
				}
			}
		}

		if (!$entry)
		{
			throw new WdHTTPException
			(
				'The requested entry was not found: !select', array
				(
					'!select' => $args['select']
				),

				404
			);
		}

		if (!$entry->is_online)
		{
			if (!$core->user->has_ownership('contents.articles', $entry))
			{
				throw new WdHTTPException
				(
					'The requested entry %uri requires authentication.', array
					(
						'%uri' => $entry->constructor . '/' . $entry->nid
					),

					401
				);
			}

			$entry->title .= ' =!=';
		}

		WdEvent::fire
		(
			'publisher.nodes_loaded', array
			(
				'nodes' => array($entry)
			)
		);

		global $page;

		$page->node = $entry;

		return $patron->publish($template, $entry);
	}

	static public function by_date(array $args, WdPatron $patron, $template)
	{
		extract($args, EXTR_PREFIX_ALL, 'p');

		$query = 'node.*, article.* FROM {prefix}system_nodes node
		INNER JOIN {prefix}contents article USING(nid) WHERE is_online = 1';
		$params = array();

		if ($p_group)
		{
			$query = 'DATE_FORMAT(`date`, ?), ' . $query;
			$params[] = $p_group;
		}

		$query .= ' ORDER BY `date` ' . $p_order;

		if ($p_limit)
		{
			$query .= " LIMIT $p_start, $p_limit";
		}
		else if ($p_start)
		{
			$query .= " LIMIT $p_start";
		}

		$entries = self::model()->query('SELECT ' . $query, $params)->fetchAll($p_group ? PDO::FETCH_GROUP | PDO::FETCH_CLASS : PDO::FETCH_CLASS, 'contents_WdActiveRecord');

		return $patron->publish($template, $entries);
	}

	static public function by_author(array $args, WdPatron $patron, $template)
	{
		$entries = self::model()->query
		(
			'SELECT username, node.*, article.*
			FROM {prefix}system_nodes node
			INNER JOIN {self} article USING(nid)
			INNER JOIN {prefix}user_users USING(uid)
			WHERE is_online = 1 ORDER BY `username`, `date` DESC'
		)
		->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_CLASS, 'contents_articles_WdActiveRecord');

		return $patron->publish($template, $entries);
	}
}
