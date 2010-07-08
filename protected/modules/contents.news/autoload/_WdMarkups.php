<?php

class contents_news_WdMarkups extends contents_WdMarkups
{
	/*
	static protected function model($name='contents.news')
	{
		return parent::model($name);
	}
	*/

	/*
	static public function home(array $args, WdPatron $patron, $template)
	{
		global $registry;

		list($conditions, $values) = self::model()->parseConditions
		(
			array
			(
				'constructor' => 'contents.news',
				'language' => WdLocale::$language,
				'is_online' => true,
				'is_home_excluded' => false
			)
		);

		$entries = self::model()->loadRange
		(
			0, $registry->get('contentsNews.homeLimit', 5), 'WHERE ' . implode(' AND ', $conditions) . ' ORDER BY date DESC', $values
		)
		->fetchAll();

		if (!$entries)
		{
			return;
		}

		return $patron->publish($template, $entries);
	}
	*/

	/*
	static public function list_(array $args, WdPatron $patron, $template)
	{
		global $registry, $document;

		// TODO-20100601: move document modifiers to view definitions

		$document->css->add('../public/list.css');

		#
		#
		#

		$select = $args['select'];

		$page = isset($select['page']) ? $select['page'] : 0;
		$categoryslug = isset($select['categoryslug']) ? $select['categoryslug'] : null;

		$limit = $args['limit'];

		if ($limit === null)
		{
			$limit = $registry->get('contentsNews.listLimit', 10);
		}

		//$page = $args['page'];

		$where = array
		(
			'is_online = 1',
			'(language = "" OR language = ?)',
			'constructor = "contents.news"'
		);

		$params = array
		(
			WdLocale::$language
		);

		if ($categoryslug)
		{
			$ids = self::model('taxonomy.terms/nodes')->select
			(
				'nid', 'INNER JOIN {prefix} taxonomy_vocabulary_scope scope USING(vid) WHERE termslug = ? AND scope.scope = ?', array
				(
					$categoryslug,
					'contents.news'
				)
			)
			->fetchAll(PDO::FETCH_COLUMN);

			if (!$ids)
			{
				return '<p>' . t('There is no entry in the %category category', array('%category' => $categoryslug)) . '</p>';
			}

			$where[] = 'nid IN(' . implode(',', $ids) . ')';
		}

		$where = 'WHERE ' . implode(' AND ', $where);;

		$count = self::model()->count(null, null, $where, $params);

		$entries = self::model()->loadRange
		(
			$page * $limit, $limit, $where . ' ORDER BY date DESC, title', $params
		)
		->fetchAll();

		if (!$entries)
		{
			return;
		}

		$patron->context['self']['range'] = array
		(
			'count' => $count,
			'page' => $page,
			'limit' => $limit
		);

		return $patron->publish($template, $entries);
	}
	*/

	/*
	static public function view(array $args, WdPatron $patron, $template)
	{
		$where  = array();
		$params = array();

		$select = $args['select'];

		if (is_array($select))
		{
			list($where, $params) = self::model()->parseConditions($select);
		}
		else if (is_numeric($select))
		{
			$where[] = '`nid` = ?';
			$params[] = $select;
		}
		else
		{
			$where[] = '`slug` = ? OR `title` = ?';
			$params[] = $select;
			$params[] = $select;
		}

		$where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

		$entry = self::model()->loadRange(0, 1, $where . ' ORDER BY `date` DESC', $params)->fetchAndClose();

		//var_dump($where, $params, $entry);

		if (!$entry)
		{
			return;
		}
		else if (!$entry->is_online)
		{
			return '<p>Entrée hors ligne</p>';
		}

		return $patron->publish($template, $entry);
	}
	*/
}