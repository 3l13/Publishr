<?php

class contents_articles_WdMarkups extends patron_markups_WdHooks
{
	static protected function model($name='contents.articles')
	{
		return parent::model($name);
	}

	static public function articles(WdHook $hook, WdPatron $patron, $template)
	{
		#
		# extract attributes
		#

		extract($hook->args, EXTR_PREFIX_ALL, 'attr');

		#
		#
		#

		// TODO-20090121: ajouter l'atribut group="username" grouporder="asc"
		// on pourra peut être se débarasser de mouth, categories, user...

		$options = $hook->args;

		#
		# build query
		#

		$where = array();
		$params = array();

		#
		# section
		#

		/*
		if ($attr_section === null)
		{
			$where[] = '`' . self::SECTION . '` is null';
		}
		else
		{
			$where[] = '`' . self::SECTION . '` = ?';
			$params[] = $attr_section;
		}
		*/

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
			/*
			global $core;


			$users_admin = $core->getModule('user', 'users');
			$user = $users_admin->load($attr_author);

			$where[] = "%i = %d";
			$params[] = self::UID;
			$params[] = $user ? $user->uid : $attr_author;
			*/

			//'`' . (is_numeric($attr_author) ? 'uid' : 'username') . '` = ?'

			$where[] = '(SELECT username FROM {prefix}user_users WHERE uid = t2.uid) = ?';
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

		if ($attr_limit && $attr_page === null && isset($_GET['page']))
		{
			$attr_page = $_GET['page'];
		}

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

	static public function articles_read(WdHook $hook, WdPatron $patron, $template)
	{
		$limit = $hook->args['limit'];
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

	static public function articles_authors(WdHook $hook, WdPatron $patron, $template)
	{
		extract($hook->args, EXTR_PREFIX_ALL, 'attr');

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

	static public function article(WdHook $hook, WdPatron $patron, $template)
	{
		$select = $hook->args['select'];

		#
		#
		#

		$where = array();
		$params = array();

		$where[] = 'is_online = 1';

		if (is_array($select))
		{
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
					/*
					case 'categoryslug':
					{
						$where[] = 'temrslug = ?';
						$params[] = $value;
					}
					break;
					*/
				}
			}

			$entries = self::model()->loadAll('WHERE ' . implode(' AND ', $where), $params)->fetchAll();

			$entry = null;
			$count = count($entries);

			if (!$count)
			{
				if (isset($select['slug']))
				{
					$slug = $select['slug'];

					$tries = self::model()->select
					(
						array('nid', 'title'), 'ORDER BY `date` DESC'
					)
					->fetchPairs();

					$key = null;
					$max = 0;

					foreach ($tries as $nid => $title)
					{
						#
						# normalize the title to compare, the same way we did
						# for the title to find
						#

						$compare = wd_normalize($title);

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
			else if ($count == 1)
			{
				$entry = array_shift($entries);
			}
			else
			{
				if (isset($select['slug']))
				{
					$slug = $select['slug'];

					$entry = null;

					foreach ($entries as $try)
					{
						if ($slug != $try->slug)
						{
							continue;
						}

						$entry = $try;

						break;
					}
				}
			}

			if (!$entry)
			{
				$patron->error('Unable to find matching article');

				return;
			}

			if (!$entry->is_online)
			{
				global $core, $app;

				if ($app->user->hasOwnership($core->getModule('contents.articles'), $entry))
				{
					return '<strong>This article is supposed to be offline, but as the owner you are able to see it.</strong><br />'

					. $patron->publish($template, $entry);
				}
				else
				{
					$patron->error('The article %title is offline', array('%title' => $entry->title));

					return;
				}
			}

			return $patron->publish($template, $entry);
		}
	}

	static public function by_date($hook, $publisher, $nodes)
	{
		extract($hook->args, EXTR_PREFIX_ALL, 'p');

		$query = 'node.*, article.* FROM {prefix}system_nodes node INNER JOIN {self} article USING(nid) WHERE is_online = 1';
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

		$entries = self::model()->query('SELECT ' . $query, $params)->fetchAll($p_group ? PDO::FETCH_GROUP | PDO::FETCH_CLASS : PDO::FETCH_CLASS, 'contents_articles_WdActiveRecord');

		return $publisher->publish($nodes, $entries);
	}

	static public function by_author($hook, $publisher, $nodes)
	{
		extract($hook->args, EXTR_PREFIX_ALL, 'p');

		$entries = self::model()->query
		(
			'SELECT username, node.*, article.*
			FROM {prefix}system_nodes node
			INNER JOIN {self} article USING(nid)
			INNER JOIN {prefix}user_users USING(uid)
			WHERE is_online = 1 ORDER BY `username`, `date` DESC'
		)
		->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_CLASS, 'contents_articles_WdActiveRecord');

		return $publisher->publish($nodes, $entries);
	}
}
